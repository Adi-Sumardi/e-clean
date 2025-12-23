<?php

namespace App\Filament\Resources\Lokasis\Pages;

use App\Filament\Resources\Lokasis\LokasiResource;
use App\Models\Lokasi;
use App\Services\QRCodeService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageLokasis extends ManageRecords
{
    protected static string $resource = LokasiResource::class;

    protected static ?string $title = 'Lokasi';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor'])),

            Action::make('generate_all_qrcode')
                ->label('Generate Semua QR Code')
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate QR Code untuk Semua Lokasi')
                ->modalDescription('Ini akan membuat QR Code baru untuk semua lokasi yang belum memiliki QR Code. Proses ini mungkin memakan waktu beberapa saat.')
                ->modalSubmitActionLabel('Generate')
                ->action(function () {
                    $qrCodeService = new QRCodeService();
                    $count = $qrCodeService->generateMissingQRCodes();

                    Notification::make()
                        ->success()
                        ->title('QR Code Berhasil Dibuat')
                        ->body("Berhasil membuat {$count} QR Code baru")
                        ->send();
                })
                ->visible(fn () => Lokasi::whereNull('qr_code')->orWhere('qr_code', '')->exists())
                ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor'])),

            Action::make('print_qrcode')
                ->label('Print QR Codes')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(route('filament.admin.resources.lokasis.print-qr'))
                ->openUrlInNewTab(),
        ];
    }
}
