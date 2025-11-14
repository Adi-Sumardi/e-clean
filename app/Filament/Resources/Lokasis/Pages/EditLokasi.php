<?php

namespace App\Filament\Resources\Lokasis\Pages;

use App\Filament\Resources\Lokasis\LokasiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLokasi extends EditRecord
{
    protected static string $resource = LokasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
