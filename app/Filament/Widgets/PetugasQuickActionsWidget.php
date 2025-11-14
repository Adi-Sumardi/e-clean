<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class PetugasQuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.petugas-quick-actions';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->hasRole('petugas');
    }

    protected function getViewData(): array
    {
        $userId = auth()->id();
        $today = Carbon::today();

        $todayReports = ActivityReport::where('petugas_id', $userId)
            ->whereDate('tanggal', $today)
            ->count();

        $pendingReports = ActivityReport::where('petugas_id', $userId)
            ->where('status', 'draft')
            ->count();

        return [
            'todayReports' => $todayReports,
            'pendingReports' => $pendingReports,
        ];
    }
}
