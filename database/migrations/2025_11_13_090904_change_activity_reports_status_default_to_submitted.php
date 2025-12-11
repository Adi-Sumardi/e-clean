<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL: Change default value only (enum constraint already exists)
        DB::statement("ALTER TABLE activity_reports ALTER COLUMN status SET DEFAULT 'submitted'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to 'draft' as default
        DB::statement("ALTER TABLE activity_reports ALTER COLUMN status SET DEFAULT 'draft'");
    }
};
