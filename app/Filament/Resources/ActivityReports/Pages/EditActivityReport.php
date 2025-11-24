<?php

namespace App\Filament\Resources\ActivityReports\Pages;

use App\Filament\Resources\ActivityReports\ActivityReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditActivityReport extends EditRecord
{
    protected static string $resource = ActivityReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn () => Auth::user()->hasRole('pengurus')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-set approved_by and approved_at when approving via edit
        if (isset($data['status']) && $data['status'] === 'approved') {
            if (!isset($data['approved_by']) || empty($data['approved_by'])) {
                $data['approved_by'] = Auth::id();
            }
            if (!isset($data['approved_at']) || empty($data['approved_at'])) {
                $data['approved_at'] = now();
            }
        }

        return $data;
    }
}
