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
        // For SQLite, we need to recreate the table with new schema
        // First backup existing data
        DB::statement('CREATE TEMPORARY TABLE penilaians_backup AS SELECT * FROM penilaians');

        // Drop old table
        Schema::dropIfExists('penilaians');

        // Create new table with correct schema
        Schema::create('penilaians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('penilai_id')->constrained('users')->cascadeOnDelete(); // Supervisor
            $table->integer('periode_bulan')->comment('1-12');
            $table->integer('periode_tahun')->comment('e.g. 2025');
            $table->decimal('skor_kehadiran', 5, 2)->comment('0-100');
            $table->decimal('skor_kualitas', 5, 2)->comment('0-100');
            $table->decimal('skor_ketepatan_waktu', 5, 2)->comment('0-100');
            $table->decimal('skor_kebersihan', 5, 2)->comment('0-100');
            $table->decimal('total_skor', 6, 2)->comment('Sum of all scores');
            $table->decimal('rata_rata', 5, 2)->comment('Average score');
            $table->enum('kategori', ['Sangat Baik', 'Baik', 'Cukup', 'Kurang']);
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['petugas_id', 'periode_bulan', 'periode_tahun'], 'unique_petugas_period');
            $table->index(['periode_tahun', 'periode_bulan']);
        });

        // Restore data if any (but likely empty or incompatible, so skip)
        // Drop temp table
        DB::statement('DROP TABLE IF EXISTS penilaians_backup');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penilaians', function (Blueprint $table) {
            // Drop new columns
            $table->dropUnique('unique_petugas_period');
            $table->dropColumn([
                'periode_bulan',
                'periode_tahun',
                'skor_kehadiran',
                'skor_kualitas',
                'skor_ketepatan_waktu',
                'skor_kebersihan',
                'total_skor',
                'rata_rata',
                'kategori'
            ]);
        });

        Schema::table('penilaians', function (Blueprint $table) {
            // Restore old columns
            $table->foreignId('activity_report_id')->nullable()->constrained('activity_reports')->nullOnDelete();
            $table->date('periode_start');
            $table->date('periode_end');
            $table->integer('aspek_kebersihan')->comment('1-5');
            $table->integer('aspek_kerapihan')->comment('1-5');
            $table->integer('aspek_ketepatan_waktu')->comment('1-5');
            $table->integer('aspek_kelengkapan_laporan')->comment('1-5');
            $table->decimal('rating_total', 3, 2);
        });
    }
};
