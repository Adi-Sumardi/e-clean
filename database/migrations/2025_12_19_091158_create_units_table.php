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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('kode_unit', 50)->unique();
            $table->string('nama_unit');
            $table->text('deskripsi')->nullable();
            $table->string('alamat')->nullable();
            $table->string('penanggung_jawab')->nullable();
            $table->string('telepon', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Add unit_id to lokasis table
        Schema::table('lokasis', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('id')->constrained('units')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lokasis', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });

        Schema::dropIfExists('units');
    }
};