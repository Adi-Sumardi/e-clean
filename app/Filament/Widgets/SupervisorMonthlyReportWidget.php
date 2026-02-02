<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SupervisorMonthlyReportWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'Rekap Laporan Bulanan';
    }

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['supervisor', 'admin', 'super_admin', 'pengurus']);
    }

    protected function getStats(): array
    {
        $now = Carbon::now();
        $startDate = $now->copy()->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        $reports = ActivityReport::whereBetween('tanggal', [$startDate, $endDate])
            ->whereHas('lokasi.unit')
            ->get();

        $total = $reports->count();
        $ontime = $reports->where('reporting_status', 'ontime')->count();
        $late = $reports->where('reporting_status', 'late')->count();
        $expired = $reports->where('reporting_status', 'expired')->count();
        $avgRating = round($reports->whereNotNull('rating')->avg('rating') ?? 0, 1);

        $ontimePct = $total ? round($ontime / $total * 100, 1) : 0;
        $latePct = $total ? round($late / $total * 100, 1) : 0;
        $expiredPct = $total ? round($expired / $total * 100, 1) : 0;

        return [
            Stat::make('Total Laporan Bulan Ini', $total . ' laporan')
                ->description($now->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-o-document-chart-bar')
                ->color('primary')
                ->chart([10, 20, 30, 40, $total])
                ->url(route('filament.admin.pages.laporan-bulanan')),

            Stat::make('Tepat Waktu', $ontime . ' (' . $ontimePct . '%)')
                ->description('Laporan dilaporkan tepat waktu')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart([5, 10, 15, 20, $ontime]),

            Stat::make('Terlambat', $late . ' (' . $latePct . '%)')
                ->description('Laporan dilaporkan terlambat')
                ->descriptionIcon('heroicon-o-clock')
                ->color($late > 0 ? 'warning' : 'success')
                ->chart([0, 2, 4, 6, $late]),

            Stat::make('Tidak Lapor', $expired . ' (' . $expiredPct . '%)')
                ->description('Petugas tidak melaporkan')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($expired > 0 ? 'danger' : 'success')
                ->chart([0, 1, 3, 5, $expired]),

            Stat::make('Rata-rata Rating', $avgRating . '/5')
                ->description('Rating kualitas pekerjaan')
                ->descriptionIcon('heroicon-o-star')
                ->color($avgRating >= 4 ? 'success' : ($avgRating >= 3 ? 'warning' : 'danger'))
                ->chart([1, 2, 3, 4, (int) $avgRating]),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}
