<?php

namespace App\Filament\Resources\Lokasis\Pages;

use App\Filament\Resources\Lokasis\LokasiResource;
use App\Models\Lokasi;
use App\Services\QRCodeService;
use Filament\Resources\Pages\Page;

class PrintQRCodes extends Page
{
    protected static string $resource = LokasiResource::class;

    protected string $view = 'filament.resources.lokasis.pages.print-qr-codes';

    protected static ?string $title = 'Cetak QR Code Lokasi';

    public $lokasis = [];

    public function mount(): void
    {
        $qrCodeService = new QRCodeService();

        // Get all active locations
        $this->lokasis = Lokasi::where('is_active', true)
            ->orderBy('kode_lokasi')
            ->get()
            ->map(function ($lokasi) use ($qrCodeService) {
                // Generate QR Code if not exists
                if (!$lokasi->qr_code) {
                    $qrCodeService->generateForLokasi($lokasi);
                    $lokasi->refresh();
                }

                return [
                    'id' => $lokasi->id,
                    'kode_lokasi' => $lokasi->kode_lokasi,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'kategori' => $lokasi->kategori,
                    'qr_code_url' => $lokasi->qr_code ? asset('storage/' . $lokasi->qr_code) : null,
                ];
            })
            ->toArray();
    }
}
