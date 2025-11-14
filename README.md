# E-Cleaning Service Management System

Aplikasi monitoring dan pelaporan aktivitas cleaning service di sekolah secara real-time.

## Tech Stack

- **Backend:** Laravel 12
- **Admin Panel:** Filament 4
- **Database:** SQLite (Development) / PostgreSQL (Production)
- **Cache/Queue:** Database (Development) / Redis (Production)
- **Role & Permission:** Filament Shield + Spatie Laravel Permission
- **Authentication:** Filament Auth + Laravel Sanctum

## Installation

### Prerequisites

- PHP 8.2+
- Composer 2.8+
- Node.js & NPM (for assets compilation)

### Setup Steps

1. **Clone repository**
   ```bash
   cd /Users/yapi/Adi/App-Dev/e-clean
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   **Configure Fonnte (Optional - for WhatsApp notifications):**
   - Register at [https://fonnte.com](https://fonnte.com)
   - Get your API token from dashboard
   - Update `.env` file:
     ```env
     FONNTE_TOKEN=your_actual_token_here
     ```

4. **Run migrations and seeders**
   ```bash
   php artisan migrate:fresh
   php artisan db:seed --class=AdminUserSeeder
   php artisan db:seed --class=RolePermissionSeeder
   ```

6. **Link storage**
   ```bash
   php artisan storage:link
   ```

7. **Start development server**
   ```bash
   php artisan serve
   ```

8. **Access admin panel**
   ```
   URL: http://localhost:8000/admin
   Email: admin@ecleaning.test
   Password: password
   ```

## Project Structure

```
├── app/
│   ├── Filament/
│   │   ├── Resources/          # CRUD Resources
│   │   ├── Pages/              # Custom Pages
│   │   └── Widgets/            # Dashboard Widgets
│   ├── Models/                 # Eloquent Models
│   ├── Policies/               # Authorization Policies
│   └── Services/               # Business Logic Services
├── database/
│   ├── migrations/             # Database Migrations
│   └── seeders/                # Database Seeders
├── resources/
│   └── views/                  # Blade Templates
└── routes/
    └── web.php                 # Web Routes
```

## Installed Packages

### Core
- `laravel/framework:^12.0` - Laravel Framework
- `filament/filament:^4.0` - Filament Admin Panel

### Authentication & Authorization
- `bezhansalleh/filament-shield:^4.0` - Role & Permission Management
- `spatie/laravel-permission:^6.0` - Permission System

### Data Visualization & Charts
- `flowframe/laravel-trend:^0.4` - Trend data for charts

### Image Processing
- `intervention/image-laravel:^1.5` - Image manipulation and WebP compression

### QR Code
- `simplesoftwareio/simple-qrcode:^4.2` - QR Code generation

### Export & Reporting
- `barryvdh/laravel-dompdf:^3.1` - PDF generation
- `maatwebsite/excel:^3.1` - Excel export

### Utilities
- `livewire/livewire:^3.0` - Frontend Reactivity

## Next Steps

Refer to [design.md](design.md) for complete application design and features specification.

### Development Roadmap

- [x] Phase 1: Database Migrations (Users, Lokasi, Jadwal, etc.) ✅
- [x] Phase 2: Filament Resources (CRUD for all entities) ✅
- [x] Phase 3: Role & Permission Setup (Super Admin, Admin, Supervisor, Pengurus, Petugas) ✅
- [x] Phase 4: Dashboard Widgets & Charts ✅
- [x] Phase 5: Image Upload & Compression (Intervention Image) ✅
- [x] Phase 6: QR Code Generation & Scanning ✅
- [x] Phase 7: WhatsApp Notifications (Fonnte) ✅
- [x] Phase 8: GPS Integration ✅
- [x] Phase 9: Export Features (PDF/Excel) ✅
- [ ] Phase 10: Testing & Deployment 🚀 Next

## Development Notes

### Database

Currently using SQLite for development. To switch to PostgreSQL for production:

1. Update `.env`:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=ecleaning
   DB_USERNAME=postgres
   DB_PASSWORD=your_password
   ```

2. Create database:
   ```bash
   createdb ecleaning
   ```

3. Run migrations:
   ```bash
   php artisan migrate:fresh --seed
   ```

### Cache & Queue

Currently using database for cache and queue. To switch to Redis:

1. Install Redis extension:
   ```bash
   pecl install redis
   ```

2. Update `.env`:
   ```env
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   ```

3. Restart PHP/Server

## License

Proprietary - E-Cleaning Service Management System

## Support

For issues and questions, contact the development team.
