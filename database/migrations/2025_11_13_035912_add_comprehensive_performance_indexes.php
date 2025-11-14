<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add comprehensive database indexes for performance optimization
     */
    public function up(): void
    {
        // Activity Reports Indexes - heavily queried for statistics and filtering
        Schema::table('activity_reports', function (Blueprint $table) {
            $table->index('rating', 'idx_ar_rating');
            $table->index(['status', 'tanggal'], 'idx_ar_status_tanggal');
            $table->index(['petugas_id', 'status', 'tanggal'], 'idx_ar_petugas_status_date');
        });

        // Penilaian Indexes - queried by kategori and period
        Schema::table('penilaians', function (Blueprint $table) {
            $table->index('kategori', 'idx_penilaian_kategori');
            $table->index(['petugas_id', 'periode_tahun', 'periode_bulan'], 'idx_petugas_period');
        });

        // Lokasi Indexes - filtered by active status and kategori
        Schema::table('lokasis', function (Blueprint $table) {
            $table->index(['is_active', 'kategori'], 'idx_lokasi_active_kategori');
            $table->index('lantai', 'idx_lokasi_lantai');
        });

        // Jadwal Kebersihan Indexes - frequently queried by petugas, status, date
        Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
            $table->index(['petugas_id', 'status', 'tanggal'], 'idx_jadwal_petugas_status_date');
            $table->index('shift', 'idx_jadwal_shift');
        });

        // Laporan Keterlambatan Indexes - queried by lokasi and date
        Schema::table('laporan_keterlambatan', function (Blueprint $table) {
            $table->index(['lokasi_id', 'tanggal'], 'idx_late_lokasi_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_reports', function (Blueprint $table) {
            $table->dropIndex('idx_ar_rating');
            $table->dropIndex('idx_ar_status_tanggal');
            $table->dropIndex('idx_ar_petugas_status_date');
        });

        Schema::table('penilaians', function (Blueprint $table) {
            $table->dropIndex('idx_penilaian_kategori');
            $table->dropIndex('idx_petugas_period');
        });

        Schema::table('lokasis', function (Blueprint $table) {
            $table->dropIndex('idx_lokasi_active_kategori');
            $table->dropIndex('idx_lokasi_lantai');
        });

        Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
            $table->dropIndex('idx_jadwal_petugas_status_date');
            $table->dropIndex('idx_jadwal_shift');
        });

        Schema::table('laporan_keterlambatan', function (Blueprint $table) {
            $table->dropIndex('idx_late_lokasi_date');
        });
    }
};
