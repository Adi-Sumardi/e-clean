<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generalisasi laporan keterlambatan agar mendukung semua domain petugas
 * (kebersihan/satpam/ob/toko), bukan hanya kebersihan.
 *
 * - Tambah kolom `domain` (default 'kebersihan').
 * - `jadwal_kebersihan_id` jadi nullable (domain field tidak punya FK ini).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_keterlambatan', function (Blueprint $table) {
            if (! Schema::hasColumn('laporan_keterlambatan', 'domain')) {
                $table->string('domain')->default('kebersihan')->after('id');
            }
        });

        Schema::table('laporan_keterlambatan', function (Blueprint $table) {
            $table->unsignedBigInteger('jadwal_kebersihan_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_keterlambatan', function (Blueprint $table) {
            if (Schema::hasColumn('laporan_keterlambatan', 'domain')) {
                $table->dropColumn('domain');
            }
        });
    }
};
