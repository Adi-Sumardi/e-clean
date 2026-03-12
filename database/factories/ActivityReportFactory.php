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
        return [
            'jadwal_id' => JadwalKebersihan::factory(),
            'petugas_id' => User::factory(),
            'lokasi_id' => Lokasi::factory(),
            'tanggal' => fake()->dateTimeBetween('-30 days', 'now'),
            'jam_mulai' => fake()->time('H:i'),
            'jam_selesai' => fake()->time('H:i'),
            'kegiatan' => fake()->sentence(10),
            'foto_sebelum' => ['photos/' . fake()->uuid() . '.jpg'],
            'foto_sesudah' => ['photos/' . fake()->uuid() . '.jpg'],
            'status' => fake()->randomElement(['draft', 'submitted', 'approved', 'rejected']),
            'rating' => fake()->optional(0.7)->numberBetween(3, 5),
            'catatan_petugas' => fake()->optional()->sentence(),
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
