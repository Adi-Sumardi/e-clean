<?php

namespace App\Filament\Resources\Lokasis\Pages;

use App\Filament\Resources\Lokasis\LokasiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLokasis extends ListRecords
{
    protected static string $resource = LokasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
