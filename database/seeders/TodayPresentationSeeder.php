<?php

namespace Database\Seeders;

use App\Models\ActivityReport;
use App\Models\GuestComplaint;
use App\Models\JadwalKebersihan;
use App\Models\JadwalOb;
use App\Models\JadwalSatpam;
use App\Models\JadwalToko;
use App\Models\LaporanKeterlambatan;
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
 * Data presentasi lengkap untuk hari ini.
 * Jalankan: php artisan db:seed --class=TodayPresentationSeeder
 * Idempotent — hapus data hari ini lalu recreate.
 */
class TodayPresentationSeeder extends Seeder
{
    // ─── Photo helpers ────────────────────────────────────────────────────────

    private static function kebFoto(string $key, int $idx = 1): array
    {
        return [
            "activity-reports/before/seed_keb_{$key}_{$idx}_sebelum.jpg",
            "activity-reports/before/seed_keb_{$key}_2_sebelum.jpg",
        ];
    }

    private static function kebFotoAfter(string $key, int $idx = 1): array
    {
        return [
            "activity-reports/after/seed_keb_{$key}_{$idx}_sesudah.jpg",
        ];
    }

    private static function satFoto(string $key, int $idx = 1): array
    {
        return ["laporan-satpam/seed_satpam_{$key}_{$idx}.jpg"];
    }

    private static function obFoto(string $key, int $idx = 1, string $type = 'sebelum'): array
    {
        $dir = $type === 'sebelum' ? 'before' : 'after';
        return ["laporan-ob/{$dir}/seed_ob_{$key}_{$idx}_{$type}.jpg"];
    }

    private static function tokoFoto(string $key, int $idx = 1): array
    {
        return ["laporan-toko/seed_toko_{$key}_{$idx}.jpg"];
    }

    private static function compFoto(string $key): string
    {
        return json_encode(["guest-complaints/seed_comp_{$key}.jpg"]);
    }

    // ─── User helper ──────────────────────────────────────────────────────────

    private function mkUser(string $email, string $name, string $role, string $phone): User
    {
        return tap(
            User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password123'), 'is_active' => true, 'phone' => $phone]
            ),
            fn ($u) => $u->syncRoles([$role])
        );
    }

    // ─── Run ─────────────────────────────────────────────────────────────────

    public function run(): void
    {
        $today = Carbon::today();
        $this->command->info("📅 Seeding presentasi: {$today->format('d F Y')}");

        // ── Users ─────────────────────────────────────────────────────────────
        $this->command->info('👥 Users...');

        $admin  = $this->mkUser('admin@yapi.test',   'Admin Demo',          'super_admin', '08111000001');
        $spv1   = $this->mkUser('spv@yapi.test',     'Dewi Rahayu',         'supervisor',  '08111000002');
        $this->mkUser('spv2@yapi.test',    'Anton Wijaya',        'supervisor',  '08111000003');

        // 5 petugas kebersihan
        $keb = [
            $this->mkUser('petugas@yapi.test',   'Rahmat Hidayat',   'petugas', '08111000010'),
            $this->mkUser('petugas2@yapi.test',  'Siti Nurhaliza',   'petugas', '08111000011'),
            $this->mkUser('petugas3@yapi.test',  'Rini Wahyuni',     'petugas', '08111000012'),
            $this->mkUser('petugas4@yapi.test',  'Joko Susanto',     'petugas', '08111000013'),
            $this->mkUser('petugas5@yapi.test',  'Maya Anggraini',   'petugas', '08111000014'),
        ];

        // 5 satpam
        $sat = [
            $this->mkUser('satpam@yapi.test',    'Budi Santoso',     'satpam',  '08111000020'),
            $this->mkUser('satpam2@yapi.test',   'Hendra Kurniawan', 'satpam',  '08111000021'),
            $this->mkUser('satpam3@yapi.test',   'Dodi Prasetya',    'satpam',  '08111000022'),
            $this->mkUser('satpam4@yapi.test',   'Agus Riyanto',     'satpam',  '08111000023'),
            $this->mkUser('satpam5@yapi.test',   'Fajar Nugroho',    'satpam',  '08111000024'),
        ];

        // 5 office boy
        $ob = [
            $this->mkUser('ob@yapi.test',        'Andi Prasetyo',    'office_boy', '08111000030'),
            $this->mkUser('ob2@yapi.test',       'Wahyu Setiawan',   'office_boy', '08111000031'),
            $this->mkUser('ob3@yapi.test',       'Tono Subarjo',     'office_boy', '08111000032'),
            $this->mkUser('ob4@yapi.test',       'Lukman Hakim',     'office_boy', '08111000033'),
            $this->mkUser('ob5@yapi.test',       'Cecep Hidayat',    'office_boy', '08111000034'),
        ];

        // 5 petugas toko
        $toko = [
            $this->mkUser('toko@yapi.test',      'Eko Firmansyah',   'petugas_toko', '08111000040'),
            $this->mkUser('toko2@yapi.test',     'Rina Marlina',     'petugas_toko', '08111000041'),
            $this->mkUser('toko3@yapi.test',     'Bambang Sutrisno', 'petugas_toko', '08111000042'),
            $this->mkUser('toko4@yapi.test',     'Nurul Hidayah',    'petugas_toko', '08111000043'),
            $this->mkUser('toko5@yapi.test',     'Putu Ardika',      'petugas_toko', '08111000044'),
        ];

        $this->command->info('✅ Users: ' . User::count() . ' total');

        // ── Units & Lokasi ────────────────────────────────────────────────────
        $this->command->info('🏢 Units & Lokasi...');

        $office = Unit::firstOrCreate(
            ['kode_unit' => 'OFC-01'],
            ['nama_unit' => 'Gedung Kantor Kopkar YAPI', 'alamat' => 'Jl. Yos Sudarso No.123, Jakarta Utara', 'penanggung_jawab' => 'Dewi Rahayu', 'telepon' => '0211234567', 'is_active' => true]
        );
        $tokoUnit = Unit::firstOrCreate(
            ['kode_unit' => 'TK-01'],
            ['nama_unit' => 'Toko Kopkar YAPI', 'alamat' => 'Jl. Yos Sudarso No.125, Jakarta Utara', 'penanggung_jawab' => 'Eko Firmansyah', 'telepon' => '0211234568', 'is_active' => true]
        );

        $mkL = fn (Unit $u, string $kode, string $nama, string $kat, string $lantai) =>
            Lokasi::firstOrCreate(
                ['kode_lokasi' => $kode],
                ['unit_id' => $u->id, 'nama_lokasi' => $nama, 'kategori' => $kat, 'lantai' => $lantai, 'is_active' => true]
            );

        // Office lokasi
        $lA01 = $mkL($office,   'LK-A01', 'Toilet Lantai 1 - Gedung A', 'toilet',      'Lantai 1');
        $lA02 = $mkL($office,   'LK-A02', 'Lobi Utama',                  'lobi',        'Lantai 1');
        $lA03 = $mkL($office,   'LK-A03', 'Pantry Lantai 2',             'pantry',      'Lantai 2');
        $lA04 = $mkL($office,   'LK-A04', 'Ruang Rapat Besar',           'ruang_rapat', 'Lantai 3');
        $lA05 = $mkL($office,   'LK-A05', 'Pos Satpam Depan',            'pos',         'Lantai 1');
        $lA06 = $mkL($office,   'LK-A06', 'Mushola',                     'mushola',     'Lantai 1');
        $lA07 = $mkL($office,   'LK-A07', 'Kantin / Ruang Makan',        'kantin',      'Lantai 1');
        $lA08 = $mkL($office,   'LK-A08', 'Gudang Umum',                 'gudang',      'Lantai B1');
        $lA09 = $mkL($office,   'LK-A09', 'Area Parkir Karyawan',        'parkir',      'Luar');
        $lB01 = $mkL($office,   'LK-B01', 'Toilet Lantai 2 - Gedung B',  'toilet',      'Lantai 2');
        $lB02 = $mkL($office,   'LK-B02', 'Ruang Arsip',                 'kantor',      'Lantai 2');
        $lB03 = $mkL($office,   'LK-B03', 'Pos Satpam Belakang',         'pos',         'Luar');
        $lB04 = $mkL($office,   'LK-B04', 'Pos Satpam Lantai 2',         'pos',         'Lantai 2');
        $lB05 = $mkL($office,   'LK-B05', 'Ruang Monitor CCTV',          'kantor',      'Lantai 1');
        // Toko lokasi
        $lTK1 = $mkL($tokoUnit, 'TK-D01', 'Display Rak A',               'toko',        'Lantai 1');
        $lTK2 = $mkL($tokoUnit, 'TK-D02', 'Display Rak B',               'toko',        'Lantai 1');
        $lTK3 = $mkL($tokoUnit, 'TK-K01', 'Area Kasir',                  'kasir',       'Lantai 1');
        $lTK4 = $mkL($tokoUnit, 'TK-G01', 'Gudang Toko',                 'gudang',      'Lantai 1');
        $lTK5 = $mkL($tokoUnit, 'TK-E01', 'Etalase Produk',              'toko',        'Lantai 1');

        $this->command->info('✅ Lokasi: ' . Lokasi::count() . ' total');

        // ── Bersihkan data hari ini ───────────────────────────────────────────
        $this->command->info("🗑️  Hapus data hari ini ({$today->toDateString()})...");

        ActivityReport::whereDate('tanggal', $today)->forceDelete();
        JadwalKebersihan::whereDate('tanggal', $today)->forceDelete();
        LaporanSatpam::whereDate('tanggal', $today)->forceDelete();
        JadwalSatpam::whereDate('tanggal', $today)->forceDelete();
        LaporanOb::whereDate('tanggal', $today)->forceDelete();
        JadwalOb::whereDate('tanggal', $today)->forceDelete();
        LaporanToko::whereDate('tanggal', $today)->forceDelete();
        JadwalToko::whereDate('tanggal', $today)->forceDelete();
        LaporanKeterlambatan::whereDate('tanggal', $today)->forceDelete();
        GuestComplaint::whereDate('created_at', $today)->forceDelete();

        $td = $today->toDateString();

        // ═════════════════════════════════════════════════════════════════════
        // KEBERSIHAN — 5 petugas, 2 jadwal masing-masing
        // ═════════════════════════════════════════════════════════════════════
        $this->command->info('🧹 Kebersihan...');

        // Helper: buat jadwal + laporan kebersihan
        $mkKeb = function (
            User $p, Lokasi $lok, string $shift, string $jamM, string $jamS,
            string $prio, string $statusLap, string $kegiatan,
            array $fBefore, array $fAfter, ?string $catatanSpv,
            bool $approved, ?int $rating, int $late = 0
        ) use ($admin, $spv1, $today, $td): void {
            $jadwal = JadwalKebersihan::create([
                'petugas_id' => $p->id, 'lokasi_id' => $lok->id,
                'tanggal' => $td, 'shift' => $shift,
                'jam_mulai' => $jamM, 'jam_selesai' => $jamS,
                'prioritas' => $prio, 'status' => 'active',
                'catatan' => "Jadwal rutin {$shift}", 'created_by' => $admin->id,
            ]);

            if ($statusLap !== 'pending') {
                ActivityReport::create([
                    'petugas_id'     => $p->id, 'lokasi_id' => $lok->id, 'jadwal_id' => $jadwal->id,
                    'tanggal'        => $td,
                    'jam_mulai'      => $jamM,
                    'jam_selesai'    => $statusLap === 'draft' ? null : $jamS,
                    'kegiatan'       => $kegiatan,
                    'catatan_petugas'=> 'Pembersihan selesai sesuai SOP. Area sudah dicek ulang.',
                    'foto_sebelum'   => $fBefore,
                    'foto_sesudah'   => $statusLap !== 'draft' ? $fAfter : null,
                    'status'         => $statusLap,
                    'late_minutes'   => $late,
                    'rating'         => $approved ? $rating : null,
                    'approved_by'    => $approved ? $spv1->id : null,
                    'approved_at'    => $approved ? now()->subHours(rand(1, 3)) : null,
                    'catatan_supervisor' => $catatanSpv,
                ]);
            }
        };

        // ── Rahmat Hidayat ────────────────────────────────────────────────────
        $mkKeb(
            $keb[0], $lA01, 'pagi', '06:30', '08:30', 'tinggi', 'approved',
            'Bersihkan & desinfeksi toilet: kuras bak, gosok closet & wastafel, ganti sabun, wangi area.',
            self::kebFoto('toilet_l1'), self::kebFotoAfter('toilet_l1'), 'Toilet sangat bersih dan harum. Pertahankan!', true, 5
        );
        $mkKeb(
            $keb[0], $lA06, 'siang', '12:00', '13:00', 'normal', 'submitted',
            'Bersihkan mushola: vacuum sajadah, lap rak Al-Quran, bersihkan area wudhu.',
            self::kebFoto('mushola'), self::kebFotoAfter('mushola'), null, false, null
        );

        // ── Siti Nurhaliza ────────────────────────────────────────────────────
        $mkKeb(
            $keb[1], $lA02, 'pagi', '07:00', '09:00', 'tinggi', 'submitted',
            'Sapu & pel lobi: bersihkan karpet, lap meja resepsionis, bersihkan kaca pintu masuk.',
            self::kebFoto('lobi', 1), self::kebFotoAfter('lobi', 1), null, false, null, 5
        );
        // Late report (5 menit)
        $mkKeb(
            $keb[1], $lA07, 'sore', '15:00', '17:00', 'normal', 'draft',
            'Bersihkan kantin: lap meja makan, sapu lantai, buang sampah.',
            self::kebFoto('kantin'), [], null, false, null
        );

        // ── Rini Wahyuni ──────────────────────────────────────────────────────
        $mkKeb(
            $keb[2], $lA03, 'pagi', '07:00', '09:00', 'normal', 'approved',
            'Bersihkan pantry: cuci piring di sink, lap microwave & kulkas, sapu & pel lantai.',
            self::kebFoto('pantry', 1), self::kebFotoAfter('pantry', 1), 'Pantry sangat rapi dan bersih.', true, 4
        );
        // Terlambat 20 menit
        $mkKeb(
            $keb[2], $lA08, 'siang', '12:00', '14:00', 'rendah', 'submitted',
            'Rapikan gudang: sapu lantai, tata barang sesuai kategori, buang sampah besar.',
            self::kebFoto('gudang', 1), self::kebFotoAfter('gudang', 1), null, false, null, 20
        );

        // ── Joko Susanto ──────────────────────────────────────────────────────
        $mkKeb(
            $keb[3], $lA04, 'pagi', '06:30', '08:30', 'tinggi', 'approved',
            'Persiapkan ruang rapat: vacuum karpet, lap meja & kursi, bersihkan whiteboard, cek proyektor.',
            self::kebFoto('ruang_rapat', 1), self::kebFotoAfter('ruang_rapat', 1), 'Ruang rapat siap pakai, sangat rapi.', true, 5
        );
        $mkKeb(
            $keb[3], $lA09, 'siang', '12:00', '14:00', 'normal', 'submitted',
            'Bersihkan area parkir: sapu seluruh lantai, buang sampah, cat marka jika ada coretan.',
            self::kebFoto('parkir', 1), self::kebFotoAfter('parkir', 1), null, false, null
        );

        // ── Maya Anggraini ────────────────────────────────────────────────────
        $mkKeb(
            $keb[4], $lB01, 'pagi', '07:00', '09:00', 'normal', 'submitted',
            'Toilet lantai 2: kuras bak, desinfeksi closet & wastafel, refill sabun & tisu.',
            self::kebFoto('toilet_l2', 1), self::kebFotoAfter('toilet_l2', 1), null, false, null, 10
        );
        $mkKeb(
            $keb[4], $lA02, 'sore', '15:30', '17:00', 'rendah', 'pending',
            '', [], [], null, false, null
        );

        // Laporan keterlambatan kebersihan — cari via ActivityReport langsung
        $lateReports = ActivityReport::whereDate('tanggal', $today)
            ->where('late_minutes', '>', 0)
            ->whereNotNull('jadwal_id')
            ->get();

        foreach ($lateReports as $lap) {
            $jdw = JadwalKebersihan::find($lap->jadwal_id);
            if (! $jdw) continue;
            LaporanKeterlambatan::create([
                'domain'               => 'kebersihan',
                'jadwal_kebersihan_id' => $jdw->id,
                'petugas_id'           => $jdw->petugas_id,
                'lokasi_id'            => $jdw->lokasi_id,
                'tanggal'              => $td,
                'shift'                => $jdw->shift,
                'batas_waktu_mulai'    => $jdw->jam_mulai->format('H:i'),
                'batas_waktu_selesai'  => $jdw->jam_selesai->format('H:i'),
                'status'               => 'terdeteksi',
                'keterangan'           => "Petugas memulai pekerjaan {$lap->late_minutes} menit setelah jadwal.",
                'waktu_terdeteksi'     => now()->setTimeFromTimeString($jdw->jam_mulai->format('H:i'))->addMinutes($lap->late_minutes),
            ]);
        }

        $kebJadwalCount = JadwalKebersihan::whereDate('tanggal', $today)->count();
        $kebLapCount    = ActivityReport::whereDate('tanggal', $today)->count();
        $this->command->info("✅ Jadwal kebersihan: {$kebJadwalCount} | Laporan: {$kebLapCount}");

        // ═════════════════════════════════════════════════════════════════════
        // SATPAM — 5 petugas, 3 shift
        // ═════════════════════════════════════════════════════════════════════
        $this->command->info('🛡️  Satpam...');

        $mkSat = function (
            User $p, Lokasi $lok, string $shift, string $jamM, string $jamS,
            string $jadwalStatus, ?string $kondisi, ?string $temuan, ?string $tindakan,
            ?array $foto, string $lapStatus, ?string $catatanSpv, bool $approved, ?int $rating
        ) use ($admin, $spv1, $td): void {
            $jadwal = JadwalSatpam::create([
                'petugas_id' => $p->id, 'lokasi_id' => $lok->id,
                'tanggal' => $td, 'shift' => $shift,
                'jam_mulai' => $jamM, 'jam_selesai' => $jamS,
                'status' => $jadwalStatus, 'created_by' => $admin->id,
            ]);

            if ($kondisi) {
                LaporanSatpam::create([
                    'jadwal_id'         => $jadwal->id, 'petugas_id' => $p->id, 'lokasi_id' => $lok->id,
                    'tanggal'           => $td, 'jam_mulai' => $jamM, 'jam_selesai' => $jamS,
                    'kondisi'           => $kondisi, 'temuan' => $temuan,
                    'tindakan'          => $tindakan, 'foto' => $foto,
                    'status'            => $lapStatus,
                    'catatan_petugas'   => 'Patroli & pemantauan berjalan normal.',
                    'catatan_supervisor'=> $catatanSpv,
                    'rating'            => $approved ? $rating : null,
                    'approved_by'       => $approved ? $spv1->id : null,
                    'approved_at'       => $approved ? now()->subHours(rand(1, 2)) : null,
                ]);
            }
        };

        // Shift pagi: Budi (pos depan) + Dodi (pos belakang) + Agus (patroli parkir)
        $mkSat($sat[0], $lA05, 'pagi', '06:00', '14:00', 'in_progress',
            'aman', '07:30 — 1 kendaraan tamu parkir di slot karyawan. Sudah diarahkan ke area tamu.',
            'Tamu dipindahkan. Dicatat di buku tamu.', self::satFoto('pos_depan', 1),
            'approved', 'Laporan lengkap dan prosedur tepat.', true, 5);

        $mkSat($sat[2], $lB03, 'pagi', '06:00', '14:00', 'in_progress',
            'aman', 'Tidak ada temuan luar biasa. Semua akses belakang terkunci.',
            'Lanjut patroli reguler setiap 2 jam.', self::satFoto('pos_belakang', 1),
            'submitted', null, false, null);

        $mkSat($sat[3], $lA09, 'pagi', '06:00', '14:00', 'in_progress',
            'perlu_perhatian', '09:00 — Ditemukan motor tanpa STNK di parkir barat. Sudah dihubungi pemilik.',
            'Koordinasi dengan pemilik kendaraan. Motor boleh setelah tunjukkan identitas.', self::satFoto('parkir', 1),
            'submitted', null, false, null);

        // Shift siang: Hendra (pos depan) + Fajar (lobby tamu)
        $mkSat($sat[1], $lA05, 'siang', '14:00', '22:00', 'pending',
            null, null, null, null, 'pending', null, false, null);

        $mkSat($sat[4], $lA02, 'siang', '14:00', '22:00', 'pending',
            null, null, null, null, 'pending', null, false, null);

        // Shift malam: Budi (second shift for demo) — ruang monitor CCTV
        $mkSat($sat[0], $lB05, 'standby', '22:00', '06:00', 'pending',
            null, null, null, null, 'pending', null, false, null);

        // Keterlambatan satpam
        LaporanKeterlambatan::create([
            'domain'              => 'satpam',
            'jadwal_kebersihan_id'=> null,
            'petugas_id'          => $sat[1]->id,
            'lokasi_id'           => $lA05->id,
            'tanggal'             => $td,
            'shift'               => 'siang',
            'batas_waktu_mulai'   => '14:00',
            'batas_waktu_selesai' => '22:00',
            'status'              => 'terdeteksi',
            'keterangan'          => 'Satpam shift siang belum check-in pukul 14:00.',
            'waktu_terdeteksi'    => now()->setTimeFromTimeString('14:18'),
        ]);

        $this->command->info('✅ Jadwal satpam: ' . JadwalSatpam::whereDate('tanggal', $today)->count() .
            ' | Laporan: ' . LaporanSatpam::whereDate('tanggal', $today)->count());

        // ═════════════════════════════════════════════════════════════════════
        // OFFICE BOY — 5 OB, berbagai tugas
        // ═════════════════════════════════════════════════════════════════════
        $this->command->info('🧴 Office Boy...');

        $mkOb = function (
            User $p, Lokasi $lok, string $shift, string $jamM, string $jamS,
            string $jadwalStatus, ?string $jenisPekerjaan, ?string $uraian,
            ?array $fBefore, ?array $fAfter, string $lapStatus,
            ?string $catatanSpv, bool $approved, ?int $rating
        ) use ($admin, $spv1, $td): void {
            $jadwal = JadwalOb::create([
                'petugas_id' => $p->id, 'lokasi_id' => $lok->id,
                'tanggal' => $td, 'shift' => $shift,
                'jam_mulai' => $jamM, 'jam_selesai' => $jamS,
                'status' => $jadwalStatus, 'created_by' => $admin->id,
            ]);

            if ($jenisPekerjaan) {
                LaporanOb::create([
                    'jadwal_id'         => $jadwal->id, 'petugas_id' => $p->id, 'lokasi_id' => $lok->id,
                    'tanggal'           => $td, 'jam_mulai' => $jamM, 'jam_selesai' => $jamS,
                    'jenis_pekerjaan'   => $jenisPekerjaan, 'uraian' => $uraian,
                    'foto_sebelum'      => $fBefore, 'foto_sesudah' => $fAfter,
                    'status'            => $lapStatus,
                    'catatan_petugas'   => 'Pekerjaan selesai sesuai instruksi.',
                    'catatan_supervisor'=> $catatanSpv,
                    'rating'            => $approved ? $rating : null,
                    'approved_by'       => $approved ? $spv1->id : null,
                    'approved_at'       => $approved ? now()->subHours(rand(1, 2)) : null,
                ]);
            }
        };

        // Andi — setup ruang rapat (approved) + serving (submitted)
        $mkOb($ob[0], $lA04, 'pagi', '07:00', '07:45', 'in_progress',
            'Setup Ruang Rapat Direksi',
            'Susun 12 kursi, pasang LCD proyektor, tulis agenda di whiteboard, siapkan name card & alat tulis peserta.',
            self::obFoto('setup_rapat', 1, 'sebelum'), self::obFoto('setup_rapat', 1, 'sesudah'),
            'approved', 'Persiapan sangat teliti dan tepat waktu.', true, 5);

        $mkOb($ob[0], $lA04, 'pagi', '09:00', '09:30', 'in_progress',
            'Serving Rapat Direksi',
            'Sajikan kopi, teh, air mineral & snack untuk 12 peserta rapat. Pastikan gelas terisi penuh.',
            self::obFoto('serving', 1, 'sebelum'), self::obFoto('serving', 1, 'sesudah'),
            'submitted', null, false, null);

        // Wahyu — pantry pagi + antar dokumen
        $mkOb($ob[1], $lA03, 'pagi', '07:00', '08:00', 'in_progress',
            'Persiapan Pantry Pagi',
            'Buat 20 cup kopi & 10 cup teh untuk distribusi ke seluruh divisi. Antar ke meja masing-masing.',
            self::obFoto('pantry', 1, 'sebelum'), self::obFoto('pantry', 1, 'sesudah'),
            'approved', 'Distribusi tepat waktu, kopi enak!', true, 4);

        $mkOb($ob[1], $lB02, 'pagi', '10:00', '11:00', 'in_progress',
            'Antar Dokumen Arsip',
            'Antar 3 binder dokumen kontrak ke ruang arsip lantai 2. Catat di buku pengiriman dokumen.',
            self::obFoto('arsip', 1, 'sebelum'), self::obFoto('arsip', 1, 'sesudah'),
            'submitted', null, false, null);

        // Tono — lobi tamu
        $mkOb($ob[2], $lA02, 'pagi', '08:00', '12:00', 'in_progress',
            'Layanan Tamu Lobi',
            'Sambut tamu, arahkan ke ruang tujuan, sediakan minuman untuk tamu yang menunggu.',
            self::obFoto('lobby_service', 1, 'sebelum'), self::obFoto('lobby_service', 1, 'sesudah'),
            'submitted', null, false, null);

        $mkOb($ob[2], $lA03, 'sore', '13:00', '14:00', 'pending',
            null, null, null, null, 'pending', null, false, null);

        // Lukman — shift sore setup rapat sore
        $mkOb($ob[3], $lA04, 'sore', '13:30', '14:30', 'pending',
            null, null, null, null, 'pending', null, false, null);

        $mkOb($ob[3], $lA03, 'sore', '15:00', '17:00', 'pending',
            null, null, null, null, 'pending', null, false, null);

        // Cecep — shift sore / malam
        $mkOb($ob[4], $lA02, 'sore', '14:00', '17:00', 'pending',
            null, null, null, null, 'pending', null, false, null);

        // Keterlambatan OB
        LaporanKeterlambatan::create([
            'domain'              => 'office_boy',
            'jadwal_kebersihan_id'=> null,
            'petugas_id'          => $ob[2]->id,
            'lokasi_id'           => $lA02->id,
            'tanggal'             => $td,
            'shift'               => 'pagi',
            'batas_waktu_mulai'   => '08:00',
            'batas_waktu_selesai' => '12:00',
            'status'              => 'terdeteksi',
            'keterangan'          => 'Office Boy belum checkin pukul 08:00. Terdeteksi 12 menit terlambat.',
            'waktu_terdeteksi'    => now()->setTimeFromTimeString('08:12'),
        ]);

        $this->command->info('✅ Jadwal OB: ' . JadwalOb::whereDate('tanggal', $today)->count() .
            ' | Laporan: ' . LaporanOb::whereDate('tanggal', $today)->count());

        // ═════════════════════════════════════════════════════════════════════
        // TOKO — 5 petugas, per area
        // ═════════════════════════════════════════════════════════════════════
        $this->command->info('🛒 Toko...');

        $mkToko = function (
            User $p, Lokasi $lok, string $shift, string $jamM, string $jamS,
            string $jadwalStatus, ?string $kondisiStok, ?string $catatanStok,
            ?array $checklist, ?array $foto, string $lapStatus,
            ?string $catatanSpv, bool $approved, ?int $rating
        ) use ($admin, $spv1, $td): void {
            $jadwal = JadwalToko::create([
                'petugas_id' => $p->id, 'lokasi_id' => $lok->id,
                'tanggal' => $td, 'shift' => $shift,
                'jam_mulai' => $jamM, 'jam_selesai' => $jamS,
                'status' => $jadwalStatus, 'created_by' => $admin->id,
            ]);

            if ($kondisiStok) {
                LaporanToko::create([
                    'jadwal_id'         => $jadwal->id, 'petugas_id' => $p->id, 'lokasi_id' => $lok->id,
                    'tanggal'           => $td, 'jam_mulai' => $jamM, 'jam_selesai' => $jamS,
                    'kondisi_stok'      => $kondisiStok, 'catatan_stok' => $catatanStok,
                    'checklist'         => $checklist, 'foto' => $foto,
                    'status'            => $lapStatus,
                    'catatan_petugas'   => 'Operasional toko berjalan lancar.',
                    'catatan_supervisor'=> $catatanSpv,
                    'rating'            => $approved ? $rating : null,
                    'approved_by'       => $approved ? $spv1->id : null,
                    'approved_at'       => $approved ? now()->subHours(rand(1, 2)) : null,
                ]);
            }
        };

        // Eko — kasir shift pagi (approved) + shift siang (pending)
        $mkToko($toko[0], $lTK3, 'pagi', '08:00', '13:00', 'in_progress',
            'aman', 'Transaksi: 47 | Omset: Rp 2.380.000 | Saldo kasir: Rp 1.200.000 | Stok mie instan hampir habis — perlu restock.',
            ['Cek kasir', 'Cek stok', 'Bersihkan etalase', 'Buka toko', 'Hitung uang awal'],
            self::tokoFoto('kasir', 1),
            'approved', 'Laporan kasir lengkap dan akurat.', true, 5);

        $mkToko($toko[0], $lTK3, 'sore', '13:00', '20:00', 'pending',
            null, null, null, null, 'pending', null, false, null);

        // Rina — display rak A (submitted)
        $mkToko($toko[1], $lTK1, 'pagi', '08:00', '13:00', 'in_progress',
            'aman', 'Rak A: semua produk tersusun rapih. Stok minuman botol tinggal 8 — perlu restock hari ini.',
            ['Cek stok rak', 'Tata produk', 'Bersihkan rak', 'Label harga update'],
            self::tokoFoto('display_a', 1),
            'submitted', null, false, null);

        $mkToko($toko[1], $lTK1, 'sore', '13:00', '20:00', 'pending',
            null, null, null, null, 'pending', null, false, null);

        // Bambang — display rak B (submitted)
        $mkToko($toko[2], $lTK2, 'pagi', '08:00', '13:00', 'in_progress',
            'aman', 'Rak B (snack & makanan): stok aman. Produk kadaluarsa 2 item sudah disingkirkan.',
            ['Cek tanggal expired', 'Tata produk FIFO', 'Bersihkan rak', 'Cek harga label'],
            self::tokoFoto('display_b', 1),
            'submitted', null, false, null);

        $mkToko($toko[2], $lTK2, 'sore', '13:00', '20:00', 'pending',
            null, null, null, null, 'pending', null, false, null);

        // Nurul — gudang toko (submitted)
        $mkToko($toko[3], $lTK4, 'pagi', '07:00', '13:00', 'in_progress',
            'perlu_perhatian', 'Gudang: terima 3 karton mie instan & 2 karton minuman. Stok semen kurang — hubungi supplier.',
            ['Terima barang', 'Cek PO supplier', 'Input stok sistem', 'Tata gudang FIFO'],
            self::tokoFoto('gudang', 1),
            'submitted', null, false, null);

        $mkToko($toko[3], $lTK4, 'sore', '13:00', '20:00', 'pending',
            null, null, null, null, 'pending', null, false, null);

        // Putu — etalase produk (approved)
        $mkToko($toko[4], $lTK5, 'pagi', '08:00', '13:00', 'in_progress',
            'aman', 'Etalase produk premium: semua item terpajang rapi, harga label diupdate, kaca etalase dilap bersih.',
            ['Lap kaca etalase', 'Susun produk premium', 'Update label harga', 'Foto etalase'],
            self::tokoFoto('etalase', 1),
            'approved', 'Etalase sangat menarik dan informatif!', true, 5);

        $mkToko($toko[4], $lTK5, 'sore', '13:00', '20:00', 'pending',
            null, null, null, null, 'pending', null, false, null);

        // Keterlambatan toko
        LaporanKeterlambatan::create([
            'domain'              => 'petugas_toko',
            'jadwal_kebersihan_id'=> null,
            'petugas_id'          => $toko[2]->id,
            'lokasi_id'           => $lTK2->id,
            'tanggal'             => $td,
            'shift'               => 'pagi',
            'batas_waktu_mulai'   => '08:00',
            'batas_waktu_selesai' => '13:00',
            'status'              => 'terdeteksi',
            'keterangan'          => 'Petugas toko belum membuka rak B pukul 08:00. Terdeteksi 8 menit terlambat.',
            'waktu_terdeteksi'    => now()->setTimeFromTimeString('08:08'),
        ]);

        $this->command->info('✅ Jadwal toko: ' . JadwalToko::whereDate('tanggal', $today)->count() .
            ' | Laporan: ' . LaporanToko::whereDate('tanggal', $today)->count());

        // ═════════════════════════════════════════════════════════════════════
        // GUEST COMPLAINTS — berbagai tipe
        // ═════════════════════════════════════════════════════════════════════
        $this->command->info('📣 Guest Complaints...');

        $complaints = [
            // [lokasi, nama_pelapor, email, telp, jenis, tipe, deskripsi, fotoKey, status, assignedTo, handledBy, catatanPenanganan]
            [
                $lA01, 'Bpk. Hendra Wibowo', 'hendra.w@kopkar.co.id', '08119988771',
                GuestComplaint::JENIS_BAU, GuestComplaint::TIPE_KEBERSIHAN,
                'Toilet lantai 1 berbau tidak sedap sejak pagi tadi. Sudah mengganggu karyawan sekitar.',
                'toilet', 'in_progress', $keb[0], null, null,
            ],
            [
                $lA02, 'Ibu Ratna Sari', 'ratna.s@kopkar.co.id', '08117766554',
                GuestComplaint::JENIS_TUMPAHAN, GuestComplaint::TIPE_KEBERSIHAN,
                'Ada tumpahan kopi di lantai lobi depan dekat lift, sudah cukup lama tidak dibersihkan.',
                'lobi', 'resolved', $keb[1], $keb[1],
                'Tumpahan sudah dibersihkan. Area sudah aman.',
            ],
            [
                $lA09, 'Pak Agus Setiawan', 'agus.s@kopkar.co.id', '08113344556',
                GuestComplaint::JENIS_LAINNYA, GuestComplaint::TIPE_SATPAM,
                'Kendaraan tidak dikenal parkir di area riservasi direksi lebih dari 3 jam. Mohon ditindak.',
                'parkir', 'resolved', $sat[0], $sat[0],
                'Kendaraan sudah dipindahkan pemilik setelah dihubungi. Area kembali normal.',
            ],
            [
                $lA04, 'Ibu Sri Mulyani', 'sri.m@kopkar.co.id', '08112233445',
                GuestComplaint::JENIS_KOTOR, GuestComplaint::TIPE_OFFICE_BOY,
                'Ruang rapat lantai 3 belum dibersihkan setelah rapat kemarin. Kursi berantakan dan meja kotor.',
                'kantin', 'resolved', $ob[0], $ob[0],
                'Ruang rapat sudah dirapikan dan dibersihkan. Siap digunakan kembali.',
            ],
            [
                $lA07, 'Pak Dani Kusuma', 'dani.k@kopkar.co.id', '08115544332',
                GuestComplaint::JENIS_BAU, GuestComplaint::TIPE_KEBERSIHAN,
                'Area kantin berbau tidak enak, kemungkinan dari tempat sampah yang belum dibuang.',
                'kantin', 'pending', null, null, null,
            ],
            [
                $lA02, 'Ibu Dewi Lestari', 'dewi.l@kopkar.co.id', '08118877665',
                GuestComplaint::JENIS_LAINNYA, GuestComplaint::TIPE_SATPAM,
                'Tamu tidak dikenal masuk tanpa lapor ke resepsionis pukul 10:30. Mohon diperketat.',
                'lobi', 'in_progress', $sat[0], null, null,
            ],
        ];

        foreach ($complaints as $c) {
            [$lok, $nama, $email, $telp, $jenis, $tipe, $desc, $fotoKey, $status, $assignedTo, $handledBy, $catatanPenanganan] = $c;
            GuestComplaint::create([
                'lokasi_id'          => $lok->id,
                'nama_pelapor'       => $nama,
                'email_pelapor'      => $email,
                'telepon_pelapor'    => $telp,
                'jenis_keluhan'      => $jenis,
                'tipe_laporan'       => $tipe,
                'deskripsi_keluhan'  => $desc,
                'foto_keluhan'       => self::compFoto($fotoKey),
                'status'             => $status,
                'assigned_to'        => $assignedTo?->id,
                'assigned_at'        => $assignedTo ? now()->subHours(rand(1, 4)) : null,
                'handled_by'         => $handledBy?->id,
                'handled_at'         => $handledBy ? now()->subMinutes(rand(15, 90)) : null,
                'catatan_penanganan' => $catatanPenanganan,
                'foto_penanganan'    => $handledBy ? self::compFoto($fotoKey) : null,
            ]);
        }

        $this->command->info('✅ Guest Complaints: ' . GuestComplaint::whereDate('created_at', $today)->count());

        // ═════════════════════════════════════════════════════════════════════
        // PENILAIAN — bulan ini untuk semua petugas
        // ═════════════════════════════════════════════════════════════════════
        $this->command->info('⭐ Penilaian...');

        $bulan = $today->month;
        $tahun = $today->year;

        $penilaianData = [
            // kebersihan
            [$keb[0], 95, 92, 96, 93, 'Performa terbaik bulan ini. Selalu on time dan sangat teliti.'],
            [$keb[1], 82, 85, 78, 88, 'Ada beberapa keterlambatan, kualitas kerja baik.'],
            [$keb[2], 88, 86, 85, 90, 'Konsisten dan rapi, perlu tingkatkan ketepatan waktu sedikit.'],
            [$keb[3], 94, 95, 92, 94, 'Sangat disiplin. Ruang rapat selalu siap pakai tepat waktu.'],
            [$keb[4], 80, 82, 80, 83, 'Masih perlu pendampingan. Ada 3 keterlambatan bulan ini.'],
            // satpam
            [$sat[0], 96, 90, 95, 92, 'Satpam terbaik. Penanganan insiden cepat dan profesional.'],
            [$sat[1], 85, 88, 80, 86, 'Baik. Perlu lebih responsif saat pergantian shift.'],
            [$sat[2], 89, 87, 88, 85, 'Patroli rutin konsisten, buku laporan lengkap.'],
            [$sat[3], 83, 84, 82, 80, 'Kinerja baik, temuan kendaraan ilegal ditangani dengan benar.'],
            [$sat[4], 78, 80, 75, 82, 'Baru bergabung, perlu adaptasi SOP. Semangat tinggi.'],
            // ob
            [$ob[0],  97, 95, 96, 94, 'OB terbaik! Setup rapat selalu sempurna dan serving sangat ramah.'],
            [$ob[1],  90, 88, 92, 87, 'Distribusi minuman tepat waktu. Manajemen pantry rapi.'],
            [$ob[2],  84, 82, 80, 85, 'Layanan tamu baik, keterlambatan 1x bulan ini.'],
            [$ob[3],  86, 89, 85, 88, 'Tanggap dan rajin. Penanganan dokumen sangat teliti.'],
            [$ob[4],  79, 81, 78, 80, 'Perlu lebih proaktif. Kualitas kerja membaik dari bulan lalu.'],
            // toko
            [$toko[0], 96, 94, 97, 93, 'Pengelolaan kasir sangat akurat. Tidak ada selisih kas selama sebulan.'],
            [$toko[1], 88, 90, 86, 89, 'Penataan rak sangat rapi dan menarik pelanggan.'],
            [$toko[2], 85, 86, 82, 87, 'Pengecekan expired date sangat teliti. Ada 1 keterlambatan.'],
            [$toko[3], 91, 88, 89, 86, 'Manajemen stok gudang baik. Restock tepat waktu.'],
            [$toko[4], 93, 95, 92, 94, 'Etalase selalu menarik. Penjualan produk premium meningkat 15%.'],
        ];

        foreach ($penilaianData as [$p, $kehadiran, $kualitas, $ketepatan, $kebersihan, $catatan]) {
            Penilaian::firstOrCreate(
                ['petugas_id' => $p->id, 'periode_bulan' => $bulan, 'periode_tahun' => $tahun],
                [
                    'penilai_id'           => $spv1->id,
                    'skor_kehadiran'       => $kehadiran,
                    'skor_kualitas'        => $kualitas,
                    'skor_ketepatan_waktu' => $ketepatan,
                    'skor_kebersihan'      => $kebersihan,
                    'total_skor'           => $kehadiran + $kualitas + $ketepatan + $kebersihan,
                    'rata_rata'            => round(($kehadiran + $kualitas + $ketepatan + $kebersihan) / 4, 2),
                    'kategori'             => (($kehadiran + $kualitas + $ketepatan + $kebersihan) / 4) >= 88
                                             ? 'Sangat Baik' : 'Baik',
                    'catatan'              => $catatan,
                ]
            );
        }

        $this->command->info('✅ Penilaian: ' . Penilaian::where('periode_bulan', $bulan)->where('periode_tahun', $tahun)->count() . ' petugas');

        // ═════════════════════════════════════════════════════════════════════
        // SUMMARY
        // ═════════════════════════════════════════════════════════════════════
        $this->command->info('');
        $this->command->info('🎉 ===== SEEDING PRESENTASI SELESAI! =====');
        $this->command->info('');
        $this->command->info("📊 Ringkasan hari ini ({$today->format('d F Y')}):");
        $this->command->info('  👥 Users     : ' . User::count() . ' total');
        $this->command->info('  🏢 Unit: ' . Unit::count() . '  |  Lokasi: ' . Lokasi::count());
        $this->command->info('  🧹 Jadwal Kebersihan : ' . JadwalKebersihan::whereDate('tanggal', $today)->count() . '  |  Laporan: ' . ActivityReport::whereDate('tanggal', $today)->count());
        $this->command->info('  🛡️  Jadwal Satpam    : ' . JadwalSatpam::whereDate('tanggal', $today)->count() . '  |  Laporan: ' . LaporanSatpam::whereDate('tanggal', $today)->count());
        $this->command->info('  🧴 Jadwal OB         : ' . JadwalOb::whereDate('tanggal', $today)->count() . '  |  Laporan: ' . LaporanOb::whereDate('tanggal', $today)->count());
        $this->command->info('  🛒 Jadwal Toko       : ' . JadwalToko::whereDate('tanggal', $today)->count() . '  |  Laporan: ' . LaporanToko::whereDate('tanggal', $today)->count());
        $this->command->info('  ⚠️  Keterlambatan    : ' . LaporanKeterlambatan::whereDate('tanggal', $today)->count() . '  (kebersihan + satpam + OB + toko)');
        $this->command->info('  📣 Keluhan Tamu      : ' . GuestComplaint::whereDate('created_at', $today)->count() . '  (kebersihan, satpam, OB)');
        $this->command->info('  ⭐ Penilaian Juni    : ' . Penilaian::where('periode_bulan', $bulan)->where('periode_tahun', $tahun)->count() . ' petugas dinilai');
        $this->command->info('');
        $this->command->info('🔐 Login (password: password123):');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Admin          : admin@yapi.test');
        $this->command->info('Supervisor 1   : spv@yapi.test         (Dewi Rahayu)');
        $this->command->info('Supervisor 2   : spv2@yapi.test        (Anton Wijaya)');
        $this->command->info('-- Kebersihan --------------------------------');
        $this->command->info('Petugas 1      : petugas@yapi.test     (Rahmat Hidayat)');
        $this->command->info('Petugas 2      : petugas2@yapi.test    (Siti Nurhaliza)');
        $this->command->info('Petugas 3      : petugas3@yapi.test    (Rini Wahyuni)');
        $this->command->info('Petugas 4      : petugas4@yapi.test    (Joko Susanto)');
        $this->command->info('Petugas 5      : petugas5@yapi.test    (Maya Anggraini)');
        $this->command->info('-- Keamanan (Satpam) -------------------------');
        $this->command->info('Satpam 1       : satpam@yapi.test      (Budi Santoso)');
        $this->command->info('Satpam 2       : satpam2@yapi.test     (Hendra Kurniawan)');
        $this->command->info('Satpam 3       : satpam3@yapi.test     (Dodi Prasetya)');
        $this->command->info('Satpam 4       : satpam4@yapi.test     (Agus Riyanto)');
        $this->command->info('Satpam 5       : satpam5@yapi.test     (Fajar Nugroho)');
        $this->command->info('-- Office Boy --------------------------------');
        $this->command->info('OB 1           : ob@yapi.test          (Andi Prasetyo)');
        $this->command->info('OB 2           : ob2@yapi.test         (Wahyu Setiawan)');
        $this->command->info('OB 3           : ob3@yapi.test         (Tono Subarjo)');
        $this->command->info('OB 4           : ob4@yapi.test         (Lukman Hakim)');
        $this->command->info('OB 5           : ob5@yapi.test         (Cecep Hidayat)');
        $this->command->info('-- Toko ------------------------------------');
        $this->command->info('Toko 1         : toko@yapi.test        (Eko Firmansyah)');
        $this->command->info('Toko 2         : toko2@yapi.test       (Rina Marlina)');
        $this->command->info('Toko 3         : toko3@yapi.test       (Bambang Sutrisno)');
        $this->command->info('Toko 4         : toko4@yapi.test       (Nurul Hidayah)');
        $this->command->info('Toko 5         : toko5@yapi.test       (Putu Ardika)');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🌐 Admin Panel : http://localhost:8000/admin');
        $this->command->info('📱 PWA Petugas : http://localhost:3000');
        $this->command->info('');
    }
}
