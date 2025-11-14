<?php

namespace App\Console\Commands;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\LaporanKeterlambatan;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckMissedSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:check-missed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check jadwal kebersihan yang terlewat dan belum dilaporkan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for missed schedules...');

        $today = Carbon::today();
        $now = Carbon::now();

        // Ambil semua jadwal hari ini
        $jadwalHariIni = JadwalKebersihan::whereDate('tanggal', $today)
            ->with(['petugas', 'lokasi'])
            ->get();

        $missedCount = 0;

        foreach ($jadwalHariIni as $jadwal) {
            // Ambil time range untuk shift ini
            $timeRange = LaporanKeterlambatan::getShiftTimeRange($jadwal->shift);
            $endTime = Carbon::parse($today->format('Y-m-d') . ' ' . $timeRange['end']);

            // Cek apakah waktu shift sudah terlewat
            if ($now->greaterThan($endTime)) {
                // Cek apakah petugas sudah membuat laporan untuk jadwal ini
                $hasReport = ActivityReport::where('petugas_id', $jadwal->petugas_id)
                    ->where('lokasi_id', $jadwal->lokasi_id)
                    ->whereDate('tanggal', $today)
                    ->whereIn('status', ['submitted', 'approved'])
                    ->exists();

                if (!$hasReport) {
                    // Cek apakah sudah tercatat sebagai keterlambatan
                    $exists = LaporanKeterlambatan::where('jadwal_kebersihan_id', $jadwal->id)
                        ->where('tanggal', $today)
                        ->exists();

                    if (!$exists) {
                        // Catat sebagai keterlambatan
                        LaporanKeterlambatan::create([
                            'jadwal_kebersihan_id' => $jadwal->id,
                            'petugas_id' => $jadwal->petugas_id,
                            'lokasi_id' => $jadwal->lokasi_id,
                            'tanggal' => $today,
                            'shift' => $jadwal->shift,
                            'batas_waktu_mulai' => $timeRange['start'],
                            'batas_waktu_selesai' => $timeRange['end'],
                            'status' => 'terlewat',
                            'keterangan' => sprintf(
                                'Petugas %s tidak melaporkan pekerjaan kebersihan di %s untuk shift %s (jadwal: %s - %s)',
                                $jadwal->petugas->name,
                                $jadwal->lokasi->nama_lokasi,
                                ucfirst($jadwal->shift),
                                $timeRange['start'],
                                $timeRange['end']
                            ),
                            'waktu_terdeteksi' => $now,
                        ]);

                        $this->error(sprintf(
                            'MISSED: %s - %s - Shift %s',
                            $jadwal->petugas->name,
                            $jadwal->lokasi->nama_lokasi,
                            ucfirst($jadwal->shift)
                        ));

                        $missedCount++;
                    }
                }
            }
        }

        if ($missedCount > 0) {
            $this->info("Total missed schedules detected: {$missedCount}");
        } else {
            $this->info('No missed schedules found.');
        }

        return Command::SUCCESS;
    }
}
