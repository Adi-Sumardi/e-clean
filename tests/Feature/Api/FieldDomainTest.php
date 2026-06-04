<?php

namespace Tests\Feature\Api;

use App\Models\LaporanSatpam;
use App\Models\Lokasi;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Covers the satpam / office-boy / store field domains: create (owner only),
 * own-scoping, per-unit filtering and the supervisor approval workflow.
 */
class FieldDomainTest extends TestCase
{
    use RefreshDatabase;

    protected User $satpam;
    protected User $ob;
    protected User $toko;
    protected User $supervisor;
    protected Unit $unitA;
    protected Unit $unitB;
    protected Lokasi $lokasiA;
    protected Lokasi $lokasiB;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(); // never hit the real Expo push endpoint

        $this->satpam = User::factory()->create();
        $this->satpam->assignRole('satpam');
        $this->ob = User::factory()->create();
        $this->ob->assignRole('office_boy');
        $this->toko = User::factory()->create();
        $this->toko->assignRole('petugas_toko');
        $this->supervisor = User::factory()->create();
        $this->supervisor->assignRole('supervisor');

        $this->unitA = Unit::create(['kode_unit' => 'UA', 'nama_unit' => 'Unit A', 'is_active' => true]);
        $this->unitB = Unit::create(['kode_unit' => 'UB', 'nama_unit' => 'Unit B', 'is_active' => true]);
        $this->lokasiA = Lokasi::factory()->create(['unit_id' => $this->unitA->id]);
        $this->lokasiB = Lokasi::factory()->create(['unit_id' => $this->unitB->id]);
    }

    private function satpamPayload(array $overrides = []): array
    {
        return array_merge([
            'lokasi_id' => $this->lokasiA->id,
            'tanggal' => today()->toDateString(),
            'jam_mulai' => '08:00',
            'kondisi' => 'aman',
            'temuan' => 'Area aman terkendali',
            'status' => 'submitted',
        ], $overrides);
    }

    public function test_satpam_can_create_patrol_report(): void
    {
        Sanctum::actingAs($this->satpam);

        $this->postJson('/api/v1/satpam/laporan', $this->satpamPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.kondisi', 'aman')
            ->assertJsonPath('data.petugas.id', $this->satpam->id);

        $this->assertDatabaseHas('laporan_satpam', [
            'petugas_id' => $this->satpam->id,
            'lokasi_id' => $this->lokasiA->id,
            'status' => 'submitted',
        ]);
    }

    public function test_non_owner_cannot_create_patrol_report(): void
    {
        Sanctum::actingAs($this->ob); // office boy is not satpam

        $this->postJson('/api/v1/satpam/laporan', $this->satpamPayload())
            ->assertStatus(403);
    }

    public function test_satpam_only_sees_own_reports(): void
    {
        $other = User::factory()->create();
        $other->assignRole('satpam');

        LaporanSatpam::create($this->satpamPayload(['petugas_id' => $this->satpam->id]));
        LaporanSatpam::create($this->satpamPayload(['petugas_id' => $other->id]));

        Sanctum::actingAs($this->satpam);

        $this->getJson('/api/v1/satpam/laporan')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_supervisor_can_filter_reports_by_unit(): void
    {
        LaporanSatpam::create($this->satpamPayload(['petugas_id' => $this->satpam->id, 'lokasi_id' => $this->lokasiA->id]));
        LaporanSatpam::create($this->satpamPayload(['petugas_id' => $this->satpam->id, 'lokasi_id' => $this->lokasiB->id]));

        Sanctum::actingAs($this->supervisor);

        $this->getJson('/api/v1/satpam/laporan?unit_id=' . $this->unitA->id)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.unit.id', $this->unitA->id);
    }

    public function test_supervisor_can_approve_report(): void
    {
        $report = LaporanSatpam::create($this->satpamPayload(['petugas_id' => $this->satpam->id]));

        Sanctum::actingAs($this->supervisor);

        $this->postJson("/api/v1/satpam/laporan/{$report->id}/approve", ['rating' => 5])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('laporan_satpam', [
            'id' => $report->id,
            'status' => 'approved',
            'approved_by' => $this->supervisor->id,
        ]);
    }

    public function test_supervisor_can_reject_report_with_reason(): void
    {
        $report = LaporanSatpam::create($this->satpamPayload(['petugas_id' => $this->satpam->id]));

        Sanctum::actingAs($this->supervisor);

        $this->postJson("/api/v1/satpam/laporan/{$report->id}/reject", ['rejected_reason' => 'Foto kurang jelas'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejected_reason', 'Foto kurang jelas');
    }

    public function test_reject_requires_reason(): void
    {
        $report = LaporanSatpam::create($this->satpamPayload(['petugas_id' => $this->satpam->id]));

        Sanctum::actingAs($this->supervisor);

        $this->postJson("/api/v1/satpam/laporan/{$report->id}/reject", [])
            ->assertStatus(422);
    }

    public function test_satpam_cannot_approve_reports(): void
    {
        $report = LaporanSatpam::create($this->satpamPayload(['petugas_id' => $this->satpam->id]));

        Sanctum::actingAs($this->satpam);

        $this->postJson("/api/v1/satpam/laporan/{$report->id}/approve")
            ->assertStatus(403);
    }

    public function test_office_boy_can_create_report(): void
    {
        Sanctum::actingAs($this->ob);

        $this->postJson('/api/v1/office-boy/laporan', [
            'lokasi_id' => $this->lokasiA->id,
            'tanggal' => today()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:00',
            'jenis_pekerjaan' => 'Setup Ruang Rapat',
            'uraian' => 'Setup selesai',
            'status' => 'submitted',
        ])->assertStatus(201);

        $this->assertDatabaseHas('laporan_ob', ['petugas_id' => $this->ob->id]);
    }

    public function test_petugas_toko_can_create_report(): void
    {
        Sanctum::actingAs($this->toko);

        $this->postJson('/api/v1/toko/laporan', [
            'lokasi_id' => $this->lokasiA->id,
            'tanggal' => today()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '16:00',
            'kondisi_stok' => 'aman',
            'catatan_stok' => 'Transaksi: 12; Omset: 500000',
            'status' => 'submitted',
        ])->assertStatus(201);

        $this->assertDatabaseHas('laporan_toko', ['petugas_id' => $this->toko->id]);
    }

    public function test_field_today_schedule_endpoint_responds(): void
    {
        Sanctum::actingAs($this->satpam);

        $this->getJson('/api/v1/satpam/jadwal/today')->assertStatus(200);
    }
}
