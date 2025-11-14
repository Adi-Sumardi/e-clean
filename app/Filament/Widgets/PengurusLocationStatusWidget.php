<?php

namespace App\Filament\Widgets;

use App\Models\Lokasi;
use Filament\Widgets\ChartWidget;

class PengurusLocationStatusWidget extends ChartWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->hasRole('pengurus');
    }

    public function getHeading(): ?string
    {
        return 'Status Kebersihan Lokasi';
    }

    public function getDescription(): ?string
    {
        return 'Berdasarkan kategori lokasi';
    }

    protected function getData(): array
    {
        // Get locations grouped by category and status
        $categories = Lokasi::select('kategori')
            ->distinct()
            ->pluck('kategori')
            ->toArray();

        $bersihData = [];
        $kotorData = [];
        $perluPerhatianData = [];

        foreach ($categories as $category) {
            $bersihData[] = Lokasi::where('kategori', $category)
                ->where('status_kebersihan', 'bersih')
                ->count();

            $kotorData[] = Lokasi::where('kategori', $category)
                ->where('status_kebersihan', 'kotor')
                ->count();

            $perluPerhatianData[] = Lokasi::where('kategori', $category)
                ->where('status_kebersihan', 'perlu_perhatian')
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Bersih',
                    'data' => $bersihData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Kotor',
                    'data' => $kotorData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Perlu Perhatian',
                    'data' => $perluPerhatianData,
                    'backgroundColor' => 'rgba(234, 179, 8, 0.8)',
                    'borderColor' => 'rgb(234, 179, 8)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => array_map(fn($cat) => ucfirst(str_replace('_', ' ', $cat)), $categories),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
