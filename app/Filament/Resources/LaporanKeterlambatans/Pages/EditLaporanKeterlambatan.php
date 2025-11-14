<?php

namespace App\Filament\Resources\LaporanKeterlambatans\Pages;

use App\Filament\Resources\LaporanKeterlambatans\LaporanKeterlambatanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLaporanKeterlambatan extends EditRecord
{
    protected static string $resource = LaporanKeterlambatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
