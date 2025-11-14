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
        Schema::create('laporan_keterlambatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_kebersihan_id')->constrained('jadwal_kebersihan')->onDelete('cascade');
            $table->foreignId('petugas_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lokasi_id')->constrained('lokasi')->onDelete('cascade');
            $table->date('tanggal');
            $table->enum('shift', ['pagi', 'siang', 'sore']);
            $table->time('batas_waktu_mulai'); // Jam mulai shift
            $table->time('batas_waktu_selesai'); // Jam selesai shift
            $table->enum('status', ['warning', 'terlewat'])->default('terlewat');
            $table->text('keterangan')->nullable();
            $table->timestamp('waktu_terdeteksi'); // Kapan sistem mendeteksi keterlambatan
            $table->timestamps();

            // Index untuk query yang lebih cepat
            $table->index(['petugas_id', 'tanggal']);
            $table->index(['status', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_keterlambatan');
    }
};
