#!/bin/bash

# ===========================================
# E-CLEAN DEPLOYMENT SCRIPT
# Domain: css.kopkaryapi.id
# ===========================================
# Jalankan dari direktori project di VPS:
#   cd /var/www/eclean && ./deploy.sh
#
# Script ini menangani FULL deployment:
#   Laravel (PHP) + Filament (Vite) + Next.js PWA (static export)
# ===========================================

set -e

# --- Warna output ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

APP_DIR=$(pwd)
DOMAIN="css.kopkaryapi.id"

# Auto-detect PHP-FPM version (sesuai nginx config)
if systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
    PHP_FPM="php8.2-fpm"
elif systemctl is-active --quiet php8.3-fpm 2>/dev/null; then
    PHP_FPM="php8.3-fpm"
else
    PHP_FPM="php8.2-fpm"
fi

echo -e "${BLUE}=============================================${NC}"
echo -e "${BLUE}   E-CLEAN DEPLOYMENT — ${DOMAIN}${NC}"
echo -e "${BLUE}   Dir : ${APP_DIR}${NC}"
echo -e "${BLUE}   FPM : ${PHP_FPM}${NC}"
echo -e "${BLUE}=============================================${NC}"
echo ""

# Guard: harus ada .env
if [ ! -f .env ]; then
    echo -e "${RED}ERROR: .env tidak ditemukan!${NC}"
    echo -e "${YELLOW}Salin .env.production ke .env lalu isi nilainya.${NC}"
    exit 1
fi

# Guard: jangan jalankan jika APP_DEBUG=true di production
if grep -q "APP_DEBUG=true" .env; then
    echo -e "${YELLOW}⚠  PERINGATAN: APP_DEBUG=true — pastikan sudah benar sebelum melanjutkan.${NC}"
fi

# -----------------------------------------------
echo -e "${GREEN}[1/12] Pull kode terbaru dari git...${NC}"
git pull origin main

# -----------------------------------------------
echo -e "${GREEN}[2/12] Install dependensi PHP (composer)...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

# -----------------------------------------------
echo -e "${GREEN}[3/12] Build aset Filament/Vite (root)...${NC}"
npm ci --production=false
npm run build

# -----------------------------------------------
echo -e "${GREEN}[4/12] Build Next.js PWA (web/)...${NC}"
# NEXT_PUBLIC_BACKEND_ORIGIN harus KOSONG di production (same-origin)
# web/.env.local tidak ada di server (gitignored) — aman
cd web
npm ci --production=false
npm run build        # output: web/out/
cd "$APP_DIR"

# -----------------------------------------------
echo -e "${GREEN}[5/12] Salin PWA static files ke public/...${NC}"
# rsync: add/update saja, tidak hapus file Laravel yang sudah ada
# index.php tidak akan tertimpa karena Next.js output index.html (beda nama)
rsync -a --checksum web/out/ public/
echo -e "       PWA static files disinkron ke public/"

# -----------------------------------------------
echo -e "${GREEN}[6/12] Bersihkan cache Laravel...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan event:clear

# -----------------------------------------------
echo -e "${GREEN}[7/12] Jalankan migrasi database...${NC}"
php artisan migrate --force

# -----------------------------------------------
echo -e "${GREEN}[8/12] Sinkron role & permission...${NC}"
php artisan db:seed --class=RolePermissionSeeder --force 2>/dev/null || true

# -----------------------------------------------
echo -e "${GREEN}[9/12] Buat storage link...${NC}"
php artisan storage:link 2>/dev/null || true

# -----------------------------------------------
echo -e "${GREEN}[10/12] Optimasi Laravel untuk production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# -----------------------------------------------
echo -e "${GREEN}[11/12] Set permission file...${NC}"
sudo chown -R www-data:www-data storage bootstrap/cache public/
sudo chmod -R 775 storage bootstrap/cache

# -----------------------------------------------
echo -e "${GREEN}[12/12] Restart layanan (nginx + PHP-FPM)...${NC}"
sudo systemctl reload "$PHP_FPM"
sudo systemctl reload nginx

# -----------------------------------------------
echo ""
echo -e "${GREEN}=============================================${NC}"
echo -e "${GREEN}   ✓ DEPLOYMENT SELESAI!${NC}"
echo -e "${GREEN}=============================================${NC}"
echo ""
echo -e "   🌐  https://${DOMAIN}/admin    → Filament (admin)"
echo -e "   📱  https://${DOMAIN}/login    → PWA petugas/supervisor"
echo -e "   🏠  https://${DOMAIN}/beranda  → Dashboard PWA"
echo ""
echo -e "${YELLOW}Checklist post-deploy:${NC}"
echo "  [ ] Buka https://${DOMAIN}/login — cek login email+password"
echo "  [ ] Coba login Google (pastikan redirect URI sudah di Google Console)"
echo "  [ ] Buka https://${DOMAIN}/admin  — cek Filament admin"
echo "  [ ] Pantau log: tail -f storage/logs/laravel.log"
echo ""
echo -e "${BLUE}Queue worker (jalankan via Supervisor atau screen):${NC}"
echo "  php artisan queue:work --sleep=3 --tries=3 --max-time=3600"
echo ""
