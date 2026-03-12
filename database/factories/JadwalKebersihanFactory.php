<?php

namespace Database\Factories;

use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalKebersihanFactory extends Factory
{
    protected $model = JadwalKebersihan::class;

    public function definition(): array
    {
        $shiftEnum = fake()->randomElement(\App\Enums\WorkShift::cases());

        return [
            'petugas_id' => User::factory(),
            'lokasi_id' => Lokasi::factory(),
            'tanggal' => fake()->dateTimeBetween('-30 days', '+30 days'),
            'shift' => $shiftEnum->value,
            'jam_mulai' => $shiftEnum->jamMulai(),
            'jam_selesai' => $shiftEnum->jamSelesai(),
            'prioritas' => fake()->randomElement(['rendah', 'normal', 'tinggi']),
            'catatan' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array$attributes) => [
            'status' => 'inactive',
        ]);
    }
}
