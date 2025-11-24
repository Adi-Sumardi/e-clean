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
        Schema::create('photo_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_report_id')->nullable()->constrained('activity_reports')->onDelete('cascade');
            $table->string('photo_path')->nullable();
            $table->enum('photo_type', ['before', 'after']);

            // GPS Data
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('gps_accuracy');
            $table->text('gps_address')->nullable();
            $table->boolean('gps_validated')->default(false);
            $table->float('gps_distance_from_location')->nullable(); // in meters

            // Timestamp Data
            $table->timestamp('captured_at');
            $table->timestamp('server_time_at_capture');
            $table->string('timezone', 50)->default('Asia/Jakarta');

            // Device Data
            $table->string('device_model', 100)->nullable();
            $table->string('device_os', 100)->nullable();
            $table->text('browser_agent')->nullable();
            $table->string('screen_resolution', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('network_type', 20)->nullable();

            // Verification Data
            $table->string('photo_hash', 64); // SHA-256
            $table->string('watermark_hash', 64);
            $table->json('exif_data')->nullable();
            $table->boolean('is_tampered')->default(false);
            $table->float('tamper_detection_score')->nullable();

            // Metadata
            $table->unsignedInteger('file_size')->nullable();
            $table->string('original_dimensions', 50)->nullable();
            $table->string('compressed_dimensions', 50)->nullable();
            $table->float('compression_ratio')->nullable();

            $table->timestamps();

            // Indexes for fast queries
            $table->index('photo_hash');
            $table->index('captured_at');
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photo_metadata');
    }
};
