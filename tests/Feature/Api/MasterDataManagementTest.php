<?php

namespace Tests\Feature\Api;

use App\Models\Lokasi;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * CRUD + authorization for the unit and lokasi master-data endpoints.
 */
class MasterDataManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $petugas;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public'); // QR generation on Lokasi::created writes here

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
        $this->petugas = User::factory()->create();
        $this->petugas->assignRole('petugas');
    }

    /* -------------------------------------------------------------- units */

    public function test_admin_can_create_unit(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/units', [
            'kode_unit' => 'OFC',
            'nama_unit' => 'Office Kopkar',
        ])->assertStatus(201)->assertJsonPath('data.kode_unit', 'OFC');

        $this->assertDatabaseHas('units', ['kode_unit' => 'OFC']);
    }

    public function test_petugas_cannot_create_unit(): void
    {
        Sanctum::actingAs($this->petugas);

        $this->postJson('/api/v1/units', ['kode_unit' => 'X', 'nama_unit' => 'X'])
            ->assertStatus(403);
    }

    public function test_admin_can_update_and_delete_unit(): void
    {
        $unit = Unit::create(['kode_unit' => 'U1', 'nama_unit' => 'Unit 1', 'is_active' => true]);
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/units/{$unit->id}", ['nama_unit' => 'Unit Satu'])
            ->assertStatus(200)->assertJsonPath('data.nama_unit', 'Unit Satu');

        $this->deleteJson("/api/v1/units/{$unit->id}")->assertStatus(200);
        $this->assertSoftDeleted('units', ['id' => $unit->id]);
    }

    public function test_cannot_delete_unit_with_locations(): void
    {
        $unit = Unit::create(['kode_unit' => 'U2', 'nama_unit' => 'Unit 2', 'is_active' => true]);
        Lokasi::factory()->create(['unit_id' => $unit->id]);
        Sanctum::actingAs($this->admin);

        $this->deleteJson("/api/v1/units/{$unit->id}")->assertStatus(422);
    }

    /* ------------------------------------------------------------- lokasi */

    public function test_admin_can_create_lokasi(): void
    {
        $unit = Unit::create(['kode_unit' => 'U3', 'nama_unit' => 'Unit 3', 'is_active' => true]);
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/lokasi', [
            'unit_id' => $unit->id,
            'kode_lokasi' => 'LOK-NEW',
            'nama_lokasi' => 'Toilet Baru',
            'kategori' => 'toilet',
        ])->assertStatus(201)->assertJsonPath('data.kode_lokasi', 'LOK-NEW');

        $this->assertDatabaseHas('lokasis', ['kode_lokasi' => 'LOK-NEW']);
    }

    public function test_lokasi_code_must_be_unique(): void
    {
        $unit = Unit::create(['kode_unit' => 'U4', 'nama_unit' => 'Unit 4', 'is_active' => true]);
        Lokasi::factory()->create(['unit_id' => $unit->id, 'kode_lokasi' => 'DUP-1']);
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/lokasi', [
            'unit_id' => $unit->id,
            'kode_lokasi' => 'DUP-1',
            'nama_lokasi' => 'Dup',
            'kategori' => 'toilet',
        ])->assertStatus(422);
    }

    public function test_admin_can_update_and_delete_lokasi(): void
    {
        $unit = Unit::create(['kode_unit' => 'U5', 'nama_unit' => 'Unit 5', 'is_active' => true]);
        $lokasi = Lokasi::factory()->create(['unit_id' => $unit->id]);
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/lokasi/{$lokasi->id}", ['nama_lokasi' => 'Nama Baru'])
            ->assertStatus(200)->assertJsonPath('data.nama_lokasi', 'Nama Baru');

        $this->deleteJson("/api/v1/lokasi/{$lokasi->id}")->assertStatus(200);
        $this->assertSoftDeleted('lokasis', ['id' => $lokasi->id]);
    }

    public function test_petugas_cannot_manage_lokasi(): void
    {
        $unit = Unit::create(['kode_unit' => 'U6', 'nama_unit' => 'Unit 6', 'is_active' => true]);
        Sanctum::actingAs($this->petugas);

        $this->postJson('/api/v1/lokasi', [
            'unit_id' => $unit->id,
            'kode_lokasi' => 'NOPE',
            'nama_lokasi' => 'Nope',
            'kategori' => 'toilet',
        ])->assertStatus(403);
    }
}
