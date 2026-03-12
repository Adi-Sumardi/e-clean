#!/bin/bash

# ===========================================
# E-CLEAN UPDATE SCRIPT
# ===========================================
# Usage: ./update.sh
# Untuk update production setelah git push
# VPS: /var/www/eclean

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

APP_DIR="/var/www/eclean"

echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}   E-CLEAN UPDATE                       ${NC}"
echo -e "${GREEN}=========================================${NC}"

cd "$APP_DIR"

# 1. Maintenance mode
echo -e "\n${YELLOW}[1/8] Enabling maintenance mode...${NC}"
php artisan down --refresh=15 --retry=60 || true

# 2. Pull latest
echo -e "${YELLOW}[2/8] Pulling latest code...${NC}"
git pull origin main

# 3. Composer
echo -e "${YELLOW}[3/8] Installing PHP dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Build frontend
echo -e "${YELLOW}[4/8] Building frontend assets...${NC}"
npm ci --production=false
npm run build

# 5. Migrate
echo -e "${YELLOW}[5/8] Running migrations...${NC}"
php artisan migrate --force

# 6. Clear & optimize
echo -e "${YELLOW}[6/8] Clearing & optimizing caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan event:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 7. Permissions & restart
echo -e "${YELLOW}[7/8] Setting permissions & restarting services...${NC}"
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache public/build
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx

# 8. Disable maintenance mode
echo -e "${YELLOW}[8/8] Disabling maintenance mode...${NC}"
php artisan up

echo -e "\n${GREEN}=========================================${NC}"
echo -e "${GREEN}   UPDATE SELESAI!                       ${NC}"
echo -e "${GREEN}=========================================${NC}"
echo -e "${YELLOW}Cek logs: tail -f $APP_DIR/storage/logs/laravel.log${NC}"
