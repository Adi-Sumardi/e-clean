<?php

namespace Database\Factories;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityReportFactory extends Factory
{
    protected $model = ActivityReport::class;

    public function definition(): array
    {
        $shift = fake()->randomElement(['pagi', 'siang', 'sore']);
        $tanggal = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'jadwal_kebersihan_id' => JadwalKebersihan::factory(),
            'petugas_id' => User::factory(),
            'lokasi_id' => Lokasi::factory(),
            'tanggal' => $tanggal,
            'shift' => $shift,
            'jam_mulai' => fake()->time('H:i'),
            'jam_selesai' => fake()->time('H:i'),
            'kegiatan' => fake()->sentence(),
            'kondisi_awal' => fake()->randomElement(['Kotor', 'Cukup Bersih', 'Perlu Perhatian']),
            'kondisi_akhir' => fake()->randomElement(['Bersih', 'Sangat Bersih', 'Rapi']),
            'foto_sebelum' => 'photos/' . fake()->uuid() . '.jpg',
            'foto_sesudah' => 'photos/' . fake()->uuid() . '.jpg',
            'gps_latitude' => fake()->latitude(-7, -5),
            'gps_longitude' => fake()->longitude(106, 108),
            'gps_accuracy' => fake()->randomFloat(2, 5, 50),
            'status' => fake()->randomElement(['draft', 'submitted', 'approved', 'rejected']),
            'rating' => fake()->optional(0.7)->numberBetween(3, 5),
            'catatan_supervisor' => fake()->optional()->sentence(),
            'rejected_reason' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'rating' => null,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'rating' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'rating' => fake()->numberBetween(3, 5),
            'catatan_supervisor' => fake()->sentence(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rating' => null,
            'rejected_reason' => fake()->sentence(),
        ]);
    }
}
