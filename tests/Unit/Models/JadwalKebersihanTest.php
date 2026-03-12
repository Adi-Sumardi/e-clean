<?php

namespace Tests\Unit\Models;

use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalKebersihanTest extends TestCase
{
    use RefreshDatabase;

    public function test_jadwal_belongs_to_petugas(): void
    {
        $petugas = User::factory()->create();
        $jadwal = JadwalKebersihan::factory()->create([
            'petugas_id' => $petugas->id,
        ]);

        $this->assertInstanceOf(User::class, $jadwal->petugas);
        $this->assertEquals($petugas->id, $jadwal->petugas->id);
    }

    public function test_jadwal_belongs_to_lokasi(): void
    {
        $lokasi = Lokasi::factory()->create();
        $jadwal = JadwalKebersihan::factory()->create([
            'lokasi_id' => $lokasi->id,
        ]);

        $this->assertInstanceOf(Lokasi::class, $jadwal->lokasi);
        $this->assertEquals($lokasi->id, $jadwal->lokasi->id);
    }

    public function test_jadwal_belongs_to_creator(): void
    {
        $creator = User::factory()->create();
        $jadwal = JadwalKebersihan::factory()->create([
            'created_by' => $creator->id,
        ]);

        $this->assertInstanceOf(User::class, $jadwal->creator);
        $this->assertEquals($creator->id, $jadwal->creator->id);
    }

    public function test_jadwal_has_correct_table_name(): void
    {
        $jadwal = new JadwalKebersihan();
        $this->assertEquals('jadwal_kebersihanans', $jadwal->getTable());
    }

    public function test_jadwal_has_correct_fillable_attributes(): void
    {
        $jadwal = new JadwalKebersihan();

        $expected = [
            'petugas_id',
            'lokasi_id',
            'tanggal',
            'shift',
            'jam_mulai',
            'jam_selesai',
            'prioritas',
            'catatan',
            'status',
            'created_by',
        ];

        $this->assertEquals($expected, $jadwal->getFillable());
    }

    public function test_jadwal_casts_tanggal_as_date(): void
    {
        $jadwal = JadwalKebersihan::factory()->create([
            'tanggal' => '2026-03-12',
        ]);

        $jadwal->refresh();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $jadwal->tanggal);
        $this->assertEquals('2026-03-12', $jadwal->tanggal->format('Y-m-d'));
    }

    public function test_jadwal_uses_soft_deletes(): void
    {
        $jadwal = JadwalKebersihan::factory()->create();
        $jadwal->delete();

        $this->assertSoftDeleted($jadwal);
    }
}
