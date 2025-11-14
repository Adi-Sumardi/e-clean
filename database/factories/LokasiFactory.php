<?php

namespace Database\Factories;

use App\Models\Lokasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class LokasiFactory extends Factory
{
    protected $model = Lokasi::class;

    public function definition(): array
    {
        return [
            'kode_lokasi' => 'LOK-' . fake()->unique()->numberBetween(1000, 9999),
            'nama_lokasi' => fake()->randomElement([
                'Ruang Kelas 1A',
                'Ruang Kelas 2B',
                'Kantor Guru',
                'Perpustakaan',
                'Laboratorium',
                'Aula',
                'Taman Sekolah',
                'Toilet Lantai 1',
                'Koridor Utama',
            ]),
            'kategori' => fake()->randomElement(['ruang_kelas', 'toilet', 'kantor', 'aula', 'taman', 'koridor', 'lainnya']),
            'deskripsi' => fake()->sentence(),
            'latitude' => fake()->latitude(-7, -5),
            'longitude' => fake()->longitude(106, 108),
            'qr_code' => 'qrcodes/' . fake()->uuid() . '.svg',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
