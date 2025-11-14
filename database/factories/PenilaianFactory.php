<?php

namespace Database\Factories;

use App\Models\Penilaian;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenilaianFactory extends Factory
{
    protected $model = Penilaian::class;

    public function definition(): array
    {
        $skorKehadiran = fake()->numberBetween(3, 5);
        $skorKualitas = fake()->numberBetween(3, 5);
        $skorKetepatanWaktu = fake()->numberBetween(3, 5);
        $skorKebersihan = fake()->numberBetween(3, 5);
        $rataRata = ($skorKehadiran + $skorKualitas + $skorKetepatanWaktu + $skorKebersihan) / 4;

        $kategori = match(true) {
            $rataRata >= 4.5 => 'sangat_baik',
            $rataRata >= 3.5 => 'baik',
            $rataRata >= 2.5 => 'cukup',
            default => 'perlu_perbaikan',
        };

        return [
            'petugas_id' => User::factory(),
            'supervisor_id' => User::factory(),
            'periode_bulan' => fake()->numberBetween(1, 12),
            'periode_tahun' => fake()->numberBetween(2024, 2025),
            'skor_kehadiran' => $skorKehadiran,
            'skor_kualitas' => $skorKualitas,
            'skor_ketepatan_waktu' => $skorKetepatanWaktu,
            'skor_kebersihan' => $skorKebersihan,
            'rata_rata' => round($rataRata, 2),
            'kategori' => $kategori,
            'catatan' => fake()->optional()->sentence(),
        ];
    }
}
