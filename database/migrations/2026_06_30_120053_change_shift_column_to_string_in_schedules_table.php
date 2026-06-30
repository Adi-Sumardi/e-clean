<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop Postgres CHECK constraints if they exist
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE jadwal_kebersihanans DROP CONSTRAINT IF EXISTS jadwal_kebersihanans_shift_check");
            DB::statement("ALTER TABLE laporan_keterlambatan DROP CONSTRAINT IF EXISTS laporan_keterlambatan_shift_check");
        }

        Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
            $table->string('shift', 50)->default('pagi')->change();
        });

        Schema::table('laporan_keterlambatan', function (Blueprint $table) {
            $table->string('shift', 50)->default('pagi')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
            $table->enum('shift', ['pagi', 'standby', 'siang', 'sweeping', 'sore'])->default('pagi')->change();
        });

        Schema::table('laporan_keterlambatan', function (Blueprint $table) {
            $table->enum('shift', ['pagi', 'standby', 'siang', 'sweeping', 'sore'])->default('pagi')->change();
        });
    }
};
