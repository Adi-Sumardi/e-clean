<?php

namespace App\Filament\Resources\JadwalKebersihanans\Widgets;

use App\Models\JadwalKebersihan;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class JadwalKebersihanStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalJadwal = JadwalKebersihan::count();
        $jadwalHariIni = JadwalKebersihan::whereDate('tanggal', $today)->count();
        $jadwalMingguIni = JadwalKebersihan::whereBetween('tanggal', [$startOfWeek, $endOfWeek])->count();
        $jadwalBulanIni = JadwalKebersihan::whereBetween('tanggal', [$startOfMonth, $endOfMonth])->count();

        return [
            Stat::make('Total Jadwal', $totalJadwal)
                ->color('primary')
                ->chart([10, 20, 30, 40, 50, $totalJadwal]),

            Stat::make('Jadwal Hari Ini', $jadwalHariIni)
                ->color($jadwalHariIni > 0 ? 'success' : 'gray')
                ->chart([0, 2, 4, $jadwalHariIni, 8]),

            Stat::make('Jadwal Minggu Ini', $jadwalMingguIni)
                ->color($jadwalMingguIni > 0 ? 'info' : 'gray'),

            Stat::make('Jadwal Bulan Ini', $jadwalBulanIni)
                ->color($jadwalBulanIni > 0 ? 'warning' : 'gray')
                ->chart([10, 15, 20, 25, $jadwalBulanIni]),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
