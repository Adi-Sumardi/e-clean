<?php

namespace App\Filament\Resources\LaporanKeterlambatans\Pages;

use App\Filament\Resources\LaporanKeterlambatans\LaporanKeterlambatanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanKeterlambatan extends ViewRecord
{
    protected static string $resource = LaporanKeterlambatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
