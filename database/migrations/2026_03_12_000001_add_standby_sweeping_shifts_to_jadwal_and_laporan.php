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

        // === jadwal_kebersihanans.shift ===
        if ($driver === 'sqlite') {
            // SQLite: drop index first, then recreate column
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
        } else {
            // MySQL/PostgreSQL: alter enum
            DB::statement("ALTER TABLE jadwal_kebersihanans MODIFY COLUMN shift ENUM('pagi','standby','siang','sweeping','sore') NOT NULL DEFAULT 'pagi'");
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
        } else {
            DB::statement("ALTER TABLE laporan_keterlambatan MODIFY COLUMN shift ENUM('pagi','standby','siang','sweeping','sore') NOT NULL DEFAULT 'pagi'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

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
        } else {
            DB::statement("ALTER TABLE jadwal_kebersihanans MODIFY COLUMN shift ENUM('pagi','siang','sore') NOT NULL DEFAULT 'pagi'");
            DB::statement("ALTER TABLE laporan_keterlambatan MODIFY COLUMN shift ENUM('pagi','siang','sore') NOT NULL DEFAULT 'pagi'");
        }
    }
};
