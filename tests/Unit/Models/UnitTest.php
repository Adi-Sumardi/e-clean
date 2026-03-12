<?php

namespace Tests\Unit\Models;

use App\Models\Lokasi;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitTest extends TestCase
{
    use RefreshDatabase;

    private function createUnit(array $overrides = []): Unit
    {
        return Unit::create(array_merge([
            'kode_unit' => 'UNIT-001',
            'nama_unit' => 'Unit Gedung A',
            'deskripsi' => 'Gedung utama',
            'is_active' => true,
        ], $overrides));
    }

    public function test_unit_has_many_lokasis(): void
    {
        $unit = $this->createUnit();
        Lokasi::factory()->count(3)->create(['unit_id' => $unit->id]);

        $this->assertCount(3, $unit->lokasis);
    }

    public function test_unit_full_name_accessor(): void
    {
        $unit = $this->createUnit([
            'kode_unit' => 'UNIT-X01',
            'nama_unit' => 'Gedung Barat',
        ]);

        $this->assertEquals('[UNIT-X01] Gedung Barat', $unit->full_name);
    }

    public function test_unit_casts_is_active_as_boolean(): void
    {
        $unit = $this->createUnit(['is_active' => 1]);
        $unit->refresh();

        $this->assertIsBool($unit->is_active);
        $this->assertTrue($unit->is_active);
    }

    public function test_unit_has_correct_fillable_attributes(): void
    {
        $unit = new Unit();
        $fillable = $unit->getFillable();

        $this->assertContains('kode_unit', $fillable);
        $this->assertContains('nama_unit', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('telepon', $fillable);
    }

    public function test_unit_uses_soft_deletes(): void
    {
        $unit = $this->createUnit();
        $unit->delete();

        $this->assertSoftDeleted($unit);
    }
}
