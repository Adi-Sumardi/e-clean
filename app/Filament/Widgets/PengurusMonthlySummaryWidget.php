<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PengurusMonthlySummaryWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('pengurus');
    }

    public function getHeading(): ?string
    {
        return 'Ringkasan Laporan Bulanan';
    }

    public function getDescription(): ?string
    {
        return 'Status laporan kegiatan bulan ini';
    }

    protected function getData(): array
    {
        $thisMonth = Carbon::now()->startOfMonth();

        // Get report counts by status
        $pending = ActivityReport::where('tanggal', '>=', $thisMonth)
            ->where('status', 'pending')
            ->count();

        $approved = ActivityReport::where('tanggal', '>=', $thisMonth)
            ->where('status', 'approved')
            ->count();

        $rejected = ActivityReport::where('tanggal', '>=', $thisMonth)
            ->where('status', 'rejected')
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Laporan',
                    'data' => [$approved, $pending, $rejected],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',    // green for approved
                        'rgb(234, 179, 8)',     // yellow for pending
                        'rgb(239, 68, 68)',     // red for rejected
                    ],
                    'borderColor' => [
                        'rgb(22, 163, 74)',
                        'rgb(202, 138, 4)',
                        'rgb(220, 38, 38)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => [
                'Approved (' . $approved . ')',
                'Pending (' . $pending . ')',
                'Rejected (' . $rejected . ')'
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'maintainAspectRatio' => true,
        ];
    }
}