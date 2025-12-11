#!/bin/bash

# ===========================================
# E-CLEAN CONTINUE DEPLOYMENT SCRIPT
# Continue from step 5 after Node.js upgrade
# ===========================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}   E-CLEAN CONTINUE DEPLOYMENT          ${NC}"
echo -e "${GREEN}   (Continuing from step 5)             ${NC}"
echo -e "${GREEN}=========================================${NC}"

# Configuration
APP_DIR="/var/www/eclean"
APP_USER="eclean-app"
WEB_USER="www-data"

cd "$APP_DIR"

# ===========================================
# STEP 0: Upgrade Node.js to 18 LTS
# ===========================================
echo -e "\n${YELLOW}[0/6] Checking/Upgrading Node.js...${NC}"
NODE_VERSION=$(node -v 2>/dev/null | cut -d'v' -f2 | cut -d'.' -f1)
if [ -z "$NODE_VERSION" ] || [ "$NODE_VERSION" -lt 18 ]; then
    echo -e "${YELLOW}Installing Node.js 18 LTS...${NC}"
    curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
    sudo apt install -y nodejs
    echo -e "${GREEN}✓ Node.js $(node -v) installed${NC}"
else
    echo -e "${GREEN}✓ Node.js v$NODE_VERSION already OK${NC}"
fi

# ===========================================
# STEP 1: Install Node dependencies & build
# ===========================================
echo -e "\n${YELLOW}[1/6] Building frontend assets...${NC}"
npm ci
npm run build
echo -e "${GREEN}✓ Frontend assets built${NC}"

# ===========================================
# STEP 2: Generate app key & storage link
# ===========================================
echo -e "\n${YELLOW}[2/6] Generating app key and storage link...${NC}"
php artisan key:generate --force
php artisan storage:link --force 2>/dev/null || true
echo -e "${GREEN}✓ App key generated and storage linked${NC}"

# ===========================================
# STEP 3: Run migrations and seeders
# ===========================================
echo -e "\n${YELLOW}[3/6] Running migrations and seeders...${NC}"
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
echo -e "${GREEN}✓ Database migrated and seeded${NC}"

# ===========================================
# STEP 4: Optimize application
# ===========================================
echo -e "\n${YELLOW}[4/6] Optimizing application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo -e "${GREEN}✓ Application optimized${NC}"

# ===========================================
# STEP 5: Set permissions
# ===========================================
echo -e "\n${YELLOW}[5/6] Setting permissions...${NC}"
sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R $APP_USER:$APP_USER .
sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache public/build
echo -e "${GREEN}✓ Permissions set${NC}"

# ===========================================
# STEP 6: Restart services
# ===========================================
echo -e "\n${YELLOW}[6/6] Restarting services...${NC}"
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo systemctl restart redis
echo -e "${GREEN}✓ Services restarted${NC}"

# ===========================================
# DONE!
# ===========================================
echo -e "\n${GREEN}=========================================${NC}"
echo -e "${GREEN}   DEPLOYMENT COMPLETED SUCCESSFULLY!   ${NC}"
echo -e "${GREEN}=========================================${NC}"
echo -e ""
echo -e "Next steps:"
echo -e "1. Create admin user:"
echo -e "   ${YELLOW}cd $APP_DIR && php artisan make:filament-user${NC}"
echo -e ""
echo -e "2. Access the application:"
echo -e "   ${YELLOW}https://eclean.adilabs.id/admin${NC}"
echo -e ""
echo -e "3. Check logs if any issues:"
echo -e "   ${YELLOW}tail -f $APP_DIR/storage/logs/laravel.log${NC}"
echo -e ""
