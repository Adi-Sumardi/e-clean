#!/bin/bash

# ===========================================
# E-CLEAN FRESH DEPLOYMENT SCRIPT
# VPS Biznet Gio - PostgreSQL + Redis
# Domain: eclean.adilabs.id
# ===========================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}   E-CLEAN FRESH DEPLOYMENT SCRIPT      ${NC}"
echo -e "${GREEN}=========================================${NC}"

# Configuration - EDIT THESE VALUES
APP_DIR="/var/www/eclean"
APP_USER="eclean-app"
WEB_USER="www-data"
REPO_URL="https://github.com/Adi-Sumardi/e-clean.git"

# Database Configuration
DB_CONNECTION="pgsql"
DB_HOST="127.0.0.1"
DB_PORT="5432"
DB_DATABASE="ecleaning"
DB_USERNAME="ecleaning"
DB_PASSWORD="B1sm1ll4h@eclean"

# ===========================================
# STEP 1: Clean existing installation
# ===========================================
echo -e "\n${YELLOW}[1/10] Cleaning existing installation...${NC}"
if [ -d "$APP_DIR" ]; then
    sudo rm -rf "$APP_DIR"
    echo -e "${GREEN}✓ Old installation removed${NC}"
else
    echo -e "${GREEN}✓ No existing installation found${NC}"
fi

# ===========================================
# STEP 2: Create directory and clone repo
# ===========================================
echo -e "\n${YELLOW}[2/10] Cloning repository...${NC}"
sudo mkdir -p "$APP_DIR"
sudo chown -R $APP_USER:$APP_USER "$APP_DIR"
cd /var/www
git clone "$REPO_URL" eclean
cd "$APP_DIR"
echo -e "${GREEN}✓ Repository cloned${NC}"

# ===========================================
# STEP 3: Create .env file
# ===========================================
echo -e "\n${YELLOW}[3/10] Creating environment file...${NC}"
cat > .env << EOF
APP_NAME="E-Clean"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://eclean.adilabs.id

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

# ===========================================
# DATABASE (PostgreSQL)
# ===========================================
DB_CONNECTION=${DB_CONNECTION}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

# ===========================================
# CACHE & SESSION (Redis)
# ===========================================
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ===========================================
# FILESYSTEM
# ===========================================
FILESYSTEM_DISK=public
BROADCAST_CONNECTION=log

# ===========================================
# MAIL (Configure as needed)
# ===========================================
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@eclean.adilabs.id"
MAIL_FROM_NAME="\${APP_NAME}"

# ===========================================
# WHATSAPP NOTIFICATION (Fonnte)
# ===========================================
FONNTE_TOKEN=
FONNTE_DEVICE=

# ===========================================
# LOGGING
# ===========================================
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

# ===========================================
# VITE
# ===========================================
VITE_APP_NAME="\${APP_NAME}"
EOF
echo -e "${GREEN}✓ Environment file created${NC}"

# ===========================================
# STEP 4: Install PHP dependencies
# ===========================================
echo -e "\n${YELLOW}[4/10] Installing PHP dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✓ PHP dependencies installed${NC}"

# ===========================================
# STEP 5: Install Node dependencies & build
# ===========================================
echo -e "\n${YELLOW}[5/10] Building frontend assets...${NC}"
npm ci
npm run build
echo -e "${GREEN}✓ Frontend assets built${NC}"

# ===========================================
# STEP 6: Generate app key & storage link
# ===========================================
echo -e "\n${YELLOW}[6/10] Generating app key and storage link...${NC}"
php artisan key:generate --force
php artisan storage:link
echo -e "${GREEN}✓ App key generated and storage linked${NC}"

# ===========================================
# STEP 7: Run migrations and seeders
# ===========================================
echo -e "\n${YELLOW}[7/10] Running migrations and seeders...${NC}"
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
echo -e "${GREEN}✓ Database migrated and seeded${NC}"

# ===========================================
# STEP 8: Optimize application
# ===========================================
echo -e "\n${YELLOW}[8/10] Optimizing application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo -e "${GREEN}✓ Application optimized${NC}"

# ===========================================
# STEP 9: Set permissions
# ===========================================
echo -e "\n${YELLOW}[9/10] Setting permissions...${NC}"
sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R $APP_USER:$APP_USER .
sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache public/build
echo -e "${GREEN}✓ Permissions set${NC}"

# ===========================================
# STEP 10: Restart services
# ===========================================
echo -e "\n${YELLOW}[10/10] Restarting services...${NC}"
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
