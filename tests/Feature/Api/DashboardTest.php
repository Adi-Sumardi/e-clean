<?php

namespace Tests\Feature\Api;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\Penilaian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $petugas;
    protected User $supervisor;
    protected Lokasi $lokasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = User::factory()->create();
        $this->petugas->assignRole('petugas');

        $this->supervisor = User::factory()->create();
        $this->supervisor->assignRole('supervisor');

        $this->lokasi = Lokasi::factory()->create();
    }

    public function test_petugas_can_view_their_dashboard(): void
    {
        Sanctum::actingAs($this->petugas);

        JadwalKebersihan::factory()->create([
            'petugas_id' => $this->petugas->id,
            'lokasi_id' => $this->lokasi->id,
            'tanggal' => today(),
        ]);

        ActivityReport::factory()->count(3)->create([
            'petugas_id' => $this->petugas->id,
            'tanggal' => today(),
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_info',
                    'today',
                    'monthly_stats',
                    'pending_tasks',
                ],
            ]);
    }

    public function test_supervisor_can_view_admin_dashboard(): void
    {
        Sanctum::actingAs($this->supervisor);

        JadwalKebersihan::factory()->count(5)->create([
            'lokasi_id' => $this->lokasi->id,
            'tanggal' => today(),
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_info',
                    'overview',
                    'today',
                    'monthly_stats',
                    'top_performers',
                    'recent_reports',
                ],
            ]);
    }

    public function test_dashboard_statistics_endpoint(): void
    {
        Sanctum::actingAs($this->petugas);

        ActivityReport::factory()->count(10)->create([
            'petugas_id' => $this->petugas->id,
            'tanggal' => today()->subDays(rand(1, 30)),
            'status' => 'approved',
            'rating' => rand(3, 5),
        ]);

        $response = $this->getJson('/api/v1/dashboard/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'reports_trend',
                    'late_submissions_trend',
                    'reports_by_status',
                    'rating_trend',
                ],
            ]);
    }

    public function test_leaderboard_endpoint(): void
    {
        Sanctum::actingAs($this->supervisor);

        // Create multiple petugas with reports
        for ($i = 0; $i < 5; $i++) {
            $petugas = User::factory()->create();
            $petugas->assignRole('petugas');

            ActivityReport::factory()->count(rand(5, 10))->create([
                'petugas_id' => $petugas->id,
                'status' => 'approved',
                'rating' => rand(3, 5),
            ]);
        }

        $response = $this->getJson('/api/v1/dashboard/leaderboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'leaderboard' => [
                        '*' => [
                            'rank',
                            'petugas_id',
                            'name',
                            'total_reports',
                            'average_rating',
                            'overall_score',
                        ],
                    ],
                ],
            ]);
    }

    public function test_dashboard_shows_correct_monthly_stats(): void
    {
        Sanctum::actingAs($this->petugas);

        // Create reports for this month
        ActivityReport::factory()->count(5)->create([
            'petugas_id' => $this->petugas->id,
            'tanggal' => today(),
            'status' => 'approved',
            'rating' => 4,
        ]);

        // Create reports for last month (should not be included)
        ActivityReport::factory()->count(3)->create([
            'petugas_id' => $this->petugas->id,
            'tanggal' => today()->subMonth(),
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200);

        $monthlyStats = $response->json('data.monthly_stats');
        $this->assertEquals(5, $monthlyStats['reports']['total']);
    }
}
