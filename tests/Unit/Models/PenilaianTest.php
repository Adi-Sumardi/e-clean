<?php

namespace Tests\Unit\Models;

use App\Models\Penilaian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenilaianTest extends TestCase
{
    use RefreshDatabase;

    public function test_penilaian_belongs_to_petugas(): void
    {
        $petugas = User::factory()->create();
        $penilai = User::factory()->create();

        $penilaian = Penilaian::factory()->create([
            'petugas_id' => $petugas->id,
            'penilai_id' => $penilai->id,
        ]);

        $this->assertInstanceOf(User::class, $penilaian->petugas);
        $this->assertEquals($petugas->id, $penilaian->petugas->id);
    }

    public function test_penilaian_belongs_to_penilai(): void
    {
        $petugas = User::factory()->create();
        $penilai = User::factory()->create();

        $penilaian = Penilaian::factory()->create([
            'petugas_id' => $petugas->id,
            'penilai_id' => $penilai->id,
        ]);

        $this->assertInstanceOf(User::class, $penilaian->penilai);
        $this->assertEquals($penilai->id, $penilaian->penilai->id);
    }

    public function test_penilaian_has_correct_fillable_attributes(): void
    {
        $penilaian = new Penilaian();

        $expected = [
            'petugas_id',
            'penilai_id',
            'periode_bulan',
            'periode_tahun',
            'skor_kehadiran',
            'skor_kualitas',
            'skor_ketepatan_waktu',
            'skor_kebersihan',
            'total_skor',
            'rata_rata',
            'kategori',
            'catatan',
        ];

        $this->assertEquals($expected, $penilaian->getFillable());
    }

    public function test_penilaian_casts_decimal_fields(): void
    {
        $petugas = User::factory()->create();
        $penilai = User::factory()->create();

        $penilaian = Penilaian::factory()->create([
            'petugas_id' => $petugas->id,
            'penilai_id' => $penilai->id,
            'skor_kualitas' => 4.5,
            'skor_ketepatan_waktu' => 3.75,
            'skor_kebersihan' => 4.0,
        ]);

        $penilaian->refresh();

        $this->assertEquals('4.50', $penilaian->skor_kualitas);
        $this->assertEquals('3.75', $penilaian->skor_ketepatan_waktu);
        $this->assertEquals('4.00', $penilaian->skor_kebersihan);
    }

    public function test_penilaian_casts_periode_as_integer(): void
    {
        $petugas = User::factory()->create();
        $penilai = User::factory()->create();

        $penilaian = Penilaian::factory()->create([
            'petugas_id' => $petugas->id,
            'penilai_id' => $penilai->id,
            'periode_bulan' => 3,
            'periode_tahun' => 2026,
        ]);

        $penilaian->refresh();

        $this->assertIsInt($penilaian->periode_bulan);
        $this->assertIsInt($penilaian->periode_tahun);
        $this->assertEquals(3, $penilaian->periode_bulan);
        $this->assertEquals(2026, $penilaian->periode_tahun);
    }

    public function test_penilaian_uses_soft_deletes(): void
    {
        $petugas = User::factory()->create();
        $penilai = User::factory()->create();

        $penilaian = Penilaian::factory()->create([
            'petugas_id' => $petugas->id,
            'penilai_id' => $penilai->id,
        ]);

        $penilaian->delete();

        $this->assertSoftDeleted($penilaian);
        $this->assertNotNull(Penilaian::withTrashed()->find($penilaian->id));
    }
}