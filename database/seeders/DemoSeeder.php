<?php

namespace Database\Seeders;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\JadwalOb;
use App\Models\JadwalSatpam;
use App\Models\JadwalToko;
use App\Models\LaporanOb;
use App\Models\LaporanSatpam;
use App\Models\LaporanToko;
use App\Models\Lokasi;
use App\Models\Penilaian;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Realistic demo data across every domain (cleaning, satpam, OB, toko) so the
 * mobile app has data to display and reports waiting for supervisor approval.
 *
 * Idempotent: clears demo domain rows then recreates. Users use firstOrCreate.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Clear domain data (keep users + roles).
        foreach ([
            LaporanSatpam::class, LaporanOb::class, LaporanToko::class,
            JadwalSatpam::class, JadwalOb::class, JadwalToko::class,
            ActivityReport::class, JadwalKebersihan::class, Penilaian::class,
            Lokasi::class, Unit::class,
        ] as $model) {
            $model::query()->forceDelete();
        }

        $user = fn (string $email, string $name, string $role) => tap(
            User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password123'), 'is_active' => true, 'phone' => '08123456789']
            ),
            fn ($u) => $u->syncRoles([$role])
        );

        $admin = $user('admin@yapi.test', 'Admin Demo', 'super_admin');
        $supervisor = $user('spv@yapi.test', 'Dewi Supervisor', 'supervisor');
        $petugas1 = $user('petugas@yapi.test', 'Rahmat Hidayat', 'petugas');
        $petugas2 = $user('petugas2@yapi.test', 'Siti Nurhaliza', 'petugas');
        $satpam = $user('satpam@yapi.test', 'Budi Santoso', 'satpam');
        $ob = $user('ob@yapi.test', 'Andi Office Boy', 'office_boy');
        $toko = $user('toko@yapi.test', 'Eko Petugas Toko', 'petugas_toko');

        // Units
        $office = Unit::create(['kode_unit' => 'OFC-01', 'nama_unit' => 'Office Kopkar YAPI', 'alamat' => 'Jl. Yos Sudarso No.123, Jakarta', 'penanggung_jawab' => 'Dewi Supervisor', 'telepon' => '0211234567', 'is_active' => true]);
        $tokoUnit = Unit::create(['kode_unit' => 'TK-01', 'nama_unit' => 'Toko Kopkar YAPI', 'alamat' => 'Jl. Yos Sudarso No.125, Jakarta', 'penanggung_jawab' => 'Eko', 'telepon' => '0211234568', 'is_active' => true]);

        // Locations
        $defs = [
            [$office, 'LK-A01', 'Toilet Lantai 1 - Gedung A', 'toilet', 'Lantai 1'],
            [$office, 'LK-A02', 'Lobi Utama', 'lobi', 'Lantai 1'],
            [$office, 'LK-A03', 'Pantry Lantai 2', 'pantry', 'Lantai 2'],
            [$office, 'LK-A04', 'Ruang Rapat Besar', 'ruang_rapat', 'Lantai 3'],
            [$office, 'LK-A05', 'Pos Satpam Depan', 'pos', 'Lantai 1'],
            [$tokoUnit, 'TK-D01', 'Display Rak Toko', 'toko', 'Lantai 1'],
            [$tokoUnit, 'TK-K01', 'Area Kasir', 'kasir', 'Lantai 1'],
        ];
        $lokasi = [];
        foreach ($defs as [$unit, $kode, $nama, $kat, $lantai]) {
            $lokasi[$kode] = Lokasi::create([
                'unit_id' => $unit->id, 'kode_lokasi' => $kode, 'nama_lokasi' => $nama,
                'kategori' => $kat, 'lantai' => $lantai, 'is_active' => true,
            ]);
        }

        $today = Carbon::today();
        $foto = ['activity-reports/before/sample.jpg'];

        // Cleaning schedules + reports (petugas1 & 2)
        foreach (['LK-A01' => $petugas1, 'LK-A02' => $petugas1, 'LK-A03' => $petugas2, 'LK-A04' => $petugas2] as $kode => $p) {
            $jadwal = JadwalKebersihan::create([
                'petugas_id' => $p->id, 'lokasi_id' => $lokasi[$kode]->id, 'tanggal' => $today->toDateString(),
                'shift' => 'pagi', 'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'status' => 'active',
                'prioritas' => 'normal', 'created_by' => $admin->id,
            ]);
            // one submitted (awaiting approval), set via array index parity
            $isSubmitted = in_array($kode, ['LK-A01', 'LK-A03']);
            ActivityReport::create([
                'petugas_id' => $p->id, 'lokasi_id' => $lokasi[$kode]->id, 'jadwal_id' => $jadwal->id,
                'tanggal' => $today->toDateString(), 'jam_mulai' => '08:00', 'jam_selesai' => '09:30',
                'kegiatan' => 'Pembersihan rutin pagi: mopping, lap kaca, buang sampah, isi ulang sabun.',
                'foto_sebelum' => $foto, 'foto_sesudah' => ['activity-reports/after/sample.jpg'],
                'status' => $isSubmitted ? 'submitted' : 'approved',
                'rating' => $isSubmitted ? null : 5,
                'approved_by' => $isSubmitted ? null : $supervisor->id,
                'approved_at' => $isSubmitted ? null : now(),
                'catatan_supervisor' => $isSubmitted ? null : 'Bagus, rapi.',
            ]);
        }

        // Satpam
        $js = JadwalSatpam::create(['petugas_id' => $satpam->id, 'lokasi_id' => $lokasi['LK-A05']->id, 'tanggal' => $today->toDateString(), 'shift' => 'pagi', 'jam_mulai' => '06:00', 'jam_selesai' => '14:00', 'status' => 'pending', 'created_by' => $admin->id]);
        LaporanSatpam::create(['jadwal_id' => $js->id, 'petugas_id' => $satpam->id, 'lokasi_id' => $lokasi['LK-A05']->id, 'tanggal' => $today->toDateString(), 'jam_mulai' => '08:00', 'kondisi' => 'aman', 'temuan' => 'Area aman terkendali, semua pintu terkunci.', 'foto' => $foto, 'status' => 'submitted']);

        // Office Boy
        $jo = JadwalOb::create(['petugas_id' => $ob->id, 'lokasi_id' => $lokasi['LK-A03']->id, 'tanggal' => $today->toDateString(), 'shift' => 'pagi', 'jam_mulai' => '07:00', 'jam_selesai' => '15:00', 'status' => 'pending', 'created_by' => $admin->id]);
        LaporanOb::create(['jadwal_id' => $jo->id, 'petugas_id' => $ob->id, 'lokasi_id' => $lokasi['LK-A03']->id, 'tanggal' => $today->toDateString(), 'jam_mulai' => '07:00', 'jam_selesai' => '08:00', 'jenis_pekerjaan' => 'Setup Ruang Rapat', 'uraian' => 'Setup kursi & konsumsi untuk rapat direksi.', 'foto_sebelum' => $foto, 'foto_sesudah' => ['x.jpg'], 'status' => 'submitted']);

        // Petugas Toko
        $jt = JadwalToko::create(['petugas_id' => $toko->id, 'lokasi_id' => $lokasi['TK-K01']->id, 'tanggal' => $today->toDateString(), 'shift' => 'pagi', 'jam_mulai' => '08:00', 'jam_selesai' => '16:00', 'status' => 'pending', 'created_by' => $admin->id]);
        LaporanToko::create(['jadwal_id' => $jt->id, 'petugas_id' => $toko->id, 'lokasi_id' => $lokasi['TK-K01']->id, 'tanggal' => $today->toDateString(), 'jam_mulai' => '08:00', 'jam_selesai' => '16:00', 'kondisi_stok' => 'aman', 'catatan_stok' => 'Transaksi: 24; Omset: 1.250.000; Saldo kasir: 500.000', 'foto' => $foto, 'status' => 'submitted']);

        // Penilaian (last month for petugas)
        foreach ([$petugas1, $petugas2] as $p) {
            $scores = ['skor_kehadiran' => 90, 'skor_kualitas' => 85, 'skor_ketepatan_waktu' => 88, 'skor_kebersihan' => 92];
            $total = array_sum($scores);
            $rata = round($total / 4, 2);
            Penilaian::create(array_merge($scores, [
                'petugas_id' => $p->id, 'penilai_id' => $supervisor->id,
                'periode_bulan' => $today->month, 'periode_tahun' => $today->year,
                'total_skor' => $total, 'rata_rata' => $rata,
                'kategori' => $rata >= 85 ? 'Sangat Baik' : 'Baik',
                'catatan' => 'Kinerja konsisten dan rapi.',
            ]));
        }

        $this->command->info('Demo data: Unit=' . Unit::count() . ' Lokasi=' . Lokasi::count() . ' Jadwal=' . JadwalKebersihan::count() . ' Laporan=' . ActivityReport::count() . ' Penilaian=' . Penilaian::count());
        $this->command->info('Logins (password123): admin@yapi.test, spv@yapi.test, petugas@yapi.test, satpam@yapi.test, ob@yapi.test, toko@yapi.test');
    }
}
