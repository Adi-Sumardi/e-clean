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
            $table->boolean('foto_sebelum_verified')->default(false)->after('foto_sesudah');
            $table->boolean('foto_sesudah_verified')->default(false)->after('foto_sebelum_verified');
            $table->float('verification_score')->default(0)->after('foto_sesudah_verified');
            $table->json('fraud_flags')->nullable()->after('verification_score');
            $table->boolean('manual_review_required')->default(false)->after('fraud_flags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_reports', function (Blueprint $table) {
            $table->dropColumn([
                'foto_sebelum_verified',
                'foto_sesudah_verified',
                'verification_score',
                'fraud_flags',
                'manual_review_required'
            ]);
        });
    }
};
