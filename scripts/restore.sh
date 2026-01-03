#!/bin/bash
#
# Chnubber-Shop Restore Script
# Restores PostgreSQL database from backup
# Usage: ./scripts/restore.sh <backup_file.sql.gz>
#

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if backup file is provided
if [ -z "$1" ]; then
    echo -e "${RED}Error: No backup file specified${NC}"
    echo ""
    echo "Usage: $0 <backup_file.sql.gz>"
    echo ""
    echo "Available backups:"
    ls -lh /opt/shoppinglist/backups/db_*.sql.gz 2>/dev/null || echo "  No backups found"
    exit 1
fi

BACKUP_FILE="$1"

# Check if file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}Error: Backup file not found: $BACKUP_FILE${NC}"
    exit 1
fi

echo -e "${YELLOW}=== Chnubber-Shop Database Restore ===${NC}"
echo "Backup file: $BACKUP_FILE"
echo "Started at: $(date)"
echo ""

# Warning
echo -e "${RED}⚠ WARNING: This will OVERWRITE your current database!${NC}"
echo -e "${RED}⚠ All current data will be LOST!${NC}"
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

echo ""
echo -e "${YELLOW}Stopping application services...${NC}"
docker-compose stop app nginx scheduler queue

echo -e "${YELLOW}Dropping existing database...${NC}"
docker exec chnubber-db psql -U chnubber -c "DROP DATABASE IF EXISTS chnubber;"

echo -e "${YELLOW}Creating fresh database...${NC}"
docker exec chnubber-db psql -U chnubber -c "CREATE DATABASE chnubber;"

echo -e "${YELLOW}Restoring database from backup...${NC}"
gunzip -c "$BACKUP_FILE" | docker exec -i chnubber-db psql -U chnubber chnubber

echo -e "${GREEN}✓ Database restored successfully${NC}"

echo -e "${YELLOW}Starting application services...${NC}"
docker-compose up -d app nginx scheduler queue

echo -e "${YELLOW}Clearing application caches...${NC}"
sleep 3  # Wait for container to start
docker exec chnubber-app php artisan config:clear
docker exec chnubber-app php artisan cache:clear
docker exec chnubber-app php artisan route:clear

echo ""
echo -e "${GREEN}=== Restore Completed ===${NC}"
echo "Database restored from: $BACKUP_FILE"
echo "Completed at: $(date)"
