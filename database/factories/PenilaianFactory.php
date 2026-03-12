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
        $skorKualitas = fake()->numberBetween(3, 5);
        $skorKetepatanWaktu = fake()->numberBetween(3, 5);
        $skorKebersihan = fake()->numberBetween(3, 5);
        $totalSkor = $skorKualitas + $skorKetepatanWaktu + $skorKebersihan;
        $rataRata = $totalSkor / 3;

        $kategori = match(true) {
            $rataRata >= 4.5 => 'Sangat Baik',
            $rataRata >= 3.5 => 'Baik',
            $rataRata >= 2.5 => 'Cukup',
            default => 'Kurang',
        };

        return [
            'petugas_id' => User::factory(),
            'penilai_id' => User::factory(),
            'periode_bulan' => fake()->numberBetween(1, 12),
            'periode_tahun' => fake()->numberBetween(2024, 2026),
            'skor_kehadiran' => 0,
            'skor_kualitas' => $skorKualitas,
            'skor_ketepatan_waktu' => $skorKetepatanWaktu,
            'skor_kebersihan' => $skorKebersihan,
            'total_skor' => round($totalSkor, 2),
            'rata_rata' => round($rataRata, 2),
            'kategori' => $kategori,
            'catatan' => fake()->optional()->sentence(),
        ];
    }
}
