# User Management & Role/Permission Setup - Completed

## What Was Fixed

The user reported: **"menu role dan permisson belum muncul dan menu user management juga belum ada"** (Role & Permission menu and User Management menu are missing)

### Root Causes Identified and Fixed

1. **FilamentShield Plugin Not Registered** ✅ FIXED
   - Shield plugin was installed but not registered in AdminPanelProvider
   - **Fix**: Added `FilamentShieldPlugin::make()` to AdminPanelProvider.php

2. **UserResource Did Not Exist** ✅ FIXED
   - No User management resource in Filament
   - **Fix**: Created complete UserResource with form, table, and pages

3. **User Model Missing Phone in Fillable** ✅ FIXED
   - Phone field couldn't be saved
   - **Fix**: Added 'phone' to fillable array in User model

---

## Implementation Summary

### 1. Shield Plugin Registration

**File**: `app/Providers/Filament/AdminPanelProvider.php`

Added Shield plugin to enable Role & Permission management:

```php
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other configurations
        ->plugin(FilamentShieldPlugin::make())
        ->middleware([...])
}
```

**Result**: Role & Permission menu now appears under Shield navigation group

---

### 2. User Management Resource

#### UserResource.php
**File**: `app/Filament/Resources/Users/UserResource.php`

Complete user management resource with:
- **Navigation Group**: "User Management"
- **Navigation Icon**: User icon (OutlinedUsers)
- **Navigation Badge**: Shows total user count
- **Badge Color**: Warning if > 10 users, success otherwise

```php
protected static UnitEnum|string|null $navigationGroup = 'User Management';
protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
protected static ?int $navigationSort = 1;

public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}
```

#### UserForm.php (Form Schema)
**File**: `app/Filament/Resources/Users/Schemas/UserForm.php`

Two sections:

**Section 1: Informasi Pengguna (User Information)**
- Name (required, max 255)
- Email (required, unique, max 255)
- Phone (WhatsApp format helper: 62812xxxx)
- Password (hashed, required on create, optional on edit)

**Section 2: Role & Permission**
- Role selector (multiple, preloaded from database)
- Email verified at timestamp

Key Features:
```php
TextInput::make('password')
    ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
    ->dehydrated(fn ($state) => filled($state))
    ->required(fn (string $context): bool => $context === 'create')
    ->helperText(fn (string $context): string =>
        $context === 'edit'
            ? 'Kosongkan jika tidak ingin mengubah password'
            : 'Minimal 8 karakter'
    )
```

```php
Select::make('roles')
    ->relationship('roles', 'name')
    ->multiple()
    ->preload()
    ->helperText('Pilih role untuk pengguna (petugas, supervisor, atau admin)')
```

#### UsersTable.php (Table Configuration)
**File**: `app/Filament/Resources/Users/Tables/UsersTable.php`

Columns:
1. **Name** - Searchable, sortable
2. **Email** - Searchable, sortable, copyable
3. **Phone** - Searchable, sortable, copyable (for WhatsApp)
4. **Roles** - Badge display with colors:
   - Red (danger) for super_admin
   - Yellow (warning) for supervisor
   - Green (success) for petugas
5. **Email Verified At** - Date/time format
6. **Created At** - Hidden by default
7. **Updated At** - Hidden by default

Filters:
- Role filter (multiple selection, preloaded)

Default Sort:
- Created at descending (newest first)

```php
TextColumn::make('roles.name')
    ->badge()
    ->colors([
        'danger' => 'super_admin',
        'warning' => 'supervisor',
        'success' => 'petugas',
    ])
```

---

### 3. User Model Updates

**File**: `app/Models/User.php`

Added phone to fillable attributes:

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'phone',  // ✅ Added for WhatsApp notifications
];
```

The model already had:
- `HasRoles` trait from Spatie Permission
- `FilamentUser` interface implementation
- `canAccessPanel()` method

---

## Menu Structure Now Available

After the fixes, the following menus should appear in Filament Admin Panel:

### 1. User Management Group
- **Users** (UserResource)
  - List all users
  - Create new user
  - Edit user
  - View user count badge

### 2. Shield Group
- **Roles** (RoleResource from Shield)
  - List all roles
  - Create new role
  - Edit role permissions
  - Assign permissions per resource

---

## Routes Registered

The following Filament routes are now available:

### User Management
- `GET /admin/users` - List users
- `GET /admin/users/create` - Create user form
- `GET /admin/users/{record}/edit` - Edit user form

### Role & Permission (Shield)
- `GET /admin/shield/roles` - List roles
- `GET /admin/shield/roles/create` - Create role
- `GET /admin/shield/roles/{record}` - View role
- `GET /admin/shield/roles/{record}/edit` - Edit role

---

## Testing Checklist

### User Management
- [ ] Access `/admin/users` - Users list should load
- [ ] Click "New User" button
- [ ] Fill in user details:
  - Name: Test User
  - Email: test@example.com
  - Phone: 628123456789
  - Password: password123
  - Role: Select petugas/supervisor/admin
- [ ] Save and verify user is created
- [ ] Edit user and change phone number
- [ ] Edit user and leave password blank (should not change)
- [ ] Verify role badge appears in table
- [ ] Test role filter in user table
- [ ] Test copy email/phone functionality
- [ ] Verify navigation badge shows user count

### Role & Permission
- [ ] Access `/admin/shield/roles` - Roles list should load
- [ ] Verify existing roles appear (petugas, supervisor, super_admin)
- [ ] Click on a role to edit permissions
- [ ] Verify resources show permission checkboxes:
  - view, view_any, create, update, delete, delete_any
- [ ] Test assigning/removing permissions
- [ ] Save and verify permissions are applied

---

## How Role-Based Access Control Works

### 1. Role Assignment
Users can have multiple roles assigned through the UserResource form.

### 2. Permission Structure
Shield automatically generates permissions for each Filament resource:
- `view_{resource}` - View single record
- `view_any_{resource}` - View list
- `create_{resource}` - Create new record
- `update_{resource}` - Edit record
- `delete_{resource}` - Delete single record
- `delete_any_{resource}` - Bulk delete

### 3. Middleware Protection
Resources can be protected by checking permissions:

```php
// In any Resource
public static function canViewAny(): bool
{
    return auth()->user()->can('view_any_user');
}
```

### 4. Policy Generation (Optional)
Shield can generate Laravel Policies for more complex authorization logic.

---

## Integration with WhatsApp Notifications

The phone field in UserResource is specifically for WhatsApp notifications:

1. **Format**: 62812xxxx (Indonesian format)
2. **Usage**: FontteService sends notifications to this number
3. **Observers**: Automatically notify users when:
   - Schedule assigned (petugas)
   - Report submitted (supervisors)
   - Report approved/rejected (petugas)
4. **Commands**: Daily reminders sent to phone numbers:
   - 07:00 - Morning attendance reminder
   - 16:00 - Evening checkout reminder
   - 18:00 - Tomorrow's schedule reminder

---

## Next Steps

### 1. Create Initial Roles (Required)
Run in Tinker or create a seeder:

```php
use Spatie\Permission\Models\Role;

// Create roles
Role::create(['name' => 'super_admin']);
Role::create(['name' => 'supervisor']);
Role::create(['name' => 'petugas']);
```

### 2. Assign Super Admin to First User

```bash
php artisan shield:super-admin
# Enter the user ID when prompted
```

### 3. Configure Permissions
- Login to admin panel
- Navigate to Shield → Roles
- Edit each role and assign appropriate permissions

### 4. Test User Creation
- Create test users with different roles
- Verify role badges appear
- Test phone number for WhatsApp notifications

---

## Files Modified

1. ✅ `app/Providers/Filament/AdminPanelProvider.php` - Added Shield plugin
2. ✅ `app/Filament/Resources/Users/UserResource.php` - Created with navigation config
3. ✅ `app/Filament/Resources/Users/Schemas/UserForm.php` - User form with roles
4. ✅ `app/Filament/Resources/Users/Tables/UsersTable.php` - User table with role badges
5. ✅ `app/Filament/Resources/Users/Pages/ListUsers.php` - Auto-generated
6. ✅ `app/Filament/Resources/Users/Pages/CreateUser.php` - Auto-generated
7. ✅ `app/Filament/Resources/Users/Pages/EditUser.php` - Auto-generated
8. ✅ `app/Models/User.php` - Added phone to fillable

---

## Configuration Reference

### Shield Config
**File**: `config/filament-shield.php`

Key settings:
- Resource slug: `shield/roles`
- Show model path: `true`
- Cluster: `null`
- Permission tabs: pages, widgets, resources enabled

### Spatie Permission
Shield uses `spatie/laravel-permission` package under the hood.

Tables:
- `roles` - Role definitions
- `permissions` - Permission definitions
- `model_has_roles` - User-role assignments
- `model_has_permissions` - Direct user-permission assignments
- `role_has_permissions` - Role-permission assignments

---

## Troubleshooting

### Menu Still Not Appearing

1. **Clear all caches**:
   ```bash
   php artisan optimize:clear
   php artisan filament:clear-cached-components
   ```

2. **Verify plugin registration**:
   ```bash
   php artisan route:list --name=filament | grep shield
   ```
   Should show shield/roles routes

3. **Check user can access panel**:
   ```php
   // In User model
   public function canAccessPanel(Panel $panel): bool
   {
       return true; // Temporarily return true
   }
   ```

### Role Dropdown Empty

Run this to create initial roles:
```bash
php artisan tinker
>>> \Spatie\Permission\Models\Role::create(['name' => 'super_admin']);
>>> \Spatie\Permission\Models\Role::create(['name' => 'supervisor']);
>>> \Spatie\Permission\Models\Role::create(['name' => 'petugas']);
```

### Phone Number Not Saving

Verify:
1. Phone field in fillable array ✅
2. Migration added phone column ✅
3. Form has phone TextInput ✅
4. Table has phone TextColumn ✅

---

## Summary

**Problem**: Role & Permission menu and User Management menu were missing

**Solution**:
1. ✅ Registered FilamentShield plugin in AdminPanelProvider
2. ✅ Created complete UserResource with role management
3. ✅ Added phone field to User model fillable
4. ✅ Configured navigation groups and icons
5. ✅ Added role badges and filters to user table

**Result**: Both menus should now appear in admin panel sidebar
- User Management → Users
- Shield → Roles

**Status**: ✅ COMPLETE - Ready for testing
