<?php

namespace Tests\Unit\Models;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LokasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lokasi_has_many_jadwal_kebersihanans(): void
    {
        $lokasi = Lokasi::factory()->create();
        JadwalKebersihan::factory()->count(3)->create(['lokasi_id' => $lokasi->id]);

        $this->assertCount(3, $lokasi->jadwalKebersihanans);
    }

    public function test_lokasi_has_many_activity_reports(): void
    {
        $lokasi = Lokasi::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $lokasi->activityReports());
    }

    public function test_lokasi_belongs_to_unit(): void
    {
        $unit = Unit::create([
            'kode_unit' => 'UNIT-001',
            'nama_unit' => 'Unit Test',
            'is_active' => true,
        ]);

        $lokasi = Lokasi::factory()->create(['unit_id' => $unit->id]);

        $this->assertInstanceOf(Unit::class, $lokasi->unit);
        $this->assertEquals($unit->id, $lokasi->unit->id);
    }

    public function test_lokasi_has_correct_fillable_attributes(): void
    {
        $lokasi = new Lokasi();

        $this->assertContains('kode_lokasi', $lokasi->getFillable());
        $this->assertContains('nama_lokasi', $lokasi->getFillable());
        $this->assertContains('kategori', $lokasi->getFillable());
        $this->assertContains('is_active', $lokasi->getFillable());
        $this->assertContains('unit_id', $lokasi->getFillable());
    }

    public function test_lokasi_casts_is_active_as_boolean(): void
    {
        $lokasi = Lokasi::factory()->create(['is_active' => 1]);
        $lokasi->refresh();

        $this->assertIsBool($lokasi->is_active);
        $this->assertTrue($lokasi->is_active);
    }

    public function test_lokasi_uses_soft_deletes(): void
    {
        $lokasi = Lokasi::factory()->create();
        $lokasi->delete();

        $this->assertSoftDeleted($lokasi);
    }

    public function test_lokasi_inactive_factory_state(): void
    {
        $lokasi = Lokasi::factory()->inactive()->create();

        $this->assertFalse($lokasi->is_active);
    }
}
