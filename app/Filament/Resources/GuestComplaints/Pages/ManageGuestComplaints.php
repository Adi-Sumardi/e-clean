<?php

namespace App\Filament\Resources\GuestComplaints\Pages;

use App\Filament\Resources\GuestComplaints\GuestComplaintResource;
use Filament\Resources\Pages\ManageRecords;

class ManageGuestComplaints extends ManageRecords
{
    protected static string $resource = GuestComplaintResource::class;

    protected static ?string $title = 'Keluhan Tamu';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
