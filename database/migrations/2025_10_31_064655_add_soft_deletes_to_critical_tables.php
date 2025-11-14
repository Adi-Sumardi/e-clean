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
        // Add soft deletes to activity_reports
        if (!Schema::hasColumn('activity_reports', 'deleted_at')) {
            Schema::table('activity_reports', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to jadwal_kebersihanans
        if (!Schema::hasColumn('jadwal_kebersihanans', 'deleted_at')) {
            Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to penilaians
        if (!Schema::hasColumn('penilaians', 'deleted_at')) {
            Schema::table('penilaians', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to lokasis
        if (!Schema::hasColumn('lokasis', 'deleted_at')) {
            Schema::table('lokasis', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('activity_reports', 'deleted_at')) {
            Schema::table('activity_reports', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('jadwal_kebersihanans', 'deleted_at')) {
            Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('penilaians', 'deleted_at')) {
            Schema::table('penilaians', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('lokasis', 'deleted_at')) {
            Schema::table('lokasis', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
