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
        Schema::create('guest_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lokasi_id')->constrained('lokasis')->cascadeOnDelete();
            $table->string('nama_pelapor');
            $table->string('email_pelapor')->nullable();
            $table->string('telepon_pelapor')->nullable();
            $table->string('jenis_keluhan'); // tumpahan, kotor, bau, rusak, lainnya
            $table->text('deskripsi_keluhan');
            $table->string('foto_keluhan')->nullable();
            $table->string('status')->default('pending'); // pending, in_progress, resolved, rejected
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('catatan_penanganan')->nullable();
            $table->string('foto_penanganan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index for faster queries
            $table->index(['lokasi_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_complaints');
    }
};
