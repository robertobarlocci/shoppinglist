#!/bin/bash
#
# Chnubber-Shop Backup Script
# Backs up PostgreSQL database, .env file, and storage volume
# Usage: ./scripts/backup.sh
#

set -e  # Exit on error

# Configuration
APP_DIR="/opt/shoppinglist"
BACKUP_DIR="$APP_DIR/backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Chnubber-Shop Backup ===${NC}"
echo "Started at: $(date)"
echo ""

# Create backup directory
mkdir -p "$BACKUP_DIR"

# 1. Backup PostgreSQL Database
echo -e "${YELLOW}[1/3] Backing up PostgreSQL database...${NC}"
if docker exec chnubber-db pg_dump -U chnubber chnubber | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"; then
    DB_SIZE=$(du -h "$BACKUP_DIR/db_$DATE.sql.gz" | cut -f1)
    echo -e "${GREEN}✓ Database backup completed: db_$DATE.sql.gz ($DB_SIZE)${NC}"
else
    echo -e "${RED}✗ Database backup failed!${NC}"
    exit 1
fi

# 2. Backup .env file
echo -e "${YELLOW}[2/3] Backing up .env file...${NC}"
if [ -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env" "$BACKUP_DIR/env_$DATE.backup"
    echo -e "${GREEN}✓ .env backup completed: env_$DATE.backup${NC}"
else
    echo -e "${YELLOW}⚠ .env file not found at $APP_DIR/.env${NC}"
fi

# 3. Backup storage volume (optional, uncomment if needed)
# echo -e "${YELLOW}[3/3] Backing up storage volume...${NC}"
# docker run --rm \
#   -v shoppinglist_storage-data:/storage \
#   -v "$BACKUP_DIR:/backup" \
#   alpine tar czf "/backup/storage_$DATE.tar.gz" -C /storage .
# echo -e "${GREEN}✓ Storage backup completed: storage_$DATE.tar.gz${NC}"

# 4. Clean old backups (keep last N days)
echo -e "${YELLOW}[3/3] Cleaning old backups (keeping last $RETENTION_DAYS days)...${NC}"
find "$BACKUP_DIR" -name "db_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "env_*.backup" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "storage_*.tar.gz" -mtime +$RETENTION_DAYS -delete 2>/dev/null || true
echo -e "${GREEN}✓ Cleanup completed${NC}"

# Summary
echo ""
echo -e "${GREEN}=== Backup Summary ===${NC}"
echo "Backup location: $BACKUP_DIR"
echo "Database: db_$DATE.sql.gz"
echo ".env file: env_$DATE.backup"
echo ""
echo "Total backups:"
ls -lh "$BACKUP_DIR" | grep -E "db_.*\.sql\.gz" | wc -l | xargs echo "  - Database:"
ls -lh "$BACKUP_DIR" | grep -E "env_.*\.backup" | wc -l | xargs echo "  - .env files:"
echo ""
echo -e "${GREEN}Backup completed successfully at: $(date)${NC}"
