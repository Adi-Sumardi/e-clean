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
        Schema::create('lokasis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_lokasi')->unique(); // ex: RK-1A, TL-L2
            $table->string('nama_lokasi');
            $table->text('deskripsi')->nullable();
            $table->enum('kategori', ['ruang_kelas', 'toilet', 'kantor', 'aula', 'taman', 'koridor', 'lainnya']);
            $table->string('lantai')->nullable(); // ex: Lantai 1, Lantai 2
            $table->decimal('luas_area', 8, 2)->nullable(); // dalam m²
            $table->string('foto_lokasi')->nullable();
            $table->text('qr_code')->nullable(); // Generated QR Code
            $table->enum('status_kebersihan', ['bersih', 'kotor', 'belum_dicek'])->default('belum_dicek');
            $table->timestamp('last_cleaned_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('kode_lokasi');
            $table->index('status_kebersihan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lokasis');
    }
};
