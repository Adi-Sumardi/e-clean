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
            // Reporting status: ontime, late, expired
            $table->string('reporting_status', 20)->default('ontime')->after('status');
            // Flag for auto-generated reports (expired jadwal)
            $table->boolean('is_auto_generated')->default(false)->after('reporting_status');
            // Minutes late (null if ontime)
            $table->integer('late_minutes')->nullable()->after('is_auto_generated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_reports', function (Blueprint $table) {
            $table->dropColumn(['reporting_status', 'is_auto_generated', 'late_minutes']);
        });
    }
};
