<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix: shift enum hanya punya pagi/siang/sore, tapi WorkShift enum punya 5 shift.
     * Menambahkan standby dan sweeping ke tabel jadwal_kebersihanans dan laporan_keterlambatan.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        $newValues = "'pagi','standby','siang','sweeping','sore'";

        // === jadwal_kebersihanans.shift ===
        if ($driver === 'sqlite') {
            try {
                Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
                    $table->dropIndex('idx_jadwal_shift');
                });
            } catch (\Exception $e) {
                // Index may not exist
            }
            Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
                $table->dropColumn('shift');
            });
            Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
                $table->enum('shift', ['pagi', 'standby', 'siang', 'sweeping', 'sore'])
                    ->default('pagi')
                    ->after('tanggal');
                $table->index('shift', 'idx_jadwal_shift');
            });
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE jadwal_kebersihanans DROP CONSTRAINT IF EXISTS jadwal_kebersihanans_shift_check");
            DB::statement("ALTER TABLE jadwal_kebersihanans ADD CONSTRAINT jadwal_kebersihanans_shift_check CHECK (shift::text = ANY (ARRAY[{$newValues}]::text[]))");
        } else {
            DB::statement("ALTER TABLE jadwal_kebersihanans MODIFY COLUMN shift ENUM({$newValues}) NOT NULL DEFAULT 'pagi'");
        }

        // === laporan_keterlambatan.shift ===
        if ($driver === 'sqlite') {
            Schema::table('laporan_keterlambatan', function (Blueprint $table) {
                $table->dropColumn('shift');
            });
            Schema::table('laporan_keterlambatan', function (Blueprint $table) {
                $table->enum('shift', ['pagi', 'standby', 'siang', 'sweeping', 'sore'])
                    ->default('pagi')
                    ->after('tanggal');
            });
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE laporan_keterlambatan DROP CONSTRAINT IF EXISTS laporan_keterlambatan_shift_check");
            DB::statement("ALTER TABLE laporan_keterlambatan ADD CONSTRAINT laporan_keterlambatan_shift_check CHECK (shift::text = ANY (ARRAY[{$newValues}]::text[]))");
        } else {
            DB::statement("ALTER TABLE laporan_keterlambatan MODIFY COLUMN shift ENUM({$newValues}) NOT NULL DEFAULT 'pagi'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        $oldValues = "'pagi','siang','sore'";

        if ($driver === 'sqlite') {
            Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
                $table->dropColumn('shift');
            });
            Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
                $table->enum('shift', ['pagi', 'siang', 'sore'])->default('pagi')->after('tanggal');
            });

            Schema::table('laporan_keterlambatan', function (Blueprint $table) {
                $table->dropColumn('shift');
            });
            Schema::table('laporan_keterlambatan', function (Blueprint $table) {
                $table->enum('shift', ['pagi', 'siang', 'sore'])->default('pagi')->after('tanggal');
            });
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE jadwal_kebersihanans DROP CONSTRAINT IF EXISTS jadwal_kebersihanans_shift_check");
            DB::statement("ALTER TABLE jadwal_kebersihanans ADD CONSTRAINT jadwal_kebersihanans_shift_check CHECK (shift::text = ANY (ARRAY[{$oldValues}]::text[]))");
            DB::statement("ALTER TABLE laporan_keterlambatan DROP CONSTRAINT IF EXISTS laporan_keterlambatan_shift_check");
            DB::statement("ALTER TABLE laporan_keterlambatan ADD CONSTRAINT laporan_keterlambatan_shift_check CHECK (shift::text = ANY (ARRAY[{$oldValues}]::text[]))");
        } else {
            DB::statement("ALTER TABLE jadwal_kebersihanans MODIFY COLUMN shift ENUM({$oldValues}) NOT NULL DEFAULT 'pagi'");
            DB::statement("ALTER TABLE laporan_keterlambatan MODIFY COLUMN shift ENUM({$oldValues}) NOT NULL DEFAULT 'pagi'");
        }
    }
};
