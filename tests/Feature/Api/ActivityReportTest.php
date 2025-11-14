<?php

namespace Tests\Feature\Api;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $petugas;
    protected User $supervisor;
    protected Lokasi $lokasi;
    protected JadwalKebersihan $jadwal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->petugas = User::factory()->create();
        $this->petugas->assignRole('petugas');

        $this->supervisor = User::factory()->create();
        $this->supervisor->assignRole('supervisor');

        $this->lokasi = Lokasi::factory()->create();

        $this->jadwal = JadwalKebersihan::factory()->create([
            'petugas_id' => $this->petugas->id,
            'lokasi_id' => $this->lokasi->id,
            'tanggal' => today(),
            'shift' => 'pagi',
        ]);
    }

    public function test_petugas_can_get_their_activity_reports(): void
    {
        Sanctum::actingAs($this->petugas);

        ActivityReport::factory()->count(3)->create([
            'petugas_id' => $this->petugas->id,
        ]);

        $response = $this->getJson('/api/v1/activity-reports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'petugas_id', 'lokasi_id', 'tanggal', 'status'],
                ],
            ]);
    }

    public function test_petugas_can_create_activity_report(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->petugas);

        $response = $this->postJson('/api/v1/activity-reports', [
            'jadwal_kebersihan_id' => $this->jadwal->id,
            'lokasi_id' => $this->lokasi->id,
            'tanggal' => today()->format('Y-m-d'),
            'shift' => 'pagi',
            'jam_mulai' => '05:30',
            'jam_selesai' => '07:30',
            'kegiatan' => 'Pembersihan ruang kelas',
            'kondisi_awal' => 'Kotor',
            'kondisi_akhir' => 'Bersih',
            'foto_sebelum' => UploadedFile::fake()->image('before.jpg'),
            'foto_sesudah' => UploadedFile::fake()->image('after.jpg'),
            'gps_latitude' => -6.200000,
            'gps_longitude' => 106.816666,
            'status' => 'draft',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'petugas_id', 'status'],
            ]);

        $this->assertDatabaseHas('activity_reports', [
            'petugas_id' => $this->petugas->id,
            'lokasi_id' => $this->lokasi->id,
            'status' => 'draft',
        ]);
    }

    public function test_petugas_can_submit_activity_report(): void
    {
        Sanctum::actingAs($this->petugas);

        $report = ActivityReport::factory()->create([
            'petugas_id' => $this->petugas->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/v1/activity-reports/{$report->id}", [
            'status' => 'submitted',
            '_method' => 'PUT',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_reports', [
            'id' => $report->id,
            'status' => 'submitted',
        ]);
    }

    public function test_supervisor_can_approve_activity_report(): void
    {
        Sanctum::actingAs($this->supervisor);

        $report = ActivityReport::factory()->create([
            'petugas_id' => $this->petugas->id,
            'status' => 'submitted',
        ]);

        $response = $this->postJson("/api/v1/activity-reports/{$report->id}", [
            'status' => 'approved',
            'rating' => 4,
            'catatan_supervisor' => 'Good work!',
            '_method' => 'PUT',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_reports', [
            'id' => $report->id,
            'status' => 'approved',
            'rating' => 4,
        ]);
    }

    public function test_supervisor_can_reject_activity_report(): void
    {
        Sanctum::actingAs($this->supervisor);

        $report = ActivityReport::factory()->create([
            'petugas_id' => $this->petugas->id,
            'status' => 'submitted',
        ]);

        $response = $this->postJson("/api/v1/activity-reports/{$report->id}", [
            'status' => 'rejected',
            'rejected_reason' => 'Incomplete photos',
            '_method' => 'PUT',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_reports', [
            'id' => $report->id,
            'status' => 'rejected',
        ]);
    }

    public function test_petugas_can_get_activity_report_statistics(): void
    {
        Sanctum::actingAs($this->petugas);

        ActivityReport::factory()->count(5)->create([
            'petugas_id' => $this->petugas->id,
            'status' => 'approved',
            'rating' => 4,
        ]);

        $response = $this->getJson('/api/v1/activity-reports/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_reports',
                    'by_status',
                    'average_rating',
                ],
            ]);
    }

    public function test_petugas_cannot_delete_approved_report(): void
    {
        Sanctum::actingAs($this->petugas);

        $report = ActivityReport::factory()->create([
            'petugas_id' => $this->petugas->id,
            'status' => 'approved',
        ]);

        $response = $this->deleteJson("/api/v1/activity-reports/{$report->id}");

        $response->assertStatus(403);
    }

    public function test_bulk_submit_activity_reports(): void
    {
        Sanctum::actingAs($this->petugas);

        $reports = ActivityReport::factory()->count(3)->create([
            'petugas_id' => $this->petugas->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson('/api/v1/activity-reports/bulk-submit', [
            'report_ids' => $reports->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200);

        foreach ($reports as $report) {
            $this->assertDatabaseHas('activity_reports', [
                'id' => $report->id,
                'status' => 'submitted',
            ]);
        }
    }
}
