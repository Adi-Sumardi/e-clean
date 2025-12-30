<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ReportingStatusWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return Cache::remember('reporting-status-stats-' . now()->format('Y-m-d-H'), 300, function () {
            $thisMonth = now();

            // Count by reporting status this month
            $onTimeCount = ActivityReport::where('reporting_status', ActivityReport::REPORTING_STATUS_ONTIME)
                ->whereMonth('tanggal', $thisMonth->month)
                ->whereYear('tanggal', $thisMonth->year)
                ->count();

            $lateCount = ActivityReport::where('reporting_status', ActivityReport::REPORTING_STATUS_LATE)
                ->whereMonth('tanggal', $thisMonth->month)
                ->whereYear('tanggal', $thisMonth->year)
                ->count();

            $expiredCount = ActivityReport::where('reporting_status', ActivityReport::REPORTING_STATUS_EXPIRED)
                ->whereMonth('tanggal', $thisMonth->month)
                ->whereYear('tanggal', $thisMonth->year)
                ->count();

            $totalReports = $onTimeCount + $lateCount + $expiredCount;

            // Calculate percentages
            $onTimePercent = $totalReports > 0 ? round(($onTimeCount / $totalReports) * 100, 1) : 0;
            $latePercent = $totalReports > 0 ? round(($lateCount / $totalReports) * 100, 1) : 0;
            $expiredPercent = $totalReports > 0 ? round(($expiredCount / $totalReports) * 100, 1) : 0;

            // Average late minutes for late reports
            $avgLateMinutes = ActivityReport::where('reporting_status', ActivityReport::REPORTING_STATUS_LATE)
                ->whereMonth('tanggal', $thisMonth->month)
                ->whereYear('tanggal', $thisMonth->year)
                ->whereNotNull('late_minutes')
                ->avg('late_minutes');
            $avgLateMinutes = $avgLateMinutes ? round($avgLateMinutes) : 0;

            // Trends (last 7 days)
            $onTimeTrend = $this->getStatusTrend(ActivityReport::REPORTING_STATUS_ONTIME);
            $lateTrend = $this->getStatusTrend(ActivityReport::REPORTING_STATUS_LATE);
            $expiredTrend = $this->getStatusTrend(ActivityReport::REPORTING_STATUS_EXPIRED);

            return [
                Stat::make('Tepat Waktu', $onTimeCount)
                    ->description($onTimePercent . '% dari total laporan bulan ini')
                    ->descriptionIcon('heroicon-o-check-circle')
                    ->color('success')
                    ->chart($onTimeTrend),

                Stat::make('Terlambat', $lateCount)
                    ->description($latePercent . '% - Rata-rata ' . $avgLateMinutes . ' menit')
                    ->descriptionIcon('heroicon-o-clock')
                    ->color('warning')
                    ->chart($lateTrend),

                Stat::make('Tidak Lapor', $expiredCount)
                    ->description($expiredPercent . '% - Auto-generated oleh sistem')
                    ->descriptionIcon('heroicon-o-x-circle')
                    ->color('danger')
                    ->chart($expiredTrend),

                Stat::make('Total Laporan', $totalReports)
                    ->description('Bulan ' . $thisMonth->translatedFormat('F Y'))
                    ->descriptionIcon('heroicon-o-document-text')
                    ->color('info'),
            ];
        });
    }

    private function getStatusTrend(string $status): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = ActivityReport::where('reporting_status', $status)
                ->whereDate('tanggal', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor']);
    }
}
