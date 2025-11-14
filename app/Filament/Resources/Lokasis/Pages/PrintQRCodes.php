<?php

namespace App\Filament\Resources\Lokasis\Pages;

use App\Filament\Resources\Lokasis\LokasiResource;
use App\Models\Lokasi;
use App\Services\BarcodeService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class PrintQRCodes extends Page
{
    protected static string $resource = LokasiResource::class;

    protected string $view = 'filament.resources.lokasis.pages.print-qr-codes';

    protected static ?string $title = 'Cetak Barcode Lokasi';

    public $lokasis = [];

    public function mount(): void
    {
        $barcodeService = new BarcodeService();

        // Get all active locations
        $this->lokasis = Lokasi::where('is_active', true)
            ->orderBy('kode_lokasi')
            ->get()
            ->map(function ($lokasi) use ($barcodeService) {
                // Generate barcode if not exists
                if (!$lokasi->qr_code) {
                    $barcodeService->generateForLokasi($lokasi);
                    $lokasi->refresh();
                }

                return [
                    'id' => $lokasi->id,
                    'kode_lokasi' => $lokasi->kode_lokasi,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'kategori' => $lokasi->kategori,
                    'qr_code_url' => $lokasi->qr_code ? Storage::disk('public')->url($lokasi->qr_code) : null,
                ];
            })
            ->toArray();
    }
}
