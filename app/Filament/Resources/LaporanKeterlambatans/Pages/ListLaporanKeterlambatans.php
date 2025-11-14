<?php

namespace App\Filament\Resources\LaporanKeterlambatans\Pages;

use App\Filament\Resources\LaporanKeterlambatans\LaporanKeterlambatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLaporanKeterlambatans extends ListRecords
{
    protected static string $resource = LaporanKeterlambatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
