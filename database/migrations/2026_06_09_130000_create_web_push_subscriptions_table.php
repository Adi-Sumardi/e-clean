<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Langganan Web Push (VAPID) untuk PWA petugas.
 *
 * Satu user bisa punya beberapa device/browser → satu baris per endpoint.
 * Endpoint unik; subscription yang invalid (404/410) dihapus otomatis saat
 * pengiriman gagal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->unique();
            $table->string('public_key');   // p256dh
            $table->string('auth_token');    // auth
            $table->string('content_encoding')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_push_subscriptions');
    }
};
