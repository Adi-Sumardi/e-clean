<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class AdminSystemOverviewWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Laporan Bulanan (12 Bulan Terakhir)';
    }

    protected function getData(): array
    {
        $months = [];
        $data = [];

        // Get last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');

            $count = ActivityReport::whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year)
                ->count();

            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Laporan',
                    'data' => $data,
                    'backgroundColor' => '#6366f1',
                    'borderColor' => '#4f46e5',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }
}
