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
            // Change status default from 'draft' to 'submitted'
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])
                ->default('submitted')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_reports', function (Blueprint $table) {
            // Revert back to 'draft' as default
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])
                ->default('draft')
                ->change();
        });
    }
};
