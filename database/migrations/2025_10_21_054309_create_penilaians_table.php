<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penilaians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_report_id')->nullable()->constrained('activity_reports')->nullOnDelete();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->date('periode_start');
            $table->date('periode_end');
            $table->integer('aspek_kebersihan')->comment('1-5');
            $table->integer('aspek_kerapihan')->comment('1-5');
            $table->integer('aspek_ketepatan_waktu')->comment('1-5');
            $table->integer('aspek_kelengkapan_laporan')->comment('1-5');
            $table->decimal('rating_total', 3, 2); // Average
            $table->text('catatan')->nullable();
            $table->foreignId('penilai_id')->constrained('users')->cascadeOnDelete(); // Supervisor
            $table->timestamps();

            // Indexes
            $table->index(['petugas_id', 'periode_start', 'periode_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaians');
    }
};
