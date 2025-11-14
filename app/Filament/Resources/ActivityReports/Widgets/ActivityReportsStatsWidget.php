<?php

namespace App\Filament\Resources\ActivityReports\Widgets;

use App\Models\ActivityReport;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActivityReportsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $query = ActivityReport::query();

        // Filter untuk petugas - hanya lihat laporan sendiri
        if ($user->hasRole('petugas')) {
            $query->where('petugas_id', $user->id);
        }

        $today = Carbon::today();

        $totalReports = (clone $query)->count();
        $todayReports = (clone $query)->whereDate('tanggal', $today)->count();
        $pendingReports = (clone $query)->where('status', 'draft')->count();
        $approvedReports = (clone $query)->where('status', 'approved')->count();
        $submittedReports = (clone $query)->where('status', 'submitted')->count();

        return [
            Stat::make('Total Laporan', $totalReports)
                ->color('primary')
                ->chart([7, 12, 15, 18, 20, 22, $totalReports]),

            Stat::make('Laporan Hari Ini', $todayReports)
                ->color($todayReports > 0 ? 'success' : 'gray')
                ->chart([0, 1, 3, $todayReports, 5]),

            Stat::make('Draft', $pendingReports)
                ->color($pendingReports > 0 ? 'warning' : 'gray'),

            Stat::make('Menunggu Approval', $submittedReports)
                ->color($submittedReports > 0 ? 'info' : 'gray'),

            Stat::make('Disetujui', $approvedReports)
                ->color('success')
                ->chart([5, 10, 15, 20, $approvedReports]),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}
