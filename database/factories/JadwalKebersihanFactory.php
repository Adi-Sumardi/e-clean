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
        $shift = fake()->randomElement(['pagi', 'siang', 'sore']);

        $shiftTimes = [
            'pagi' => ['05:00', '08:00'],
            'siang' => ['10:00', '14:00'],
            'sore' => ['15:00', '18:00'],
        ];

        return [
            'petugas_id' => User::factory(),
            'lokasi_id' => Lokasi::factory(),
            'tanggal' => fake()->dateTimeBetween('-30 days', '+30 days'),
            'shift' => $shift,
            'jam_mulai' => $shiftTimes[$shift][0],
            'jam_selesai' => $shiftTimes[$shift][1],
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
