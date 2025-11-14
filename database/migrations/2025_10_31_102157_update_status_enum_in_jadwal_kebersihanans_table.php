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
        // Drop index first
        Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
            $table->dropIndex('jadwal_kebersihanans_status_index');
        });

        // Drop the old status column
        Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Create new status column with new enum values
        Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('catatan');
        });

        // Re-create index
        Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
            $table->index('status');
        });

        // Update all existing records to 'active'
        \DB::table('jadwal_kebersihanans')->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('jadwal_kebersihanans', function (Blueprint $table) {
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending')->after('catatan');
        });
    }
};
