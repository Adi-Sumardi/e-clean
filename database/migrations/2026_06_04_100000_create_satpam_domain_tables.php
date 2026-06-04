<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satpam (security/patrol) domain: patrol schedules + patrol reports.
 * Kept separate from the cleaning domain so security has its own workflow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_satpam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lokasi_id')->constrained('lokasis')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('shift')->default('pagi');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('status')->default('pending'); // pending|in_progress|completed|missed
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['petugas_id', 'tanggal']);
            $table->index(['tanggal', 'status']);
        });

        Schema::create('laporan_satpam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->nullable()->constrained('jadwal_satpam')->nullOnDelete();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lokasi_id')->constrained('lokasis')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai')->nullable();
            // Patrol-specific
            $table->string('kondisi')->default('aman'); // aman|perhatian|bahaya
            $table->text('temuan')->nullable();
            $table->text('tindakan')->nullable();
            $table->json('foto')->nullable();
            // Approval workflow (shared shape across field domains)
            $table->string('status')->default('submitted'); // draft|submitted|approved|rejected
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
        Schema::dropIfExists('laporan_satpam');
        Schema::dropIfExists('jadwal_satpam');
    }
};
