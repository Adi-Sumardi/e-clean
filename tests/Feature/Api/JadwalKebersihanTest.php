<?php

namespace Tests\Feature\Api;

use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JadwalKebersihanTest extends TestCase
{
    use RefreshDatabase;

    protected User $petugas;
    protected User $admin;
    protected Lokasi $lokasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->petugas = User::factory()->create();
        $this->petugas->assignRole('petugas');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->lokasi = Lokasi::factory()->create();
    }

    public function test_petugas_can_view_their_schedules(): void
    {
        Sanctum::actingAs($this->petugas);

        JadwalKebersihan::factory()->count(3)->create([
            'petugas_id' => $this->petugas->id,
            'lokasi_id' => $this->lokasi->id,
        ]);

        $response = $this->getJson('/api/v1/jadwal');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'petugas_id', 'lokasi_id', 'tanggal', 'shift'],
                ],
            ]);
    }

    public function test_petugas_can_view_today_schedules(): void
    {
        Sanctum::actingAs($this->petugas);

        JadwalKebersihan::factory()->create([
            'petugas_id' => $this->petugas->id,
            'lokasi_id' => $this->lokasi->id,
            'tanggal' => today(),
        ]);

        $response = $this->getJson('/api/v1/jadwal/today');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'tanggal', 'shift'],
                ],
            ]);
    }

    public function test_petugas_can_view_upcoming_schedules(): void
    {
        Sanctum::actingAs($this->petugas);

        JadwalKebersihan::factory()->create([
            'petugas_id' => $this->petugas->id,
            'lokasi_id' => $this->lokasi->id,
            'tanggal' => today()->addDays(1),
        ]);

        $response = $this->getJson('/api/v1/jadwal/upcoming');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'tanggal', 'shift'],
                ],
            ]);
    }

    public function test_petugas_can_view_schedule_details(): void
    {
        Sanctum::actingAs($this->petugas);

        $jadwal = JadwalKebersihan::factory()->create([
            'petugas_id' => $this->petugas->id,
            'lokasi_id' => $this->lokasi->id,
        ]);

        $response = $this->getJson("/api/v1/jadwal/{$jadwal->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'petugas_id', 'lokasi', 'tanggal', 'shift'],
            ]);
    }

    public function test_petugas_cannot_view_other_petugas_schedule(): void
    {
        Sanctum::actingAs($this->petugas);

        $otherPetugas = User::factory()->create();
        $otherPetugas->assignRole('petugas');

        $jadwal = JadwalKebersihan::factory()->create([
            'petugas_id' => $otherPetugas->id,
            'lokasi_id' => $this->lokasi->id,
        ]);

        $response = $this->getJson("/api/v1/jadwal/{$jadwal->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_all_schedules(): void
    {
        Sanctum::actingAs($this->admin);

        JadwalKebersihan::factory()->count(5)->create([
            'lokasi_id' => $this->lokasi->id,
        ]);

        $response = $this->getJson('/api/v1/jadwal');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }
}
