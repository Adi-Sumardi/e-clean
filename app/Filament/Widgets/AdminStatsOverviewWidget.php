<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
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

        return [
            Stat::make('Total Lokasi Aktif', $totalLokasi)
                ->description('Lokasi yang sedang aktif')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3, $totalLokasi]),

            Stat::make('Total Petugas', $totalPetugas)
                ->description('Petugas kebersihan terdaftar')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary')
                ->chart([3, 2, 4, 3, 5, 4, 6, 5, $totalPetugas]),

            Stat::make('Jadwal Aktif', $totalJadwalAktif)
                ->description('Jadwal yang akan datang')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('warning')
                ->chart([5, 10, 8, 15, 12, 10, 14, 11, $totalJadwalAktif]),

            Stat::make('Laporan Bulan Ini', $totalLaporanBulanIni)
                ->description($laporanDiapprove . ' disetujui (' . $approvalRate . '%)')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color('info')
                ->chart([10, 15, 12, 18, 16, 20, 22, 19, $totalLaporanBulanIni]),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }
}
