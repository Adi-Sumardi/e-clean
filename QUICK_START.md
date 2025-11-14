# 🚀 Quick Start Guide - E-Clean

Panduan cepat untuk menjalankan aplikasi E-Clean (Backend Laravel + Frontend Flutter)

---

## 📋 Prerequisites

### Backend Requirements:
- PHP >= 8.2
- Composer
- MySQL / SQLite
- Node.js & NPM (untuk Filament assets)

### Frontend Requirements:
- Flutter SDK >= 3.32.4
- Dart >= 3.8.1
- Android Studio / Xcode
- Android SDK / iOS SDK

---

## ⚡ Backend Setup (5 menit)

### 1. Masuk ke folder Backend
```bash
cd Backend
```

### 2. Install dependencies
```bash
composer install
npm install
```

### 3. Setup database (sudah menggunakan SQLite)
Database SQLite sudah siap digunakan. File database ada di `database/database.sqlite`.

Jika ingin menggunakan MySQL, update file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e_clean
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Run migrations & seeders
```bash
php artisan migrate --seed
```

Output yang diharapkan:
- Membuat semua tabel
- Membuat roles & permissions
- Membuat users default (admin, supervisor, petugas)
- Membuat data dummy (lokasi, jadwal, reports)

### 5. Create storage link
```bash
php artisan storage:link
```

### 6. Build assets (optional)
```bash
npm run build
```

### 7. Start development server
```bash
php artisan serve
```

### 8. Test API & Admin Panel

**API Testing:**
```bash
# Test API endpoint
curl http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

**Admin Panel:**
- URL: http://localhost:8000/admin
- Email: admin@example.com
- Password: password

**Default Users:**
| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Supervisor | supervisor@example.com | password |
| Petugas | petugas1@example.com | password |

---

## 📱 Frontend Setup (3 menit)

### 1. Masuk ke folder Frontend
```bash
cd Frontend
```

### 2. Install dependencies
```bash
flutter pub get
```

### 3. Run the app
```bash
# List available devices
flutter devices

# Run on specific device
flutter run -d <device-id>

# Or just run (will prompt device selection)
flutter run
```

### 4. Test on emulator/simulator

**Android Emulator:**
```bash
# Start emulator
emulator @Pixel_4_API_30

# Run app
flutter run
```

**iOS Simulator (Mac only):**
```bash
# List simulators
xcrun simctl list devices

# Open simulator
open -a Simulator

# Run app
flutter run
```

---

## 🧪 Testing

### Backend Testing
```bash
cd Backend

# Run all tests
php artisan test

# Run specific test
php artisan test --filter=AuthControllerTest
```

### Frontend Testing
```bash
cd Frontend

# Run all tests
flutter test

# Run with coverage
flutter test --coverage
```

---

## 📊 Accessing Features

### Backend (API)

**Base URL:** `http://localhost:8000/api/v1`

**Authentication:**
```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# Returns token - use it in subsequent requests:
# Authorization: Bearer YOUR_TOKEN_HERE
```

**Example API Calls:**
```bash
TOKEN="your-token-here"

# Get current user
curl http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer $TOKEN"

# Get dashboard
curl http://localhost:8000/api/v1/dashboard \
  -H "Authorization: Bearer $TOKEN"

# Get today's schedules
curl http://localhost:8000/api/v1/jadwal/today \
  -H "Authorization: Bearer $TOKEN"
```

### Backend (Admin Panel)

1. Go to: http://localhost:8000/admin
2. Login with admin credentials
3. Explore:
   - Dashboard & Statistics
   - Manage Users
   - Manage Locations
   - Manage Schedules
   - View Activity Reports
   - View Attendance
   - View Evaluations
   - Leaderboard

---

## 🔧 Troubleshooting

### Backend Issues

**Problem: "Class not found" errors**
```bash
composer dump-autoload
php artisan optimize:clear
```

**Problem: "Storage link missing"**
```bash
php artisan storage:link
```

**Problem: "Permission denied" on storage**
```bash
chmod -R 775 storage bootstrap/cache
```

**Problem: "Database locked" (SQLite)**
```bash
chmod 664 database/database.sqlite
chmod 775 database
```

### Frontend Issues

**Problem: "flutter: command not found"**
```bash
# Install Flutter from: https://flutter.dev/docs/get-started/install
# Add to PATH
export PATH="$PATH:/path/to/flutter/bin"
```

**Problem: "No devices found"**
```bash
# Android
flutter doctor --android-licenses

# iOS (Mac only)
sudo xcode-select --switch /Applications/Xcode.app/Contents/Developer
sudo xcodebuild -runFirstLaunch
```

**Problem: "Gradle build failed"**
```bash
cd android
./gradlew clean
cd ..
flutter clean
flutter pub get
```

---

## 📝 Next Steps

### Backend Development:
1. Review API documentation: `Backend/API_DOCUMENTATION.md`
2. Customize models & migrations
3. Add custom business logic in Services
4. Configure WhatsApp notifications (Fonnte)
5. Deploy to production

### Frontend Development:
1. Configure API base URL
2. Implement authentication flow
3. Build main screens (Dashboard, Check-in, Reports)
4. Add offline support
5. Implement push notifications
6. Build & release APK/IPA

---

## 🎯 Common Tasks

### Reset Database
```bash
cd Backend
php artisan migrate:fresh --seed
```

### Clear All Caches
```bash
cd Backend
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

### Generate Fake Data
```bash
cd Backend
php artisan db:seed --class=DummyDataSeeder
```

### Export API Collection for Postman
See: `Backend/API_DOCUMENTATION.md` for all endpoints and examples

### Build Flutter Release
```bash
cd Frontend

# Android APK
flutter build apk --release

# Android App Bundle
flutter build appbundle --release

# iOS (Mac only)
flutter build ios --release
```

---

## 📚 Documentation

- **Backend API:** [Backend/API_DOCUMENTATION.md](Backend/API_DOCUMENTATION.md)
- **Project README:** [README.md](README.md)
- **Laravel Docs:** https://laravel.com/docs/11.x
- **Flutter Docs:** https://docs.flutter.dev
- **Filament Docs:** https://filamentphp.com/docs

---

## 💡 Pro Tips

1. **Use SQLite for development** - Sudah di-configure by default
2. **Test API dengan Postman** - Import endpoints from documentation
3. **Enable hot reload di Flutter** - Press 'r' saat app running
4. **Monitor logs:**
   - Backend: `tail -f Backend/storage/logs/laravel.log`
   - Frontend: Check console output saat `flutter run`
5. **Use Filament admin panel** untuk quick data management

---

## 🆘 Need Help?

- Check documentation files in Backend/ folder
- Review error logs
- Test API endpoints with Postman
- Check Laravel & Flutter official docs

---

**Happy Coding! 🚀**
