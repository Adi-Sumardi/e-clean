<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\LaporanKeterlambatan;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PetugasStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()->hasRole('petugas');
    }

    protected function getStats(): array
    {
        $userId = Auth::id();
        $today = Carbon::today();

        // Jadwal lokasi hari ini
        $jadwalHariIni = JadwalKebersihan::where('petugas_id', $userId)
            ->whereDate('tanggal', $today)
            ->with('lokasi')
            ->get();

        $jumlahLokasi = $jadwalHariIni->count();

        if ($jumlahLokasi == 0) {
            $namaLokasiValue = 'Tidak ada jadwal';
            $namaLokasiDesc = 'Hari ini libur';
        } else {
            // Tampilkan semua lokasi dengan format: Nama Lokasi - Shift
            $lokasiList = $jadwalHariIni->map(function ($jadwal) {
                $shift = ucfirst($jadwal->shift);
                return $jadwal->lokasi->nama_lokasi . ' - ' . $shift;
            })->implode("\n");

            $namaLokasiValue = $jumlahLokasi . ' Lokasi';
            $namaLokasiDesc = $lokasiList;
        }

        // Today's tasks
        $todayReports = ActivityReport::where('petugas_id', $userId)
            ->whereDate('tanggal', $today)
            ->count();

        // Pending tasks
        $pendingReports = ActivityReport::where('petugas_id', $userId)
            ->whereIn('status', ['draft', 'revision'])
            ->count();

        // Time-based checking untuk jadwal hari ini
        $now = Carbon::now();
        $urgentTasks = [];
        $missedTasks = [];

        foreach ($jadwalHariIni as $jadwal) {
            // Cek apakah sudah ada laporan untuk jadwal ini
            $hasReport = ActivityReport::where('petugas_id', $userId)
                ->where('lokasi_id', $jadwal->lokasi_id)
                ->whereDate('tanggal', $today)
                ->whereIn('status', ['submitted', 'approved'])
                ->exists();

            if (!$hasReport) {
                // Ambil time range untuk shift ini
                $timeRange = LaporanKeterlambatan::getShiftTimeRange($jadwal->shift);
                $startTime = Carbon::parse($today->format('Y-m-d') . ' ' . $timeRange['start']);
                $endTime = Carbon::parse($today->format('Y-m-d') . ' ' . $timeRange['end']);

                // Cek status waktu
                if ($now->greaterThan($endTime)) {
                    // Sudah terlewat
                    $missedTasks[] = $jadwal->lokasi->nama_lokasi . ' - ' . ucfirst($jadwal->shift);
                } elseif ($now->between($startTime, $endTime)) {
                    // Sedang dalam waktu shift (urgent!)
                    $urgentTasks[] = $jadwal->lokasi->nama_lokasi . ' - ' . ucfirst($jadwal->shift);
                }
            }
        }

        // Build description untuk Tugas Pending
        $totalPendingTasks = $pendingReports + count($urgentTasks) + count($missedTasks);
        $tugasPendingDesc = '';
        $tugasPendingColor = 'success';
        $tugasPendingIcon = 'heroicon-o-check-badge';

        if (count($missedTasks) > 0) {
            $tugasPendingDesc = '⚠️ ' . count($missedTasks) . ' jadwal terlewat!' . "\n" . implode("\n", array_slice($missedTasks, 0, 2));
            if (count($missedTasks) > 2) {
                $tugasPendingDesc .= "\n+" . (count($missedTasks) - 2) . " lainnya";
            }
            $tugasPendingColor = 'danger';
            $tugasPendingIcon = 'heroicon-o-exclamation-triangle';
        } elseif (count($urgentTasks) > 0) {
            $tugasPendingDesc = '🔔 ' . count($urgentTasks) . ' jadwal sedang berjalan!' . "\n" . implode("\n", array_slice($urgentTasks, 0, 2));
            if (count($urgentTasks) > 2) {
                $tugasPendingDesc .= "\n+" . (count($urgentTasks) - 2) . " lainnya";
            }
            $tugasPendingColor = 'warning';
            $tugasPendingIcon = 'heroicon-o-clock';
        } elseif ($pendingReports > 0) {
            $tugasPendingDesc = 'Ada laporan draft yang perlu diselesaikan';
            $tugasPendingColor = 'info';
            $tugasPendingIcon = 'heroicon-o-document-text';
        } else {
            $tugasPendingDesc = 'Semua tugas sudah selesai!';
        }

        // Real chart data - Jadwal trend (last 7 days)
        $jadwalChart = $this->getJadwalChart($userId);

        // Real chart data - Laporan trend (last 7 days)
        $laporanChart = $this->getLaporanChart($userId);

        // Real chart data - Tugas pending trend (last 7 days)
        $pendingChart = $this->getPendingChart($userId);

        return [
            // Lokasi Hari Ini
            Stat::make('Lokasi Hari Ini', $namaLokasiValue)
                ->description($namaLokasiDesc)
                ->descriptionIcon('heroicon-o-map-pin')
                ->color($jumlahLokasi > 0 ? 'info' : 'gray')
                ->chart($jadwalChart),

            // Laporan Hari Ini
            Stat::make('Laporan Hari Ini', $todayReports . ' laporan')
                ->description($todayReports > 0 ? 'Kerja bagus! Tetap semangat' : 'Belum ada laporan dibuat')
                ->descriptionIcon('heroicon-o-document-text')
                ->color($todayReports > 0 ? 'success' : 'gray')
                ->chart($laporanChart),

            // Tugas yang Perlu Diselesaikan (dengan time-based warning)
            Stat::make('Tugas Pending', $totalPendingTasks . ' tugas')
                ->description($tugasPendingDesc)
                ->descriptionIcon($tugasPendingIcon)
                ->color($tugasPendingColor)
                ->chart($pendingChart),
        ];
    }

    /**
     * Get real chart data for Jadwal (last 7 days)
     */
    private function getJadwalChart(int $userId): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = JadwalKebersihan::where('petugas_id', $userId)
                ->whereDate('tanggal', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    /**
     * Get real chart data for Laporan (last 7 days)
     */
    private function getLaporanChart(int $userId): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = ActivityReport::where('petugas_id', $userId)
                ->whereDate('tanggal', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    /**
     * Get real chart data for Pending tasks (last 7 days)
     */
    private function getPendingChart(int $userId): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = ActivityReport::where('petugas_id', $userId)
                ->whereIn('status', ['draft', 'revision'])
                ->whereDate('created_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    protected function getColumns(): int
    {
        return 3; // 3 columns layout
    }
}
