<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\Penilaian;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PengurusStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()->hasRole('pengurus');
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Total Petugas
        $totalPetugas = User::role('petugas')->count();

        // Total Lokasi
        $totalLokasi = Lokasi::count();

        // Laporan Bulan Ini
        $reportsThisMonth = ActivityReport::where('tanggal', '>=', $thisMonth)->count();
        $reportsLastMonth = ActivityReport::whereBetween('tanggal', [$lastMonth, $lastMonthEnd])->count();
        $reportsTrend = $reportsLastMonth > 0
            ? (($reportsThisMonth - $reportsLastMonth) / $reportsLastMonth) * 100
            : 0;

        // Tingkat Persetujuan Bulan Ini
        $approvedThisMonth = ActivityReport::where('tanggal', '>=', $thisMonth)
            ->where('status', 'approved')
            ->count();
        $approvalRate = $reportsThisMonth > 0
            ? round(($approvedThisMonth / $reportsThisMonth) * 100, 1)
            : 0;

        $approvedLastMonth = ActivityReport::whereBetween('tanggal', [$lastMonth, $lastMonthEnd])
            ->where('status', 'approved')
            ->count();
        $reportsLastMonthTotal = ActivityReport::whereBetween('tanggal', [$lastMonth, $lastMonthEnd])->count();
        $lastMonthApprovalRate = $reportsLastMonthTotal > 0
            ? round(($approvedLastMonth / $reportsLastMonthTotal) * 100, 1)
            : 0;
        $approvalTrend = $approvalRate - $lastMonthApprovalRate;

        // Rating Rata-rata
        $avgRating = Penilaian::whereYear('periode_bulan', Carbon::now()->year)
            ->whereMonth('periode_bulan', Carbon::now()->month)
            ->avg('skor_total');
        $avgRating = $avgRating ? round($avgRating, 1) : 0;

        $lastMonthAvgRating = Penilaian::whereYear('periode_bulan', Carbon::now()->subMonth()->year)
            ->whereMonth('periode_bulan', Carbon::now()->subMonth()->month)
            ->avg('skor_total');
        $lastMonthAvgRating = $lastMonthAvgRating ? round($lastMonthAvgRating, 1) : 0;
        $ratingTrend = $avgRating - $lastMonthAvgRating;

        // Jadwal Hari Ini
        $schedulesToday = JadwalKebersihan::whereDate('tanggal', $today)->count();

        return [
            Stat::make('Total Petugas', $totalPetugas)
                ->description('Petugas kebersihan aktif')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->chart([7, 8, 8, 9, 10, 9, $totalPetugas]),

            Stat::make('Total Lokasi', $totalLokasi)
                ->description('Lokasi yang dikelola')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success')
                ->chart([12, 13, 14, 14, 15, 15, $totalLokasi]),

            Stat::make('Laporan Bulan Ini', $reportsThisMonth)
                ->description($reportsTrend >= 0
                    ? number_format($reportsTrend, 1) . '% peningkatan dari bulan lalu'
                    : number_format(abs($reportsTrend), 1) . '% penurunan dari bulan lalu')
                ->descriptionIcon($reportsTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($reportsTrend >= 0 ? 'success' : 'danger')
                ->chart(array_fill(0, 7, $reportsLastMonth / 7) + [$reportsThisMonth]),

            Stat::make('Tingkat Persetujuan', $approvalRate . '%')
                ->description($approvalTrend >= 0
                    ? '+' . number_format($approvalTrend, 1) . '% dari bulan lalu'
                    : number_format($approvalTrend, 1) . '% dari bulan lalu')
                ->descriptionIcon($approvalTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($approvalRate >= 80 ? 'success' : ($approvalRate >= 60 ? 'warning' : 'danger'))
                ->chart([
                    $lastMonthApprovalRate,
                    $lastMonthApprovalRate + ($approvalTrend / 4),
                    $lastMonthApprovalRate + ($approvalTrend / 2),
                    $lastMonthApprovalRate + ($approvalTrend * 0.75),
                    $approvalRate
                ]),

            Stat::make('Rating Rata-rata', $avgRating . ' / 100')
                ->description($ratingTrend >= 0
                    ? '+' . number_format($ratingTrend, 1) . ' dari bulan lalu'
                    : number_format($ratingTrend, 1) . ' dari bulan lalu')
                ->descriptionIcon($ratingTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($avgRating >= 80 ? 'success' : ($avgRating >= 60 ? 'warning' : 'danger'))
                ->chart([
                    $lastMonthAvgRating,
                    $lastMonthAvgRating + ($ratingTrend / 4),
                    $lastMonthAvgRating + ($ratingTrend / 2),
                    $lastMonthAvgRating + ($ratingTrend * 0.75),
                    $avgRating
                ]),

            Stat::make('Jadwal Hari Ini', $schedulesToday)
                ->description('Lokasi yang dijadwalkan dibersihkan hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart([5, 6, 7, 6, 8, 7, $schedulesToday]),
        ];
    }
}