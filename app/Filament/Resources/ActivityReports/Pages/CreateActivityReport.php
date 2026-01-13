<?php

namespace App\Filament\Resources\ActivityReports\Pages;

use App\Filament\Resources\ActivityReports\ActivityReportResource;
use App\Models\GuestComplaint;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Js;

class CreateActivityReport extends CreateRecord
{
    protected static string $resource = ActivityReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCancelFormAction(): Action
    {
        $url = $this->getResource()::getUrl('index');

        return Action::make('cancel')
            ->label('Batal')
            ->alpineClickHandler('window.location.href = ' . Js::from($url))
            ->color('gray');
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

    protected function afterCreate(): void
    {
        // Auto-update pending guest complaints at this location to 'in_progress'
        $lokasiId = $this->record->lokasi_id;

        if ($lokasiId) {
            GuestComplaint::where('lokasi_id', $lokasiId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'in_progress',
                    'handled_by' => Auth::id(),
                    'handled_at' => now(),
                ]);
        }
    }
}
