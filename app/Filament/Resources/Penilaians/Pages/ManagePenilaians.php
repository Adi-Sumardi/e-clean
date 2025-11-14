<?php

namespace App\Filament\Resources\Penilaians\Pages;

use App\Exports\PenilaianExport;
use App\Filament\Resources\Penilaians\PenilaianResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Maatwebsite\Excel\Facades\Excel;

class ManagePenilaians extends ManageRecords
{
    protected static string $resource = PenilaianResource::class;

    public function mount(): void
    {
        parent::mount();

        // Show info notification on first load
        if (!session()->has('penilaian_info_shown')) {
            Notification::make()
                ->info()
                ->title('📊 Penilaian Otomatis')
                ->body('Penilaian dibuat otomatis oleh sistem ketika supervisor meng-approve laporan kegiatan. Anda hanya bisa melihat dan menambahkan catatan.')
                ->persistent()
                ->send();

            session()->put('penilaian_info_shown', true);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $query = $this->getFilteredTableQuery();
                    return Excel::download(
                        new PenilaianExport($query),
                        'penilaian-petugas-' . now()->format('Y-m-d-His') . '.xlsx'
                    );
                })
                ->visible(fn () => auth()->user()->hasAnyRole(['pengurus', 'supervisor', 'admin', 'super_admin'])),
        ];
    }
}
