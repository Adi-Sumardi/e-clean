<?php

namespace Tests\Unit\Models;

use App\Models\JadwalKebersihan;
use App\Models\LaporanKeterlambatan;
use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanKeterlambatanTest extends TestCase
{
    use RefreshDatabase;

    private function createLaporan(array $overrides = []): LaporanKeterlambatan
    {
        $petugas = User::factory()->create();
        $lokasi = Lokasi::factory()->create();
        $jadwal = JadwalKebersihan::factory()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
        ]);

        return LaporanKeterlambatan::create(array_merge([
            'jadwal_kebersihan_id' => $jadwal->id,
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'tanggal' => now()->toDateString(),
            'shift' => 'pagi',
            'batas_waktu_mulai' => '05:00',
            'batas_waktu_selesai' => '08:00',
            'status' => 'terlewat',
            'waktu_terdeteksi' => now(),
        ], $overrides));
    }

    public function test_laporan_belongs_to_jadwal_kebersihan(): void
    {
        $laporan = $this->createLaporan();

        $this->assertInstanceOf(JadwalKebersihan::class, $laporan->jadwalKebersihan);
    }

    public function test_laporan_belongs_to_petugas(): void
    {
        $laporan = $this->createLaporan();

        $this->assertInstanceOf(User::class, $laporan->petugas);
    }

    public function test_laporan_belongs_to_lokasi(): void
    {
        $laporan = $this->createLaporan();

        $this->assertInstanceOf(Lokasi::class, $laporan->lokasi);
    }

    public function test_get_shift_time_ranges_includes_all_shifts(): void
    {
        $ranges = LaporanKeterlambatan::getShiftTimeRanges();

        $this->assertArrayHasKey('pagi', $ranges);
        $this->assertArrayHasKey('standby', $ranges);
        $this->assertArrayHasKey('siang', $ranges);
        $this->assertArrayHasKey('sweeping', $ranges);
        $this->assertArrayHasKey('sore', $ranges);
        $this->assertCount(5, $ranges);
    }

    public function test_get_shift_time_range_pagi(): void
    {
        $range = LaporanKeterlambatan::getShiftTimeRange('pagi');

        $this->assertEquals('05:30', $range['start']);
        $this->assertEquals('07:30', $range['end']);
    }

    public function test_get_shift_time_range_standby_not_zero(): void
    {
        $range = LaporanKeterlambatan::getShiftTimeRange('standby');

        $this->assertEquals('07:30', $range['start']);
        $this->assertEquals('09:30', $range['end']);
        // Bug fix: sebelumnya return 00:00 karena standby tidak ada di mapping
    }

    public function test_get_shift_time_range_sweeping(): void
    {
        $range = LaporanKeterlambatan::getShiftTimeRange('sweeping');

        $this->assertEquals('13:00', $range['start']);
        $this->assertEquals('14:00', $range['end']);
    }

    public function test_get_shift_time_range_for_unknown_shift(): void
    {
        $range = LaporanKeterlambatan::getShiftTimeRange('malam');

        $this->assertEquals('00:00', $range['start']);
        $this->assertEquals('00:00', $range['end']);
    }

    public function test_laporan_has_correct_table_name(): void
    {
        $laporan = new LaporanKeterlambatan();
        $this->assertEquals('laporan_keterlambatan', $laporan->getTable());
    }

    public function test_laporan_casts_dates_correctly(): void
    {
        $laporan = $this->createLaporan();
        $laporan->refresh();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $laporan->tanggal);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $laporan->waktu_terdeteksi);
    }
}
