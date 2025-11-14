<?php

namespace App\Filament\Resources\Lokasis\Pages;

use App\Filament\Resources\Lokasis\LokasiResource;
use App\Models\Lokasi;
use App\Services\BarcodeService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageLokasis extends ManageRecords
{
    protected static string $resource = LokasiResource::class;

    protected static ?string $title = 'Lokasi';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->hidden(fn () => auth()->user()->hasAnyRole(['petugas', 'pengurus', 'supervisor'])),

            Action::make('generate_all_barcode')
                ->label('Generate Semua Barcode')
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate Barcode untuk Semua Lokasi')
                ->modalDescription('Ini akan membuat Barcode baru untuk semua lokasi yang belum memiliki Barcode. Proses ini mungkin memakan waktu beberapa saat.')
                ->modalSubmitActionLabel('Generate')
                ->action(function () {
                    $barcodeService = new BarcodeService();
                    $count = $barcodeService->generateMissingBarcodes();

                    Notification::make()
                        ->success()
                        ->title('Barcode Berhasil Dibuat')
                        ->body("Berhasil membuat {$count} Barcode baru")
                        ->send();
                })
                ->visible(fn () => Lokasi::whereNull('qr_code')->orWhere('qr_code', '')->exists())
                ->hidden(fn () => auth()->user()->hasAnyRole(['petugas', 'pengurus'])),

            Action::make('print_barcode')
                ->label('Print Barcodes')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(route('filament.admin.resources.lokasis.print-qr'))
                ->openUrlInNewTab(),
        ];
    }
}
