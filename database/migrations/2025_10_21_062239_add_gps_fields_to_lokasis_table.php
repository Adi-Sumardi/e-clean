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
        Schema::table('lokasis', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('is_active');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->text('address')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lokasis', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'address']);
        });
    }
};
