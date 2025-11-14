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
        Schema::table('activity_reports', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('catatan');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('gps_accuracy', 8, 2)->nullable()->after('longitude');
            $table->text('gps_address')->nullable()->after('gps_accuracy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_reports', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'gps_accuracy', 'gps_address']);
        });
    }
};
