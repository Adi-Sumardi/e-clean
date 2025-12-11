<?php

namespace App\Filament\Resources\ActivityReports\Pages;

use App\Exports\ActivityReportsExport;
use App\Filament\Resources\ActivityReports\ActivityReportResource;
use App\Filament\Resources\ActivityReports\Widgets\ActivityReportsStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;

class ListActivityReports extends ListRecords
{
    protected static string $resource = ActivityReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $query = $this->getFilteredTableQuery();
                    return Excel::download(
                        new ActivityReportsExport($query),
                        'laporan-kegiatan-' . now()->format('Y-m-d-His') . '.xlsx'
                    );
                })
                ->visible(fn () => Auth::user()->hasAnyRole(['pengurus', 'supervisor', 'admin', 'super_admin'])),

            CreateAction::make()
                ->label('Buat Laporan Baru')
                ->icon('heroicon-o-plus-circle')
                ->hidden(fn () => Auth::user()->hasRole('pengurus')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ActivityReportsStatsWidget::class,
        ];
    }
}
