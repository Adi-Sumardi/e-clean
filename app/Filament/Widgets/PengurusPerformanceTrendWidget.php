<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PengurusPerformanceTrendWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('pengurus');
    }

    public function getHeading(): ?string
    {
        return 'Trend Laporan 7 Hari Terakhir';
    }

    public function getDescription(): ?string
    {
        return 'Perbandingan laporan approved vs rejected';
    }

    protected function getData(): array
    {
        $days = collect();
        $approvedData = [];
        $rejectedData = [];
        $labels = [];

        // Get last 7 days data
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            $approved = ActivityReport::whereDate('tanggal', $date)
                ->where('status', 'approved')
                ->count();

            $rejected = ActivityReport::whereDate('tanggal', $date)
                ->where('status', 'rejected')
                ->count();

            $approvedData[] = $approved;
            $rejectedData[] = $rejected;
            $labels[] = $date->format('d M');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Approved',
                    'data' => $approvedData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Rejected',
                    'data' => $rejectedData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
