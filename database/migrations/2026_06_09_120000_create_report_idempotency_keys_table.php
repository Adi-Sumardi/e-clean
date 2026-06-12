<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency untuk submit laporan dari PWA.
 *
 * Saat offline lalu sync, retry bisa terjadi setelah server sebenarnya sudah
 * menerima (respons hilang di jaringan jelek). Tiap submit membawa header
 * Idempotency-Key unik; key→report_id disimpan di sini agar retry mengembalikan
 * laporan yang sama, bukan membuat duplikat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_type');
            $table->unsignedBigInteger('report_id');
            $table->timestamps();

            $table->index(['user_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_idempotency_keys');
    }
};
