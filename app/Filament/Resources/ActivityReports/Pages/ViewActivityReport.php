<?php

namespace App\Filament\Resources\ActivityReports\Pages;

use App\Filament\Resources\ActivityReports\ActivityReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewActivityReport extends ViewRecord
{
    protected static string $resource = ActivityReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor'])),
            Actions\DeleteAction::make()
                ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin'])),
        ];
    }
}
