<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityReportResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create petugas user
        $this->petugas = User::factory()->create();
        $this->petugas->assignRole('petugas');
    }

    /** @test */
    public function petugas_can_create_activity_report()
    {
        $lokasi = Lokasi::factory()->create();

        $reportData = [
            'lokasi_id' => $lokasi->id,
            'petugas_id' => $this->petugas->id,
            'tanggal' => now()->format('Y-m-d'),
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:00',
            'kegiatan' => 'Membersihkan ruangan dengan sapu dan pel',
            'status' => 'draft',
        ];

        $this->actingAs($this->petugas);

        $report = ActivityReport::create($reportData);

        $this->assertDatabaseHas('activity_reports', [
            'lokasi_id' => $lokasi->id,
            'petugas_id' => $this->petugas->id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function admin_can_approve_activity_report()
    {
        $report = ActivityReport::factory()->create([
            'petugas_id' => $this->petugas->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($this->admin);

        $report->update([
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $this->assertEquals('approved', $report->fresh()->status);
        $this->assertEquals($this->admin->id, $report->fresh()->approved_by);
    }

    /** @test */
    public function activity_report_belongs_to_petugas()
    {
        $report = ActivityReport::factory()->create([
            'petugas_id' => $this->petugas->id,
        ]);

        $this->assertInstanceOf(User::class, $report->petugas);
        $this->assertEquals($this->petugas->id, $report->petugas->id);
    }

    /** @test */
    public function activity_report_belongs_to_lokasi()
    {
        $lokasi = Lokasi::factory()->create();

        $report = ActivityReport::factory()->create([
            'lokasi_id' => $lokasi->id,
        ]);

        $this->assertInstanceOf(Lokasi::class, $report->lokasi);
        $this->assertEquals($lokasi->id, $report->lokasi->id);
    }

    /** @test */
    public function activity_report_status_can_be_filtered()
    {
        ActivityReport::factory()->create(['status' => 'approved']);
        ActivityReport::factory()->create(['status' => 'pending']);
        ActivityReport::factory()->create(['status' => 'rejected']);

        $approved = ActivityReport::where('status', 'approved')->count();
        $pending = ActivityReport::where('status', 'pending')->count();
        $rejected = ActivityReport::where('status', 'rejected')->count();

        $this->assertEquals(1, $approved);
        $this->assertEquals(1, $pending);
        $this->assertEquals(1, $rejected);
    }
}
