<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for each resource
        $resources = [
            'Lokasi',
            'JadwalKebersihan',
            'ActivityReport',
            'Presensi',
            'Penilaian',
        ];

        $permissions = [];
        foreach ($resources as $resource) {
            foreach (['view', 'view_any', 'create', 'update', 'delete', 'delete_any'] as $ability) {
                $permission = Permission::firstOrCreate([
                    'name' => $ability . '_' . $resource,
                    'guard_name' => 'web',
                ]);
                $permissions[$resource][$ability] = $permission;
            }
        }

        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $supervisor = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $pengurus = Role::firstOrCreate(['name' => 'pengurus', 'guard_name' => 'web']);
        $petugas = Role::firstOrCreate(['name' => 'petugas', 'guard_name' => 'web']);
        // Field-staff roles for the separate satpam / office boy / store domains.
        // Their data lives in dedicated tables and is consumed via the mobile API,
        // so they share the same baseline permissions as petugas.
        $satpam = Role::firstOrCreate(['name' => 'satpam', 'guard_name' => 'web']);
        $officeBoy = Role::firstOrCreate(['name' => 'office_boy', 'guard_name' => 'web']);
        $petugasToko = Role::firstOrCreate(['name' => 'petugas_toko', 'guard_name' => 'web']);

        // Super Admin - Full Access to Everything
        $superAdmin->syncPermissions(Permission::all());

        // Admin - Full CRUD on all resources
        $adminPermissions = [];
        foreach ($permissions as $resource => $abilities) {
            $adminPermissions = array_merge($adminPermissions, array_values($abilities));
        }
        $admin->syncPermissions($adminPermissions);

        // Supervisor - Can view all, create/update/approve reports and evaluations
        $supervisorPermissions = [
            // Lokasi - View only
            $permissions['Lokasi']['view'],
            $permissions['Lokasi']['view_any'],

            // Jadwal - Full access
            $permissions['JadwalKebersihan']['view'],
            $permissions['JadwalKebersihan']['view_any'],
            $permissions['JadwalKebersihan']['create'],
            $permissions['JadwalKebersihan']['update'],
            $permissions['JadwalKebersihan']['delete'],

            // Activity Reports - Full access (approve/reject)
            $permissions['ActivityReport']['view'],
            $permissions['ActivityReport']['view_any'],
            $permissions['ActivityReport']['update'], // For approving

            // Presensi - Full access
            $permissions['Presensi']['view'],
            $permissions['Presensi']['view_any'],
            $permissions['Presensi']['create'],
            $permissions['Presensi']['update'],

            // Penilaian - Full access
            $permissions['Penilaian']['view'],
            $permissions['Penilaian']['view_any'],
            $permissions['Penilaian']['create'],
            $permissions['Penilaian']['update'],
            $permissions['Penilaian']['delete'],
        ];
        $supervisor->syncPermissions($supervisorPermissions);

        // Pengurus - Read-only access to all
        $pengurusPermissions = [
            $permissions['Lokasi']['view'],
            $permissions['Lokasi']['view_any'],
            $permissions['JadwalKebersihan']['view'],
            $permissions['JadwalKebersihan']['view_any'],
            $permissions['ActivityReport']['view'],
            $permissions['ActivityReport']['view_any'],
            $permissions['Presensi']['view'],
            $permissions['Presensi']['view_any'],
            $permissions['Penilaian']['view'],
            $permissions['Penilaian']['view_any'],
        ];
        $pengurus->syncPermissions($pengurusPermissions);

        // Petugas - Can only create/view their own reports and attendance
        $petugasPermissions = [
            // Lokasi - View only
            $permissions['Lokasi']['view'],
            $permissions['Lokasi']['view_any'],

            // Jadwal - View only (their own schedules)
            $permissions['JadwalKebersihan']['view'],
            $permissions['JadwalKebersihan']['view_any'],

            // Activity Reports - Create and view own
            $permissions['ActivityReport']['view'],
            $permissions['ActivityReport']['view_any'],
            $permissions['ActivityReport']['create'],
            $permissions['ActivityReport']['update'], // Own reports only

            // Presensi - Create and view own
            $permissions['Presensi']['view'],
            $permissions['Presensi']['view_any'],
            $permissions['Presensi']['create'],

            // Penilaian - View only (their evaluations)
            $permissions['Penilaian']['view'],
            $permissions['Penilaian']['view_any'],
        ];
        $petugas->syncPermissions($petugasPermissions);

        // Satpam / Office Boy / Petugas Toko share the petugas baseline. Their
        // domain-specific data is gated in the API controllers by role, not by
        // Filament permissions, so the same Filament permission set is fine.
        $satpam->syncPermissions($petugasPermissions);
        $officeBoy->syncPermissions($petugasPermissions);
        $petugasToko->syncPermissions($petugasPermissions);

        $this->command->info('Roles and permissions created successfully!');
    }
}
