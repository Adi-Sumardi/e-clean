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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['whatsapp', 'email', 'system'])->default('system');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'read'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
