<?php

namespace Database\Seeders;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\Penilaian;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Starting dummy data seeding...');

        // 1. Create Users for all roles
        $this->command->info('👥 Creating users...');

        // Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@eclean.test',
            'phone' => '081234567890',
            'password' => Hash::make('password'),
        ]);
        $superAdmin->assignRole('super_admin');
        $this->command->info('✅ Super Admin created: superadmin@eclean.test / password');

        // Admin
        $admin = User::create([
            'name' => 'Admin System',
            'email' => 'admin@eclean.test',
            'phone' => '081234567891',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');
        $this->command->info('✅ Admin created: admin@eclean.test / password');

        // Supervisors
        $supervisor1 = User::create([
            'name' => 'Budi Supervisor',
            'email' => 'supervisor@eclean.test',
            'phone' => '081234567892',
            'password' => Hash::make('password'),
        ]);
        $supervisor1->assignRole('supervisor');

        $supervisor2 = User::create([
            'name' => 'Siti Supervisor',
            'email' => 'supervisor2@eclean.test',
            'phone' => '081234567893',
            'password' => Hash::make('password'),
        ]);
        $supervisor2->assignRole('supervisor');
        $this->command->info('✅ 2 Supervisors created: supervisor@eclean.test, supervisor2@eclean.test / password');

        // Pengurus
        $pengurus = User::create([
            'name' => 'Kepala Sekolah',
            'email' => 'pengurus@eclean.test',
            'phone' => '081234567894',
            'password' => Hash::make('password'),
        ]);
        $pengurus->assignRole('pengurus');
        $this->command->info('✅ Pengurus created: pengurus@eclean.test / password');

        // Petugas (5 orang)
        $petugasNames = [
            ['Andi Petugas', 'petugas1@eclean.test', '081234567895'],
            ['Budi Petugas', 'petugas2@eclean.test', '081234567896'],
            ['Citra Petugas', 'petugas3@eclean.test', '081234567897'],
            ['Dewi Petugas', 'petugas4@eclean.test', '081234567898'],
            ['Eko Petugas', 'petugas5@eclean.test', '081234567899'],
        ];

        $allPetugas = [];
        foreach ($petugasNames as $data) {
            $petugas = User::create([
                'name' => $data[0],
                'email' => $data[1],
                'phone' => $data[2],
                'password' => Hash::make('password'),
            ]);
            $petugas->assignRole('petugas');
            $allPetugas[] = $petugas;
        }
        $this->command->info('✅ 5 Petugas created: petugas1-5@eclean.test / password');

        // 2. Create Lokasi
        $this->command->info('📍 Creating locations...');

        $lokasiData = [
            ['RK-1A', 'Ruang Kelas 1A', 'ruang_kelas', 'Lantai 1', 48.5],
            ['RK-1B', 'Ruang Kelas 1B', 'ruang_kelas', 'Lantai 1', 48.5],
            ['RK-2A', 'Ruang Kelas 2A', 'ruang_kelas', 'Lantai 2', 48.5],
            ['TL-L1', 'Toilet Lantai 1', 'toilet', 'Lantai 1', 12.0],
            ['TL-L2', 'Toilet Lantai 2', 'toilet', 'Lantai 2', 12.0],
            ['KT-GURU', 'Kantor Guru', 'kantor', 'Lantai 1', 60.0],
            ['KT-TU', 'Kantor Tata Usaha', 'kantor', 'Lantai 1', 40.0],
            ['AULA', 'Aula Sekolah', 'aula', 'Lantai 1', 200.0],
            ['TMN-1', 'Taman Depan', 'taman', 'Luar', 100.0],
            ['KRD-L1', 'Koridor Lantai 1', 'koridor', 'Lantai 1', 80.0],
            ['KRD-L2', 'Koridor Lantai 2', 'koridor', 'Lantai 2', 80.0],
            ['PERP', 'Perpustakaan', 'lainnya', 'Lantai 2', 120.0],
        ];

        $allLokasi = [];
        foreach ($lokasiData as $data) {
            $lokasi = Lokasi::create([
                'kode_lokasi' => $data[0],
                'nama_lokasi' => $data[1],
                'kategori' => $data[2],
                'lantai' => $data[3],
                'luas_area' => $data[4],
                'deskripsi' => 'Lokasi ' . $data[1],
                'status_kebersihan' => 'bersih',
                'is_active' => true,
            ]);
            $allLokasi[] = $lokasi;
        }
        $this->command->info('✅ ' . count($allLokasi) . ' Lokasi created');

        // 3. Create Jadwal Kebersihan (bulan ini)
        $this->command->info('📅 Creating schedules...');

        $shifts = ['pagi', 'siang', 'sore'];
        $prioritas = ['rendah', 'normal', 'tinggi'];
        $scheduleCount = 0;

        // Create schedules for this month
        for ($day = 1; $day <= 30; $day++) {
            $date = now()->startOfMonth()->addDays($day - 1);

            // Random 3-5 schedules per day
            $schedulesPerDay = rand(3, 5);

            for ($i = 0; $i < $schedulesPerDay; $i++) {
                $petugas = $allPetugas[array_rand($allPetugas)];
                $lokasi = $allLokasi[array_rand($allLokasi)];
                $shift = $shifts[array_rand($shifts)];

                $jamMulai = match($shift) {
                    'pagi' => '06:00',
                    'siang' => '12:00',
                    'sore' => '15:00',
                };

                $jamSelesai = match($shift) {
                    'pagi' => '10:00',
                    'siang' => '14:00',
                    'sore' => '17:00',
                };

                $status = $date < now() ? 'completed' : ($date->isToday() ? 'in_progress' : 'pending');

                JadwalKebersihan::create([
                    'petugas_id' => $petugas->id,
                    'lokasi_id' => $lokasi->id,
                    'tanggal' => $date,
                    'shift' => $shift,
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'prioritas' => $prioritas[array_rand($prioritas)],
                    'catatan' => 'Jadwal pembersihan ' . $shift,
                    'status' => $status,
                ]);

                $scheduleCount++;
            }
        }
        $this->command->info('✅ ' . $scheduleCount . ' Schedules created');

        // 4. Create Activity Reports
        $this->command->info('📝 Creating activity reports...');

        $statuses = ['draft', 'submitted', 'approved', 'rejected'];
        $reportCount = 0;

        foreach ($allPetugas as $petugas) {
            // Each petugas has 10-15 reports this month
            $reportsCount = rand(10, 15);

            for ($i = 0; $i < $reportsCount; $i++) {
                $date = now()->startOfMonth()->addDays(rand(0, 25));
                $lokasi = $allLokasi[array_rand($allLokasi)];
                $status = $statuses[array_rand($statuses)];

                $report = ActivityReport::create([
                    'petugas_id' => $petugas->id,
                    'lokasi_id' => $lokasi->id,
                    'tanggal' => $date,
                    'jam_mulai' => '07:00',
                    'jam_selesai' => '09:00',
                    'kegiatan' => 'Membersihkan ' . $lokasi->nama_lokasi . ' meliputi menyapu, mengepel, dan membersihkan jendela.',
                    'catatan_petugas' => 'Pembersihan dilakukan dengan baik dan menyeluruh.',
                    'status' => $status,
                    'rating' => $status === 'approved' ? rand(3, 5) : null,
                    'catatan_supervisor' => $status === 'approved' ? 'Pekerjaan bagus! Ruangan sangat bersih.' : null,
                    'rejected_reason' => $status === 'rejected' ? 'Foto kurang jelas, harap upload foto yang lebih baik.' : null,
                    'approved_by' => in_array($status, ['approved', 'rejected']) ? $supervisor1->id : null,
                ]);

                $reportCount++;
            }
        }
        $this->command->info('✅ ' . $reportCount . ' Activity Reports created');

        // 5. Create Presensi
        $this->command->info('⏰ Creating attendance records...');

        $presensiCount = 0;
        $presensiStatuses = ['hadir', 'hadir', 'hadir', 'hadir', 'izin', 'sakit']; // Mostly hadir

        foreach ($allPetugas as $petugas) {
            // Each petugas has attendance for 20 days this month
            for ($day = 1; $day <= 20; $day++) {
                $date = now()->startOfMonth()->addDays($day - 1);

                if ($date > now()) break;

                $status = $presensiStatuses[array_rand($presensiStatuses)];

                Presensi::create([
                    'petugas_id' => $petugas->id,
                    'tanggal' => $date,
                    'jam_masuk' => $status === 'hadir' ? '07:00' : null,
                    'jam_keluar' => $status === 'hadir' ? '15:00' : null,
                    'lokasi_absen_masuk' => $status === 'hadir' ? 'Kantor Tata Usaha' : null,
                    'lokasi_absen_keluar' => $status === 'hadir' ? 'Kantor Tata Usaha' : null,
                    'status' => $status,
                    'total_jam_kerja' => $status === 'hadir' ? 8 : 0,
                    'keterangan' => $status !== 'hadir' ? 'Tidak bisa hadir karena ' . $status : null,
                    'approved_by' => $supervisor1->id,
                ]);

                $presensiCount++;
            }
        }
        $this->command->info('✅ ' . $presensiCount . ' Attendance records created');

        // 6. Create Penilaian
        $this->command->info('⭐ Creating evaluations...');

        $penilaianCount = 0;

        foreach ($allPetugas as $petugas) {
            // Each petugas has 2-3 evaluations this month
            $evalCount = rand(2, 3);

            for ($i = 0; $i < $evalCount; $i++) {
                $startDate = now()->startOfMonth()->addDays($i * 10);
                $endDate = $startDate->copy()->addDays(9);

                // Random scores 3-5
                $kebersihan = rand(3, 5);
                $kerapihan = rand(3, 5);
                $ketepatan = rand(3, 5);
                $kelengkapan = rand(3, 5);

                $ratingTotal = ($kebersihan + $kerapihan + $ketepatan + $kelengkapan) / 4;

                Penilaian::create([
                    'petugas_id' => $petugas->id,
                    'periode_start' => $startDate,
                    'periode_end' => $endDate,
                    'aspek_kebersihan' => $kebersihan,
                    'aspek_kerapihan' => $kerapihan,
                    'aspek_ketepatan_waktu' => $ketepatan,
                    'aspek_kelengkapan_laporan' => $kelengkapan,
                    'rating_total' => $ratingTotal,
                    'catatan' => 'Penilaian periode ' . $startDate->format('d M') . ' - ' . $endDate->format('d M Y') . '. ' .
                                ($ratingTotal >= 4 ? 'Performa sangat baik, pertahankan!' : 'Masih perlu peningkatan dalam beberapa aspek.'),
                    'penilai_id' => rand(0, 1) ? $supervisor1->id : $supervisor2->id,
                ]);

                $penilaianCount++;
            }
        }
        $this->command->info('✅ ' . $penilaianCount . ' Evaluations created');

        // Summary
        $this->command->info('');
        $this->command->info('🎉 ===== SEEDING COMPLETED! =====');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('👤 Users: ' . User::count());
        $this->command->info('   - Super Admin: 1');
        $this->command->info('   - Admin: 1');
        $this->command->info('   - Supervisor: 2');
        $this->command->info('   - Pengurus: 1');
        $this->command->info('   - Petugas: 5');
        $this->command->info('📍 Lokasi: ' . Lokasi::count());
        $this->command->info('📅 Jadwal: ' . JadwalKebersihan::count());
        $this->command->info('📝 Laporan: ' . ActivityReport::count());
        $this->command->info('⏰ Presensi: ' . Presensi::count());
        $this->command->info('⭐ Penilaian: ' . Penilaian::count());
        $this->command->info('');
        $this->command->info('🔐 Login Credentials:');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Super Admin : superadmin@eclean.test / password');
        $this->command->info('Admin       : admin@eclean.test / password');
        $this->command->info('Supervisor  : supervisor@eclean.test / password');
        $this->command->info('Supervisor 2: supervisor2@eclean.test / password');
        $this->command->info('Pengurus    : pengurus@eclean.test / password');
        $this->command->info('Petugas 1   : petugas1@eclean.test / password');
        $this->command->info('Petugas 2   : petugas2@eclean.test / password');
        $this->command->info('Petugas 3   : petugas3@eclean.test / password');
        $this->command->info('Petugas 4   : petugas4@eclean.test / password');
        $this->command->info('Petugas 5   : petugas5@eclean.test / password');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('');
        $this->command->info('🚀 You can now login at: /admin');
    }
}
