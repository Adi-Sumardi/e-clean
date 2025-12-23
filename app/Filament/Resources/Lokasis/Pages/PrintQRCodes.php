<?php

namespace App\Filament\Resources\Lokasis\Pages;

use App\Filament\Resources\Lokasis\LokasiResource;
use App\Models\Lokasi;
use App\Services\QRCodeService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class PrintQRCodes extends Page
{
    protected static string $resource = LokasiResource::class;

    protected string $view = 'filament.resources.lokasis.pages.print-qr-codes';

    protected static ?string $title = 'Cetak QR Code Lokasi';

    public $lokasis = [];

    public function mount(): void
    {
        $this->loadLokasis();
    }

    public function loadLokasis(): void
    {
        // Get all active locations
        $this->lokasis = Lokasi::where('is_active', true)
            ->orderBy('kode_lokasi')
            ->get()
            ->map(function ($lokasi) {
                // Check if QR code file actually exists
                $qrCodeExists = false;
                if ($lokasi->qr_code) {
                    $qrCodeExists = Storage::disk('public')->exists($lokasi->qr_code);
                }

                return [
                    'id' => $lokasi->id,
                    'kode_lokasi' => $lokasi->kode_lokasi,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'kategori' => $lokasi->kategori,
                    'qr_code_url' => $qrCodeExists ? asset('storage/' . $lokasi->qr_code) : null,
                    'qr_code_exists' => $qrCodeExists,
                ];
            })
            ->toArray();
    }

    public function generateQRCode(int $lokasiId): void
    {
        $lokasi = Lokasi::find($lokasiId);

        if (!$lokasi) {
            Notification::make()
                ->title('Error')
                ->body('Lokasi tidak ditemukan')
                ->danger()
                ->send();
            return;
        }

        $qrCodeService = new QRCodeService();
        $qrCodeService->generateForLokasi($lokasi);

        Notification::make()
            ->title('QR Code Berhasil Dibuat')
            ->body('QR Code untuk ' . $lokasi->nama_lokasi . ' telah dibuat')
            ->success()
            ->send();

        // Reload data
        $this->loadLokasis();
    }

    public function generateAllMissing(): void
    {
        $qrCodeService = new QRCodeService();
        $count = 0;

        $lokasis = Lokasi::where('is_active', true)->get();

        foreach ($lokasis as $lokasi) {
            $fileExists = $lokasi->qr_code && Storage::disk('public')->exists($lokasi->qr_code);

            if (!$fileExists) {
                $qrCodeService->generateForLokasi($lokasi);
                $count++;
            }
        }

        if ($count > 0) {
            Notification::make()
                ->title('QR Code Berhasil Dibuat')
                ->body("Berhasil membuat {$count} QR Code")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Semua QR Code Tersedia')
                ->body('Tidak ada QR Code yang perlu dibuat')
                ->info()
                ->send();
        }

        // Reload data
        $this->loadLokasis();
    }
}
