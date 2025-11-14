<?php

namespace App\Filament\Resources\ActivityReports\Pages;

use App\Exports\ActivityReportsExport;
use App\Filament\Resources\ActivityReports\ActivityReportResource;
use App\Filament\Resources\ActivityReports\Widgets\ActivityReportsStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRecords;
use Maatwebsite\Excel\Facades\Excel;

class ManageActivityReports extends ManageRecords
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
                ->visible(fn () => auth()->user()->hasAnyRole(['pengurus', 'supervisor', 'admin', 'super_admin'])),

            CreateAction::make()
                ->label('Buat Laporan Baru')
                ->icon('heroicon-o-plus-circle')
                ->mutateFormDataUsing(function (array $data): array {
                    // Auto-set approved_by and approved_at when creating with approved status
                    if (isset($data['status']) && $data['status'] === 'approved' && !isset($data['approved_by'])) {
                        $data['approved_by'] = auth()->id();
                        $data['approved_at'] = now();
                    }
                    return $data;
                })
                ->hidden(fn () => auth()->user()->hasAnyRole(['petugas', 'pengurus'])),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ActivityReportsStatsWidget::class,
        ];
    }

    // This method is NOT called for ManageRecords - we need to configure table actions instead
    // The EditAction needs to be configured in ActivityReportResource::table()
}
