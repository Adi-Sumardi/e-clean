#!/bin/bash

# ===========================================
# E-CLEAN DEPLOYMENT SCRIPT FOR VPS BIZNET GIO
# ===========================================
# Usage: ./deploy.sh [environment]
# Example: ./deploy.sh production
#
# Prerequisites:
# - PHP 8.2+
# - Composer
# - Node.js & NPM
# - MySQL/PostgreSQL
# - Git

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-production}
APP_DIR=$(pwd)

echo -e "${BLUE}=========================================${NC}"
echo -e "${BLUE}   E-CLEAN DEPLOYMENT SCRIPT${NC}"
echo -e "${BLUE}   Environment: ${ENVIRONMENT}${NC}"
echo -e "${BLUE}=========================================${NC}"
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${RED}ERROR: .env file not found!${NC}"
    echo -e "${YELLOW}Please copy .env.production to .env and configure it.${NC}"
    exit 1
fi

# Step 1: Pull latest code
echo -e "${GREEN}[1/10] Pulling latest code...${NC}"
git pull origin main

# Step 2: Install PHP dependencies
echo -e "${GREEN}[2/10] Installing PHP dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

# Step 3: Install Node dependencies and build assets
echo -e "${GREEN}[3/10] Installing Node dependencies...${NC}"
npm ci --production=false

echo -e "${GREEN}[4/10] Building frontend assets...${NC}"
npm run build

# Step 4: Clear all caches
echo -e "${GREEN}[5/10] Clearing caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan event:clear

# Step 5: Run migrations
echo -e "${GREEN}[6/10] Running database migrations...${NC}"
php artisan migrate --force

# Step 6: Seed permissions if needed
echo -e "${GREEN}[7/10] Syncing roles and permissions...${NC}"
php artisan db:seed --class=RolePermissionSeeder --force 2>/dev/null || true

# Step 7: Create storage link
echo -e "${GREEN}[8/10] Creating storage link...${NC}"
php artisan storage:link 2>/dev/null || true

# Step 8: Optimize for production
echo -e "${GREEN}[9/10] Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Step 9: Set permissions
echo -e "${GREEN}[10/10] Setting file permissions...${NC}"
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}   DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo -e "${YELLOW}Post-deployment checklist:${NC}"
echo "  [ ] Verify APP_ENV=production in .env"
echo "  [ ] Verify APP_DEBUG=false in .env"
echo "  [ ] Test the application at your domain"
echo "  [ ] Check logs: tail -f storage/logs/laravel.log"
echo "  [ ] Setup supervisor for queue workers (optional)"
echo ""
echo -e "${BLUE}Queue worker command (run in separate terminal or supervisor):${NC}"
echo "  php artisan queue:work --sleep=3 --tries=3 --max-time=3600"
echo ""
