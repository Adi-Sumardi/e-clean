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
        Schema::create('jadwal_kebersihanans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lokasi_id')->constrained('lokasis')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('shift', ['pagi', 'siang', 'sore']);
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->enum('prioritas', ['rendah', 'normal', 'tinggi'])->default('normal');
            $table->text('catatan')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('jadwal_kebersihanans');
    }
};
