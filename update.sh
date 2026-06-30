#!/bin/bash

# ===========================================
# APPS KOPKARYAPI UPDATE SCRIPT
# Domain: css.kopkaryapi.id
# ===========================================
# Usage: ./update.sh
# VPS: /var/www/eclean

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

APP_DIR="/var/www/eclean"
cd "$APP_DIR"

# -----------------------------------------------
# LANGKAH 0: git pull dulu, lalu re-exec script
# yang baru agar semua langkah berikutnya pakai
# versi terbaru update.sh dari repo.
# -----------------------------------------------
if [[ "${1:-}" != "--updated" ]]; then
    echo -e "${YELLOW}► Pull kode terbaru...${NC}"
    git pull origin main
    echo -e "${YELLOW}► Re-exec script versi baru...${NC}"
    exec bash "$0" --updated
fi

# -----------------------------------------------
# Script di bawah ini berjalan dari versi BARU
# -----------------------------------------------

# Auto-detect PHP-FPM
if systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
    PHP_FPM="php8.2-fpm"
elif systemctl is-active --quiet php8.3-fpm 2>/dev/null; then
    PHP_FPM="php8.3-fpm"
else
    PHP_FPM="php8.2-fpm"
fi

echo -e "${GREEN}=============================================${NC}"
echo -e "${GREEN}   APPS KOPKARYAPI UPDATE                    ${NC}"
echo -e "${GREEN}   css.kopkaryapi.id · FPM: ${PHP_FPM}       ${NC}"
echo -e "${GREEN}=============================================${NC}"

# 1. Maintenance mode
echo -e "\n${YELLOW}[1/9] Maintenance mode ON...${NC}"
php artisan down --refresh=15 --retry=60 || true

# 2. Sync VAPID keys ke .env
echo -e "${YELLOW}[2/9] Sync VAPID keys ke .env...${NC}"
VAPID_PUBLIC="BEbuPxGS2smxeS2oP5w2uhYez3dB9E90XMImuo_pMQ7j4QRg9Gh_Ffm3enMtEgDVUu6u3YA39XIEXcxBWtHgow0"
VAPID_PRIVATE="tBrY7f7c9XdQ1UTjcy5xjsRYJSYioR0uU451O4sx4jE"
VAPID_SUBJECT="mailto:admin@kopkaryapi.id"
grep -q "^VAPID_PUBLIC_KEY=" .env \
    && sed -i "s|^VAPID_PUBLIC_KEY=.*|VAPID_PUBLIC_KEY=${VAPID_PUBLIC}|" .env \
    || echo "VAPID_PUBLIC_KEY=${VAPID_PUBLIC}" >> .env
grep -q "^VAPID_PRIVATE_KEY=" .env \
    && sed -i "s|^VAPID_PRIVATE_KEY=.*|VAPID_PRIVATE_KEY=${VAPID_PRIVATE}|" .env \
    || echo "VAPID_PRIVATE_KEY=${VAPID_PRIVATE}" >> .env
grep -q "^VAPID_SUBJECT=" .env \
    && sed -i "s|^VAPID_SUBJECT=.*|VAPID_SUBJECT=${VAPID_SUBJECT}|" .env \
    || echo "VAPID_SUBJECT=${VAPID_SUBJECT}" >> .env
echo -e "       VAPID keys OK"

# 3. PHP dependencies
echo -e "${YELLOW}[3/9] PHP dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Build Filament/Vite assets
echo -e "${YELLOW}[4/9] Build Filament/Vite assets...${NC}"
npm ci --production=false
npm run build

# 5. Build Next.js PWA
echo -e "${YELLOW}[5/9] Build Next.js PWA (web/)...${NC}"
# Bump SW version = timestamp build → paksa semua browser hapus cache lama
SW_VERSION="v$(date -u +%Y%m%d%H%M)"
sed -i "s|^const VERSION = .*|const VERSION = \"${SW_VERSION}\";|" web/public/sw.js
echo -e "       SW version bumped → ${SW_VERSION}"
cd web
npm ci --production=false
npm run build
cd "$APP_DIR"

# 6. Sync PWA static files ke public/
echo -e "${YELLOW}[6/9] Sync PWA ke public/...${NC}"
rsync -a --no-owner --no-group --checksum web/out/ public/

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

# 9. Permissions & reload
echo -e "${YELLOW}[9/9] Permission & reload services...${NC}"
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache public/
sudo systemctl reload "$PHP_FPM"
sudo systemctl reload nginx

php artisan up

echo -e "\n${GREEN}=============================================${NC}"
echo -e "${GREEN}   ✓ UPDATE SELESAI!                         ${NC}"
echo -e "${GREEN}=============================================${NC}"
echo -e "   🌐  https://css.kopkaryapi.id/admin"
echo -e "   📱  https://css.kopkaryapi.id/login"
echo -e "${YELLOW}Log: tail -f $APP_DIR/storage/logs/laravel.log${NC}"
