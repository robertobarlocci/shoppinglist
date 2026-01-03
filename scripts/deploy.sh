#!/bin/bash
#
# Chnubber-Shop Deployment Script
# Safe deployment that preserves data
# Usage: ./scripts/deploy.sh
#

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Chnubber-Shop Deployment Script     ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
echo ""

# 1. Pre-deployment backup
echo -e "${YELLOW}[1/8] Creating backup before deployment...${NC}"
if ./scripts/backup.sh; then
    echo -e "${GREEN}✓ Backup completed${NC}"
else
    echo -e "${RED}✗ Backup failed! Deployment aborted.${NC}"
    exit 1
fi
echo ""

# 2. Pull latest code
echo -e "${YELLOW}[2/8] Pulling latest code from Git...${NC}"
if git pull origin main; then
    echo -e "${GREEN}✓ Code updated${NC}"
else
    echo -e "${RED}✗ Git pull failed!${NC}"
    exit 1
fi
echo ""

# 3. Stop application (but keep database running)
echo -e "${YELLOW}[3/8] Stopping application services...${NC}"
docker-compose stop app nginx scheduler queue
echo -e "${GREEN}✓ Application stopped (database still running)${NC}"
echo ""

# 4. Rebuild containers
echo -e "${YELLOW}[4/8] Rebuilding Docker containers...${NC}"
docker-compose build --no-cache app scheduler queue
echo -e "${GREEN}✓ Containers rebuilt${NC}"
echo ""

# 5. Start containers
echo -e "${YELLOW}[5/8] Starting containers...${NC}"
docker-compose up -d
echo -e "${GREEN}✓ Containers started${NC}"
echo ""

# Wait for containers to be healthy
echo -e "${YELLOW}Waiting for containers to be healthy...${NC}"
sleep 5

# 6. Install/update dependencies
echo -e "${YELLOW}[6/8] Installing Composer dependencies...${NC}"
docker exec chnubber-app composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✓ Composer dependencies installed${NC}"
echo ""

# 7. Run migrations
echo -e "${YELLOW}[7/8] Running database migrations...${NC}"
docker exec chnubber-app php artisan migrate --force
echo -e "${GREEN}✓ Migrations completed${NC}"
echo ""

# 8. Optimize application
echo -e "${YELLOW}[8/8] Optimizing application...${NC}"
docker exec chnubber-app php artisan config:cache
docker exec chnubber-app php artisan route:cache
docker exec chnubber-app php artisan view:cache
echo -e "${GREEN}✓ Optimization completed${NC}"
echo ""

# Verify deployment
echo -e "${BLUE}=== Deployment Verification ===${NC}"
echo ""
echo "Container status:"
docker-compose ps
echo ""

echo "Database connection test:"
if docker exec chnubber-app php artisan tinker --execute="echo 'DB connection OK: ' . DB::connection()->getPdo() ? 'YES' : 'NO';" 2>/dev/null; then
    echo -e "${GREEN}✓ Database connection successful${NC}"
else
    echo -e "${RED}✗ Database connection failed${NC}"
fi
echo ""

echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   Deployment Completed Successfully!  ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════╝${NC}"
echo ""
echo "Your application is now running with the latest code."
echo "All data has been preserved in Docker volumes."
echo ""
echo "Next steps:"
echo "  - Test your application: http://localhost:8585"
echo "  - Check logs: docker-compose logs -f app"
echo "  - Monitor resources: docker stats"
