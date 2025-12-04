# E-Clean Deployment Guide

## Domain: eclean.adilabs.id

Panduan lengkap deploy E-Clean ke VPS Biznet Gio dengan Nginx.

---

## Prerequisites

- VPS dengan Ubuntu 22.04/24.04
- PHP 8.2+
- MySQL 8.0 atau PostgreSQL 15+
- Nginx
- Node.js 18+ & NPM
- Composer 2.x
- Git
- Certbot (untuk SSL)

---

## Step 1: Setup Server

### Install dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
    php8.2-pgsql php8.2-xml php8.2-curl php8.2-gd php8.2-mbstring \
    php8.2-zip php8.2-bcmath php8.2-intl php8.2-readline

# Install Nginx
sudo apt install -y nginx

# Install MySQL
sudo apt install -y mysql-server

# Install Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Certbot
sudo apt install -y certbot python3-certbot-nginx
```

---

## Step 2: Setup DNS di Hostinger

1. Login ke Hostinger panel
2. Pilih domain `adilabs.id`
3. Buka **DNS Zone**
4. Tambahkan A Record:
   - **Type**: A
   - **Name**: eclean
   - **Points to**: `<IP VPS Biznet Gio>`
   - **TTL**: 14400

5. Tunggu propagasi DNS (5-30 menit)

---

## Step 3: Setup Database

```bash
# Login MySQL
sudo mysql

# Buat database dan user
CREATE DATABASE e_clean CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'eclean_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON e_clean.* TO 'eclean_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Step 4: Clone & Configure Application

```bash
# Buat directory
sudo mkdir -p /var/www/eclean
sudo chown -R $USER:$USER /var/www/eclean

# Clone repository
cd /var/www
git clone https://github.com/Adi-Sumardi/e-clean.git eclean
cd eclean

# Copy environment file
cp .env.production .env

# Edit .env dengan konfigurasi server
nano .env
```

### Konfigurasi `.env` yang perlu diubah:

```env
APP_KEY=                          # Generate dengan: php artisan key:generate
DB_DATABASE=e_clean
DB_USERNAME=eclean_user
DB_PASSWORD=your_secure_password
FONNTE_TOKEN=your_fonnte_token    # Dari fonnte.com
```

---

## Step 5: Run Deployment Script

```bash
# Set permission
chmod +x deploy.sh

# Jalankan deployment
./deploy.sh
```

Atau manual:

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies & build
npm ci
npm run build

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed roles & permissions
php artisan db:seed --class=RolePermissionSeeder --force

# Create storage link
php artisan storage:link

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## Step 6: Setup Nginx

```bash
# Copy nginx config
sudo cp nginx/eclean.adilabs.id.conf /etc/nginx/sites-available/

# Enable site
sudo ln -s /etc/nginx/sites-available/eclean.adilabs.id.conf /etc/nginx/sites-enabled/

# Test config
sudo nginx -t

# Restart nginx
sudo systemctl restart nginx
```

---

## Step 7: Setup SSL dengan Let's Encrypt

```bash
# Generate SSL certificate
sudo certbot --nginx -d eclean.adilabs.id

# Pilih redirect HTTP to HTTPS (option 2)

# Test auto-renewal
sudo certbot renew --dry-run
```

---

## Step 8: Create Admin User

```bash
cd /var/www/eclean

# Buat super admin
php artisan make:filament-user

# Masukkan:
# - Name: Admin
# - Email: admin@eclean.adilabs.id
# - Password: (password aman)
```

---

## Step 9: Setup Queue Worker (Optional)

Untuk WhatsApp notifications:

```bash
# Install Supervisor
sudo apt install -y supervisor

# Buat config
sudo nano /etc/supervisor/conf.d/eclean-worker.conf
```

```ini
[program:eclean-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/eclean/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/eclean/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start eclean-worker:*
```

---

## Step 10: Verify Deployment

1. Buka `https://eclean.adilabs.id`
2. Login ke admin panel: `https://eclean.adilabs.id/admin`
3. Cek logs jika ada error:
   ```bash
   tail -f /var/www/eclean/storage/logs/laravel.log
   ```

---

## Troubleshooting

### 502 Bad Gateway
```bash
# Check PHP-FPM
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm
```

### Permission denied
```bash
sudo chown -R www-data:www-data /var/www/eclean/storage
sudo chmod -R 775 /var/www/eclean/storage
```

### SSL Error
```bash
# Regenerate certificate
sudo certbot --nginx -d eclean.adilabs.id --force-renewal
```

### Clear all caches
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

---

## Update Deployment

Untuk update aplikasi:

```bash
cd /var/www/eclean
./deploy.sh
```

---

## Security Checklist

- [ ] `APP_DEBUG=false` di .env
- [ ] `APP_ENV=production` di .env
- [ ] Database password yang kuat
- [ ] Firewall aktif (UFW)
- [ ] SSL certificate aktif
- [ ] Regular backup database
