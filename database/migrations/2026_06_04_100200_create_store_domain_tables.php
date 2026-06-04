<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Petugas Toko (store) domain: store shift schedules + daily checklist
 * reports, kept separate from the cleaning domain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_toko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lokasi_id')->constrained('lokasis')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('shift')->default('pagi');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('status')->default('pending');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['petugas_id', 'tanggal']);
            $table->index(['tanggal', 'status']);
        });

        Schema::create('laporan_toko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->nullable()->constrained('jadwal_toko')->nullOnDelete();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lokasi_id')->constrained('lokasis')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai')->nullable();
            // Store-specific
            $table->json('checklist')->nullable(); // [{item, done}]
            $table->string('kondisi_stok')->nullable(); // aman|menipis|kosong
            $table->text('catatan_stok')->nullable();
            $table->json('foto')->nullable();
            // Approval workflow
            $table->string('status')->default('submitted');
            $table->text('catatan_petugas')->nullable();
            $table->text('catatan_supervisor')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['petugas_id', 'tanggal']);
            $table->index(['status', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_toko');
        Schema::dropIfExists('jadwal_toko');
    }
};
