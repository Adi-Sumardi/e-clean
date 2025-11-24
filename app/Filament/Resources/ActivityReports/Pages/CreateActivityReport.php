<?php

namespace App\Filament\Resources\ActivityReports\Pages;

use App\Filament\Resources\ActivityReports\ActivityReportResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateActivityReport extends CreateRecord
{
    protected static string $resource = ActivityReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-set approved_by and approved_at when creating with approved status
        if (isset($data['status']) && $data['status'] === 'approved' && !isset($data['approved_by'])) {
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
        }

        return $data;
    }
}
