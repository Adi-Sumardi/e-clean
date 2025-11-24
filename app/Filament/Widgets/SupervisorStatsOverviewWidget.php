<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\LaporanKeterlambatan;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SupervisorStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['supervisor', 'admin']);
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;

        // Total Petugas Aktif
        $totalPetugas = User::whereHas('roles', function ($query) {
            $query->where('name', 'petugas');
        })->count();

        // Laporan Pending Approval (status submitted)
        $pendingApproval = ActivityReport::where('status', 'submitted')->count();

        // Jadwal Hari Ini
        $jadwalHariIni = JadwalKebersihan::whereDate('tanggal', $today)->count();

        // Laporan Terselesaikan Hari Ini
        $laporanHariIni = ActivityReport::whereDate('tanggal', $today)
            ->whereIn('status', ['submitted', 'approved'])
            ->count();

        // Laporan Bulan Ini
        $laporanBulanIni = ActivityReport::whereMonth('tanggal', $thisMonth)
            ->whereYear('tanggal', $thisYear)
            ->whereIn('status', ['submitted', 'approved'])
            ->count();

        // Keterlambatan Hari Ini
        $keterlambatanHariIni = LaporanKeterlambatan::whereDate('tanggal', $today)->count();

        // Completion Rate Hari Ini (%)
        $completionRate = $jadwalHariIni > 0
            ? round(($laporanHariIni / $jadwalHariIni) * 100, 1)
            : 0;

        return [
            // Pending Approval - Priority tinggi
            Stat::make('Menunggu Approval', $pendingApproval . ' laporan')
                ->description($pendingApproval > 0 ? 'Perlu ditinjau dan disetujui' : 'Semua laporan sudah diproses')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingApproval > 5 ? 'danger' : ($pendingApproval > 0 ? 'warning' : 'success'))
                ->chart([3, 5, $pendingApproval, 4, 2]),

            // Jadwal & Laporan Hari Ini
            Stat::make('Jadwal Hari Ini', $jadwalHariIni . ' jadwal')
                ->description($laporanHariIni . ' laporan selesai (' . $completionRate . '%)')
                ->descriptionIcon('heroicon-o-calendar')
                ->color($completionRate >= 80 ? 'success' : ($completionRate >= 50 ? 'warning' : 'danger'))
                ->chart([0, 5, 10, $laporanHariIni, 15]),

            // Total Petugas
            Stat::make('Total Petugas', $totalPetugas . ' petugas')
                ->description('Petugas aktif terdaftar')
                ->descriptionIcon('heroicon-o-users')
                ->color('info')
                ->chart([5, 10, 15, $totalPetugas, 20]),

            // Laporan Bulan Ini
            Stat::make('Laporan Bulan Ini', $laporanBulanIni . ' laporan')
                ->description('Total laporan di ' . Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-o-document-text')
                ->color('success')
                ->chart([10, 20, 30, 40, $laporanBulanIni]),

            // Keterlambatan
            Stat::make('Keterlambatan', $keterlambatanHariIni . ' petugas')
                ->description($keterlambatanHariIni > 0 ? 'Tidak melaporkan pekerjaan hari ini' : 'Tidak ada keterlambatan')
                ->descriptionIcon($keterlambatanHariIni > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-badge')
                ->color($keterlambatanHariIni > 0 ? 'danger' : 'success')
                ->chart([0, 1, 2, $keterlambatanHariIni, 3]),
        ];
    }

    protected function getColumns(): int
    {
        return 5; // 5 columns layout
    }
}
