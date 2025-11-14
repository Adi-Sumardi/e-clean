<?php

namespace Tests\Unit\Models;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_report_belongs_to_petugas(): void
    {
        $petugas = User::factory()->create();
        $report = ActivityReport::factory()->create([
            'petugas_id' => $petugas->id,
        ]);

        $this->assertInstanceOf(User::class, $report->petugas);
        $this->assertEquals($petugas->id, $report->petugas->id);
    }

    public function test_activity_report_belongs_to_lokasi(): void
    {
        $lokasi = Lokasi::factory()->create();
        $report = ActivityReport::factory()->create([
            'lokasi_id' => $lokasi->id,
        ]);

        $this->assertInstanceOf(Lokasi::class, $report->lokasi);
        $this->assertEquals($lokasi->id, $report->lokasi->id);
    }

    public function test_activity_report_belongs_to_jadwal(): void
    {
        $jadwal = JadwalKebersihan::factory()->create();
        $report = ActivityReport::factory()->create([
            'jadwal_kebersihan_id' => $jadwal->id,
        ]);

        $this->assertInstanceOf(JadwalKebersihan::class, $report->jadwalKebersihan);
        $this->assertEquals($jadwal->id, $report->jadwalKebersihan->id);
    }

    public function test_activity_report_can_be_approved(): void
    {
        $report = ActivityReport::factory()->create([
            'status' => 'submitted',
        ]);

        $report->update([
            'status' => 'approved',
            'rating' => 5,
        ]);

        $this->assertEquals('approved', $report->status);
        $this->assertEquals(5, $report->rating);
    }

    public function test_activity_report_can_be_rejected(): void
    {
        $report = ActivityReport::factory()->create([
            'status' => 'submitted',
        ]);

        $report->update([
            'status' => 'rejected',
            'rejected_reason' => 'Photos not clear',
        ]);

        $this->assertEquals('rejected', $report->status);
        $this->assertNotNull($report->rejected_reason);
    }

    public function test_activity_report_has_correct_fillable_attributes(): void
    {
        $fillable = (new ActivityReport())->getFillable();

        $this->assertContains('petugas_id', $fillable);
        $this->assertContains('lokasi_id', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('rating', $fillable);
        $this->assertContains('foto_sebelum', $fillable);
        $this->assertContains('foto_sesudah', $fillable);
    }

    public function test_activity_report_casts_dates_correctly(): void
    {
        $report = ActivityReport::factory()->create([
            'tanggal' => '2025-11-13',
            'jam_mulai' => '05:30',
            'jam_selesai' => '07:30',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $report->tanggal);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $report->jam_mulai);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $report->jam_selesai);
    }
}
