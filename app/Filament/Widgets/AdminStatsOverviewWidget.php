<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Enable polling untuk real-time updates (refresh every 30 seconds)
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Cache for 5 minutes to reduce database load
        return Cache::remember('admin-stats-' . now()->format('Y-m-d-H-i'), 300, function () {
            $totalLokasi = Lokasi::where('is_active', true)->count();
            $totalPetugas = User::whereHas('roles', function ($query) {
                $query->where('name', 'petugas');
            })->count();

            $totalJadwalAktif = JadwalKebersihan::where('status', 'active')
                ->whereDate('tanggal', '>=', today())
                ->count();

            $totalLaporanBulanIni = ActivityReport::whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->count();

            $laporanDiapprove = ActivityReport::where('status', 'approved')
                ->whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->count();

            $approvalRate = $totalLaporanBulanIni > 0
                ? round(($laporanDiapprove / $totalLaporanBulanIni) * 100, 1)
                : 0;

            // Real chart data - Lokasi trend (last 7 days)
            $lokasiChart = $this->getLokasiTrend();

            // Real chart data - Petugas trend (last 7 days)
            $petugasChart = $this->getPetugasTrend();

            // Real chart data - Jadwal trend (last 7 days)
            $jadwalChart = $this->getJadwalTrend();

            // Real chart data - Laporan trend (last 7 days)
            $laporanChart = $this->getLaporanTrend();

            return [
                Stat::make('Total Lokasi Aktif', $totalLokasi)
                    ->description('Lokasi yang sedang aktif')
                    ->descriptionIcon('heroicon-o-building-office-2')
                    ->color('success')
                    ->chart($lokasiChart),

                Stat::make('Total Petugas', $totalPetugas)
                    ->description('Petugas kebersihan terdaftar')
                    ->descriptionIcon('heroicon-o-user-group')
                    ->color('primary')
                    ->chart($petugasChart),

                Stat::make('Jadwal Aktif', $totalJadwalAktif)
                    ->description('Jadwal yang akan datang')
                    ->descriptionIcon('heroicon-o-calendar-days')
                    ->color('warning')
                    ->chart($jadwalChart),

                Stat::make('Laporan Bulan Ini', $totalLaporanBulanIni)
                    ->description($laporanDiapprove . ' disetujui (' . $approvalRate . '%)')
                    ->descriptionIcon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->chart($laporanChart),
            ];
        });
    }

    /**
     * Get real chart data for Lokasi trend (last 7 days)
     */
    private function getLokasiTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = Lokasi::where('is_active', true)
                ->whereDate('created_at', '<=', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    /**
     * Get real chart data for Petugas trend (last 7 days)
     */
    private function getPetugasTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = User::whereHas('roles', function ($query) {
                $query->where('name', 'petugas');
            })->whereDate('created_at', '<=', $date)->count();
            $data[] = $count;
        }
        return $data;
    }

    /**
     * Get real chart data for Jadwal trend (last 7 days)
     */
    private function getJadwalTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = JadwalKebersihan::whereDate('tanggal', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    /**
     * Get real chart data for Laporan trend (last 7 days)
     */
    private function getLaporanTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = ActivityReport::whereDate('tanggal', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin']);
    }
}
