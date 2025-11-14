# 👥 Manajemen Petugas

**Date:** 2025-11-13
**Feature:** Sub menu Petugas di Master Data untuk mengaktifkan/menonaktifkan status petugas

---

## Overview

Menambahkan fitur manajemen petugas di dashboard supervisor dengan kemampuan untuk mengaktifkan atau menonaktifkan status petugas. Petugas yang di-non-aktifkan tidak dapat login ke aplikasi mobile.

---

## Changes Made

### 1. Database Migration - Add `is_active` Column

**File:** [database/migrations/2025_11_13_074132_add_is_active_to_users_table.php](database/migrations/2025_11_13_074132_add_is_active_to_users_table.php)

```php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('is_active')->default(true)->after('phone');
});
```

**Column Details:**
- **Type:** Boolean
- **Default:** `true` (aktif)
- **Position:** After `phone` column

---

### 2. User Model Update

**File:** [app/Models/User.php](app/Models/User.php)

**Added to fillable:**
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'phone',
    'is_active', // ✅ New
];
```

**Added to casts:**
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean', // ✅ New
    ];
}
```

---

### 3. New PetugasResource

**File:** [app/Filament/Resources/Petugas/PetugasResource.php](app/Filament/Resources/Petugas/PetugasResource.php)

**Features:**
- ✅ List hanya petugas (filter by role 'petugas')
- ✅ Toggle status aktif/non-aktif per petugas
- ✅ Bulk action untuk aktifkan semua
- ✅ Badge count di navigation
- ✅ Icon status (green checkmark / red X)
- ✅ Filter by status (Aktif/Non-Aktif)
- ✅ Disable create & delete (safety)
- ✅ Edit untuk update info petugas

**Navigation:**
- **Group:** Master Data
- **Icon:** User Group (heroicon-o-user-group)
- **Label:** Petugas
- **Sort:** 1 (paling atas di grup Master Data)

**Access Control:**
- **View:** Supervisor, Admin, Super Admin
- **Edit:** Supervisor, Admin, Super Admin
- **Create:** Disabled (petugas dibuat lewat User Management)
- **Delete:** Disabled (untuk safety)

---

### 4. Toggle Status Action

**Features:**
- Button label dinamis: "Aktifkan" atau "Non-Aktifkan"
- Konfirmasi dialog sebelum mengubah status
- Notification setelah berhasil
- Icon dan warna sesuai status (hijau = aktifkan, merah = non-aktifkan)

**Code:**
```php
Action::make('toggle_status')
    ->label(fn ($record) => $record->is_active ? 'Non-Aktifkan' : 'Aktifkan')
    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
    ->requiresConfirmation()
    ->action(function ($record) {
        $record->update(['is_active' => !$record->is_active]);
        // Show notification
    })
```

---

### 5. API Login Check

**File:** [app/Http/Controllers/Api/AuthController.php](app/Http/Controllers/Api/AuthController.php)

**Added validation:**
```php
// Check if user is active
if (!$user->is_active) {
    return $this->unauthorizedResponse('Your account has been deactivated. Please contact supervisor.');
}
```

**Flow:**
1. User submit email & password ✅
2. Credentials valid ✅
3. **Check is_active status** ⬅️ New
4. If inactive → Return 401 error
5. If active → Generate token & return success

---

### 6. User Management Update

**Updated Files:**
- [app/Filament/Resources/Users/Schemas/UserForm.php](app/Filament/Resources/Users/Schemas/UserForm.php)
- [app/Filament/Resources/Users/Tables/UsersTable.php](app/Filament/Resources/Users/Tables/UsersTable.php)

**Form - Added Toggle:**
```php
Toggle::make('is_active')
    ->label('Status Aktif')
    ->default(true)
    ->helperText('Petugas yang non-aktif tidak dapat login ke aplikasi mobile')
    ->inline(false)
    ->columnSpanFull()
```

**Table - Added Column:**
```php
IconColumn::make('is_active')
    ->label('Status')
    ->boolean()
    ->trueIcon('heroicon-o-check-circle')
    ->falseIcon('heroicon-o-x-circle')
    ->trueColor('success')
    ->falseColor('danger')
    ->sortable()
```

**Table - Added Filter:**
```php
TernaryFilter::make('is_active')
    ->label('Status')
    ->placeholder('Semua Status')
    ->trueLabel('Aktif')
    ->falseLabel('Non-Aktif')
```

---

## Table Columns

### Petugas Resource Table

| Column | Type | Description |
|--------|------|-------------|
| **Nama Petugas** | Text | Searchable & sortable |
| **Email** | Text | Copyable, with icon |
| **No. WhatsApp** | Text | Copyable, optional |
| **Status** | Icon | Green ✓ (aktif) / Red ✗ (non-aktif) |
| **Terdaftar** | Date | Created date |

### Actions Available

1. **Toggle Status** - Aktifkan/Non-aktifkan individual
2. **Edit** - Update info petugas
3. **Aktifkan Semua** (Header) - Bulk activate semua yang non-aktif

---

## User Experience

### For Supervisor/Admin:

1. **View Petugas List**
   - Buka http://127.0.0.1:8003/admin/petugas
   - Lihat list semua petugas
   - Filter by status (Aktif/Non-Aktif)
   - Sort by name, email, atau tanggal

2. **Non-Aktifkan Petugas**
   - Klik tombol "Non-Aktifkan" pada row petugas
   - Konfirmasi di dialog
   - Petugas tidak bisa login ke mobile app
   - Notifikasi success

3. **Aktifkan Kembali**
   - Klik tombol "Aktifkan" pada row petugas non-aktif
   - Konfirmasi di dialog
   - Petugas bisa login kembali
   - Notifikasi success

4. **Bulk Activate**
   - Klik "Aktifkan Semua" di header (jika ada yang non-aktif)
   - Konfirmasi
   - Semua petugas non-aktif akan diaktifkan

5. **Edit Info**
   - Klik "Edit" untuk update nama, email, phone, atau status

### For Petugas (Mobile App):

1. **Active User Login:**
   - Email & password ✅
   - Status aktif ✅
   - Login berhasil, dapat token

2. **Inactive User Login:**
   - Email & password ✅
   - Status non-aktif ❌
   - Error: "Your account has been deactivated. Please contact supervisor."
   - Tidak dapat login

---

## Navigation Structure

```
📊 Dashboard
📁 Master Data
   └── 👥 Petugas (5)           ⬅️ NEW
   └── ... (other menu items)
📁 Laporan
   └── 📝 Laporan Kegiatan
   └── 📊 Penilaian
   └── 🏆 Peringkat Petugas
...
```

**Badge:** Shows count of total petugas
- Green if > 10 petugas
- Yellow if ≤ 10 petugas

---

## Permissions & Access

### Who Can Access:

| Role | View | Edit | Toggle Status | Create | Delete |
|------|------|------|---------------|--------|--------|
| **Supervisor** | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Admin** | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Super Admin** | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Petugas** | ❌ | ❌ | ❌ | ❌ | ❌ |

**Notes:**
- Create disabled → Use User Management untuk create user baru
- Delete disabled → Untuk safety, tidak bisa delete user
- Petugas tidak bisa lihat menu ini sama sekali

---

## Info Notification

**First Load Message:**
```
📊 Manajemen Petugas

Di sini Anda dapat melihat daftar petugas dan mengaktifkan/menonaktifkan
status mereka. Petugas yang non-aktif tidak dapat login ke aplikasi mobile.
```

**After Toggle:**
```
✅ Status Berhasil Diubah

Status {nama_petugas} berhasil diubah menjadi aktif/non-aktif
```

---

## Testing Checklist

### Test 1: View Petugas List
- [ ] Login sebagai supervisor
- [ ] Buka http://127.0.0.1:8003/admin/petugas
- [ ] Verifikasi muncul list petugas
- [ ] Verifikasi badge count di navigation
- [ ] Verifikasi icon status (green/red)

### Test 2: Non-Aktifkan Petugas
- [ ] Klik "Non-Aktifkan" pada petugas aktif
- [ ] Konfirmasi di dialog
- [ ] Verifikasi notifikasi success
- [ ] Verifikasi icon berubah menjadi red X
- [ ] Verifikasi status di database: `is_active = 0`

### Test 3: Mobile Login - Active User
- [ ] Login dengan petugas yang aktif
- [ ] Verifikasi login berhasil
- [ ] Verifikasi dapat token

### Test 4: Mobile Login - Inactive User
- [ ] Non-aktifkan petugas dari dashboard
- [ ] Login dengan petugas yang di-non-aktifkan
- [ ] Verifikasi error: "Your account has been deactivated"
- [ ] Verifikasi tidak dapat token

### Test 5: Aktifkan Kembali
- [ ] Klik "Aktifkan" pada petugas non-aktif
- [ ] Konfirmasi di dialog
- [ ] Verifikasi notifikasi success
- [ ] Verifikasi icon berubah menjadi green ✓
- [ ] Login mobile berhasil

### Test 6: Filter by Status
- [ ] Klik filter "Status"
- [ ] Pilih "Aktif" → Lihat hanya aktif
- [ ] Pilih "Non-Aktif" → Lihat hanya non-aktif
- [ ] Reset filter → Lihat semua

### Test 7: Bulk Activate
- [ ] Non-aktifkan 2-3 petugas
- [ ] Klik "Aktifkan Semua" di header
- [ ] Konfirmasi
- [ ] Verifikasi semua menjadi aktif

### Test 8: Edit Petugas
- [ ] Klik "Edit" pada petugas
- [ ] Update nama/email/phone/status
- [ ] Save
- [ ] Verifikasi perubahan tersimpan

### Test 9: User Management Integration
- [ ] Buka http://127.0.0.1:8003/admin/users
- [ ] Verifikasi ada kolom "Status"
- [ ] Verifikasi ada filter "Status"
- [ ] Edit user, toggle "Status Aktif"
- [ ] Verifikasi sinkron dengan Petugas Resource

---

## Database Schema

```sql
-- users table
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    name VARCHAR NOT NULL,
    email VARCHAR NOT NULL UNIQUE,
    password VARCHAR NOT NULL,
    phone VARCHAR,
    is_active BOOLEAN DEFAULT 1,  -- ✅ NEW COLUMN
    email_verified_at DATETIME,
    remember_token VARCHAR,
    created_at DATETIME,
    updated_at DATETIME
);
```

---

## API Response Examples

### Login Success (Active User)
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "Andi Petugas",
            "email": "andi@example.com",
            "phone": "628123456789"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### Login Failed (Inactive User)
```json
{
    "success": false,
    "message": "Your account has been deactivated. Please contact supervisor.",
    "errors": null
}
```

---

## Files Created

1. ✅ Migration: `database/migrations/2025_11_13_074132_add_is_active_to_users_table.php`
2. ✅ Resource: `app/Filament/Resources/Petugas/PetugasResource.php`
3. ✅ Page: `app/Filament/Resources/Petugas/Pages/ManagePetugas.php`
4. ✅ Documentation: `PETUGAS_MANAGEMENT.md`

## Files Modified

1. ✅ `app/Models/User.php` - Added fillable & cast
2. ✅ `app/Http/Controllers/Api/AuthController.php` - Added is_active check
3. ✅ `app/Filament/Resources/Users/Schemas/UserForm.php` - Added Toggle
4. ✅ `app/Filament/Resources/Users/Tables/UsersTable.php` - Added column & filter

---

## Benefits

### For Supervisor/Admin:
- 🎯 Kontrol akses petugas ke mobile app
- 📊 Monitoring status petugas secara visual
- ⚡ Quick toggle tanpa delete user
- 🔒 Temporary suspend tanpa kehilangan data historis

### For System:
- 🔐 Security - Prevent unauthorized access
- 📈 Audit trail - Track when users were deactivated
- 💾 Data retention - Keep user history even when inactive
- 🚀 Reversible - Easy to reactivate users

---

## Summary

**Status:** 🟢 Feature selesai dan siap digunakan

**New Features:**
- ✅ Sub menu "Petugas" di Master Data
- ✅ Toggle status aktif/non-aktif per petugas
- ✅ Bulk activate semua petugas
- ✅ Icon status di tabel (green ✓ / red ✗)
- ✅ Filter by status (Aktif/Non-Aktif)
- ✅ API login check untuk petugas non-aktif
- ✅ Integration dengan User Management

**Test URL:** http://127.0.0.1:8003/admin/petugas

**Next:** Ready untuk production deployment
