#!/bin/bash

# ===========================================
# E-CLEAN UPDATE SCRIPT
# Domain: css.kopkaryapi.id
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

# Auto-detect PHP-FPM (sesuai nginx config)
if systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
    PHP_FPM="php8.2-fpm"
elif systemctl is-active --quiet php8.3-fpm 2>/dev/null; then
    PHP_FPM="php8.3-fpm"
else
    PHP_FPM="php8.2-fpm"
fi

echo -e "${GREEN}=============================================${NC}"
echo -e "${GREEN}   E-CLEAN UPDATE — css.kopkaryapi.id        ${NC}"
echo -e "${GREEN}   FPM: ${PHP_FPM}                           ${NC}"
echo -e "${GREEN}=============================================${NC}"

cd "$APP_DIR"

# 1. Maintenance mode
echo -e "\n${YELLOW}[1/9] Maintenance mode ON...${NC}"
php artisan down --refresh=15 --retry=60 || true

# 2. Pull latest
echo -e "${YELLOW}[2/9] Pull kode terbaru...${NC}"
git pull origin main

# 3. Composer
echo -e "${YELLOW}[3/9] PHP dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Build Filament/Vite assets (root)
echo -e "${YELLOW}[4/9] Build Filament/Vite assets...${NC}"
npm ci --production=false
npm run build

# 5. Build Next.js PWA
echo -e "${YELLOW}[5/9] Build Next.js PWA (web/)...${NC}"
cd web
npm ci --production=false
npm run build
cd "$APP_DIR"

# 6. Sync PWA static files ke public/
echo -e "${YELLOW}[6/9] Sync PWA static files ke public/...${NC}"
rsync -a --checksum web/out/ public/

# 7. Migrate
echo -e "${YELLOW}[7/9] Migrasi database...${NC}"
php artisan migrate --force

# 8. Clear & optimize
echo -e "${YELLOW}[8/9] Clear cache & optimize...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan event:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 9. Permissions & reload services
echo -e "${YELLOW}[9/9] Permission & reload services...${NC}"
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache public/
sudo systemctl reload "$PHP_FPM"
sudo systemctl reload nginx

# Done — matikan maintenance mode
php artisan up

echo -e "\n${GREEN}=============================================${NC}"
echo -e "${GREEN}   ✓ UPDATE SELESAI!                         ${NC}"
echo -e "${GREEN}=============================================${NC}"
echo -e "   🌐  https://css.kopkaryapi.id/admin"
echo -e "   📱  https://css.kopkaryapi.id/login"
echo -e "${YELLOW}Log: tail -f $APP_DIR/storage/logs/laravel.log${NC}"
