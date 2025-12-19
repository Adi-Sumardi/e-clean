<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageUnits extends ManageRecords
{
    protected static string $resource = UnitResource::class;

    protected static ?string $title = 'Unit';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor'])),
        ];
    }
}
