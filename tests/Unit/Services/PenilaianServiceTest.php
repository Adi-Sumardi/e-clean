<?php

namespace Tests\Unit\Services;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\LaporanKeterlambatan;
use App\Models\Lokasi;
use App\Models\Penilaian;
use App\Models\User;
use App\Services\PenilaianService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenilaianServiceTest extends TestCase
{
    use RefreshDatabase;

    private PenilaianService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PenilaianService();
    }

    public function test_generate_penilaian_with_no_reports(): void
    {
        $petugas = User::factory()->create();
        $penilai = User::factory()->create();

        $penilaian = $this->service->generateOrUpdateMonthlyPenilaian(
            $petugas->id, 3, 2026, $penilai->id
        );

        $this->assertInstanceOf(Penilaian::class, $penilaian);
        $this->assertEquals($petugas->id, $penilaian->petugas_id);
        $this->assertEquals($penilai->id, $penilaian->penilai_id);
        $this->assertEquals(3, $penilaian->periode_bulan);
        $this->assertEquals(2026, $penilaian->periode_tahun);
        // With no reports, skor_kualitas = 0, skor_kebersihan = 0
        $this->assertEquals('0.00', $penilaian->skor_kualitas);
    }

    public function test_generate_penilaian_with_approved_reports(): void
    {
        $petugas = User::factory()->create();
        $penilai = User::factory()->create();
        $lokasi = Lokasi::factory()->create();

        $jadwal = JadwalKebersihan::factory()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'tanggal' => '2026-03-10',
        ]);

        // Create approved reports with ratings
        ActivityReport::factory()->count(3)->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'jadwal_id' => $jadwal->id,
            'tanggal' => '2026-03-10',
            'status' => 'approved',
            'rating' => 4,
        ]);

        $penilaian = $this->service->generateOrUpdateMonthlyPenilaian(
            $petugas->id, 3, 2026, $penilai->id
        );

        $this->assertEquals('4.00', $penilaian->skor_kualitas);
        $this->assertEquals('4.00', $penilaian->skor_kebersihan); // same as kualitas
        $this->assertNotNull($penilaian->kategori);
        $this->assertNotNull($penilaian->catatan);
    }

    public function test_generate_penilaian_updates_existing(): void
    {
        $petugas = User::factory()->create();
        $penilai = User::factory()->create();

        // First generation
        $first = $this->service->generateOrUpdateMonthlyPenilaian(
            $petugas->id, 3, 2026, $penilai->id
        );

        // Second generation - should update, not create new
        $second = $this->service->generateOrUpdateMonthlyPenilaian(
            $petugas->id, 3, 2026, $penilai->id
        );

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Penilaian::where('petugas_id', $petugas->id)->count());
    }

    public function test_update_penilaian_after_approval(): void
    {
        $petugas = User::factory()->create();
        $penilai = User::factory()->create();
        $lokasi = Lokasi::factory()->create();

        $report = ActivityReport::factory()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'tanggal' => '2026-03-10',
            'status' => 'approved',
            'approved_by' => $penilai->id,
            'rating' => 5,
        ]);

        $penilaian = $this->service->updatePenilaianAfterApproval($report);

        $this->assertInstanceOf(Penilaian::class, $penilaian);
        $this->assertEquals($petugas->id, $penilaian->petugas_id);
    }

    public function test_update_penilaian_returns_null_for_non_approved(): void
    {
        $report = ActivityReport::factory()->create([
            'status' => 'submitted',
            'approved_by' => null,
        ]);

        $result = $this->service->updatePenilaianAfterApproval($report);
        $this->assertNull($result);
    }

    public function test_kategori_sangat_baik(): void
    {
        $petugas = User::factory()->create();
        $penilai = User::factory()->create();
        $lokasi = Lokasi::factory()->create();

        // Create 1 schedule and 1 approved report with rating 5
        JadwalKebersihan::factory()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'tanggal' => '2026-03-10',
        ]);

        ActivityReport::factory()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'tanggal' => '2026-03-10',
            'status' => 'approved',
            'rating' => 5,
        ]);

        $penilaian = $this->service->generateOrUpdateMonthlyPenilaian(
            $petugas->id, 3, 2026, $penilai->id
        );

        $this->assertEquals('Sangat Baik', $penilaian->kategori);
    }
}
