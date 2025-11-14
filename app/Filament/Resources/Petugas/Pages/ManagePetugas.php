<?php

namespace App\Filament\Resources\Petugas\Pages;

use App\Filament\Resources\Petugas\PetugasResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManagePetugas extends ManageRecords
{
    protected static string $resource = PetugasResource::class;

    public function mount(): void
    {
        parent::mount();

        // Show info notification on first load
        if (!session()->has('petugas_info_shown')) {
            Notification::make()
                ->info()
                ->title('👥 Manajemen Petugas')
                ->body('Di sini Anda dapat melihat daftar petugas dan mengaktifkan/menonaktifkan status mereka. Petugas yang non-aktif tidak dapat login ke aplikasi mobile.')
                ->persistent()
                ->send();

            session()->put('petugas_info_shown', true);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('activate_all')
                ->label('Aktifkan Semua')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aktifkan Semua Petugas?')
                ->modalDescription('Semua petugas akan diaktifkan dan dapat login ke aplikasi mobile.')
                ->modalSubmitActionLabel('Aktifkan Semua')
                ->action(function () {
                    $count = \App\Models\User::whereHas('roles', function ($query) {
                        $query->where('name', 'petugas');
                    })
                    ->where('is_active', false)
                    ->update(['is_active' => true]);

                    Notification::make()
                        ->success()
                        ->title('Berhasil')
                        ->body("{$count} petugas berhasil diaktifkan.")
                        ->send();
                })
                ->visible(fn () => \App\Models\User::whereHas('roles', function ($query) {
                    $query->where('name', 'petugas');
                })->where('is_active', false)->exists()),
        ];
    }
}
