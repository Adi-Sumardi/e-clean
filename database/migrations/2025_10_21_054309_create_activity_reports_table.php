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
        Schema::create('activity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lokasi_id')->constrained('lokasis')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->nullable()->constrained('jadwal_kebersihanans')->nullOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai')->nullable();
            $table->text('kegiatan'); // Deskripsi kegiatan yang dilakukan
            $table->json('foto_sebelum')->nullable(); // Multiple photos
            $table->json('foto_sesudah')->nullable(); // Multiple photos
            $table->json('koordinat_lokasi')->nullable(); // GPS coordinates {lat, lng}
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->text('catatan_petugas')->nullable();
            $table->text('catatan_supervisor')->nullable();
            $table->integer('rating')->nullable()->comment('1-5');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['petugas_id', 'tanggal']);
            $table->index(['lokasi_id', 'tanggal']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_reports');
    }
};
