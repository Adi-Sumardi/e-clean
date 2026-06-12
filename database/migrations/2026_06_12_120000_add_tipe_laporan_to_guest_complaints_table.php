<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tamu memilih tipe laporan saat mengisi form keluhan (hasil scan QR lokasi):
 * kebersihan / office_boy / satpam — menentukan jenis petugas yang menindak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_complaints', function (Blueprint $table) {
            if (! Schema::hasColumn('guest_complaints', 'tipe_laporan')) {
                $table->string('tipe_laporan')->default('kebersihan')->after('jenis_keluhan');
                $table->index('tipe_laporan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_complaints', function (Blueprint $table) {
            if (Schema::hasColumn('guest_complaints', 'tipe_laporan')) {
                $table->dropIndex(['tipe_laporan']);
                $table->dropColumn('tipe_laporan');
            }
        });
    }
};
