<?php

namespace App\Console\Commands;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\JadwalOb;
use App\Models\JadwalSatpam;
use App\Models\JadwalToko;
use App\Models\LaporanKeterlambatan;
use App\Models\LaporanOb;
use App\Models\LaporanSatpam;
use App\Models\LaporanToko;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckMissedSchedules extends Command
{
    protected $signature = 'schedule:check-missed';

    protected $description = 'Cek jadwal semua jenis petugas yang terlewat & belum dilaporkan, catat keterlambatan';

    /**
     * Konfigurasi per domain: model jadwal & model laporan.
     */
    private function domains(): array
    {
        return [
            'kebersihan' => ['jadwal' => JadwalKebersihan::class, 'laporan' => ActivityReport::class],
            'satpam' => ['jadwal' => JadwalSatpam::class, 'laporan' => LaporanSatpam::class],
            'ob' => ['jadwal' => JadwalOb::class, 'laporan' => LaporanOb::class],
            'toko' => ['jadwal' => JadwalToko::class, 'laporan' => LaporanToko::class],
        ];
    }

    public function handle()
    {
        $this->info('Checking for missed schedules (semua domain)...');

        $today = Carbon::today();
        $now = Carbon::now();
        $totalMissed = 0;

        foreach ($this->domains() as $domain => $cfg) {
            $jadwalModel = $cfg['jadwal'];
            $laporanModel = $cfg['laporan'];

            $jadwalHariIni = $jadwalModel::whereDate('tanggal', $today)
                ->with(['petugas', 'lokasi'])
                ->get();

            foreach ($jadwalHariIni as $jadwal) {
                $timeRange = LaporanKeterlambatan::getShiftTimeRange($jadwal->shift);
                $endTime = Carbon::parse($today->format('Y-m-d') . ' ' . $timeRange['end']);

                // Shift belum lewat → lewati
                if (! $now->greaterThan($endTime)) {
                    continue;
                }

                // Sudah ada laporan untuk jadwal ini?
                $hasReport = $laporanModel::where('petugas_id', $jadwal->petugas_id)
                    ->where('lokasi_id', $jadwal->lokasi_id)
                    ->whereDate('tanggal', $today)
                    ->whereIn('status', ['submitted', 'approved'])
                    ->exists();

                if ($hasReport) {
                    continue;
                }

                // Sudah tercatat sebagai keterlambatan? (dedupe per domain)
                $exists = LaporanKeterlambatan::where('domain', $domain)
                    ->where('petugas_id', $jadwal->petugas_id)
                    ->where('lokasi_id', $jadwal->lokasi_id)
                    ->whereDate('tanggal', $today)
                    ->where('shift', $jadwal->shift)
                    ->exists();

                if ($exists) {
                    continue;
                }

                LaporanKeterlambatan::create([
                    'domain' => $domain,
                    // FK kebersihan hanya untuk domain kebersihan
                    'jadwal_kebersihan_id' => $domain === 'kebersihan' ? $jadwal->id : null,
                    'petugas_id' => $jadwal->petugas_id,
                    'lokasi_id' => $jadwal->lokasi_id,
                    'tanggal' => $today,
                    'shift' => $jadwal->shift,
                    'batas_waktu_mulai' => $timeRange['start'],
                    'batas_waktu_selesai' => $timeRange['end'],
                    'status' => 'terlewat',
                    'keterangan' => sprintf(
                        'Petugas %s tidak melaporkan tugas di %s untuk shift %s (%s - %s)',
                        $jadwal->petugas->name ?? '-',
                        $jadwal->lokasi->nama_lokasi ?? '-',
                        ucfirst($jadwal->shift),
                        $timeRange['start'],
                        $timeRange['end']
                    ),
                    'waktu_terdeteksi' => $now,
                ]);

                $this->error(sprintf(
                    'MISSED [%s]: %s - %s - Shift %s',
                    $domain,
                    $jadwal->petugas->name ?? '-',
                    $jadwal->lokasi->nama_lokasi ?? '-',
                    ucfirst($jadwal->shift)
                ));

                $totalMissed++;
            }
        }

        $this->info($totalMissed > 0
            ? "Total keterlambatan terdeteksi: {$totalMissed}"
            : 'Tidak ada jadwal terlewat.');

        return Command::SUCCESS;
    }
}
