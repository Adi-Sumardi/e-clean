<?php

namespace App\Filament\Resources\Lokasis\Pages;

use App\Filament\Resources\Lokasis\LokasiResource;
use App\Models\Lokasi;
use App\Models\Unit;
use App\Services\QRCodeService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PrintQRCodes extends Page
{
    protected static string $resource = LokasiResource::class;

    protected string $view = 'filament.resources.lokasis.pages.print-qr-codes';

    protected static ?string $title = 'Cetak QR Code Lokasi';

    public $lokasis = [];
    public $units = [];
    public $selectedUnit = null;
    public $selectedLokasi = null;

    public function mount(): void
    {
        $this->loadUnits();
        $this->loadLokasis();
    }

    public function loadUnits(): void
    {
        $this->units = Unit::where('is_active', true)
            ->orderBy('nama_unit')
            ->pluck('nama_unit', 'id')
            ->toArray();
    }

    public function updatedSelectedUnit(): void
    {
        // Reset lokasi selection when unit changes
        $this->selectedLokasi = null;
        $this->loadLokasis();
    }

    public function updatedSelectedLokasi(): void
    {
        $this->loadLokasis();
    }

    public function resetFilters(): void
    {
        $this->selectedUnit = null;
        $this->selectedLokasi = null;
        $this->loadLokasis();
    }

    public function getLokasiOptions(): array
    {
        $query = Lokasi::where('is_active', true);

        if ($this->selectedUnit) {
            $query->where('unit_id', $this->selectedUnit);
        }

        return $query->orderBy('nama_lokasi')
            ->pluck('nama_lokasi', 'id')
            ->toArray();
    }

    public function loadLokasis(): void
    {
        $query = Lokasi::where('is_active', true);

        // Filter by unit if selected
        if ($this->selectedUnit) {
            $query->where('unit_id', $this->selectedUnit);
        }

        // Filter by specific lokasi if selected
        if ($this->selectedLokasi) {
            $query->where('id', $this->selectedLokasi);
        }

        $this->lokasis = $query->orderBy('kode_lokasi')
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
                    'unit_nama' => $lokasi->unit?->nama_unit ?? '-',
                    'qr_code_url' => $qrCodeExists ? asset('storage/' . $lokasi->qr_code) : null,
                    'qr_code_exists' => $qrCodeExists,
                ];
            })
            ->toArray();
    }

    public function generateQRCode(int $lokasiId): void
    {
        Log::info('generateQRCode called', ['lokasi_id' => $lokasiId]);

        $lokasi = Lokasi::find($lokasiId);

        if (!$lokasi) {
            Log::error('Lokasi not found', ['lokasi_id' => $lokasiId]);
            Notification::make()
                ->title('Error')
                ->body('Lokasi tidak ditemukan')
                ->danger()
                ->send();
            return;
        }

        try {
            Log::info('Generating QR Code', ['kode' => $lokasi->kode_lokasi]);
            $qrCodeService = new QRCodeService();
            $path = $qrCodeService->generateForLokasi($lokasi);
            Log::info('QR Code generated', ['path' => $path]);

            // Verify file exists
            $exists = Storage::disk('public')->exists($path);
            Log::info('File exists check', ['exists' => $exists, 'path' => $path]);

            Notification::make()
                ->title('QR Code Berhasil Dibuat')
                ->body('QR Code untuk ' . $lokasi->nama_lokasi . ' telah dibuat')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('QR Code generation failed', ['error' => $e->getMessage()]);
            Notification::make()
                ->title('Error')
                ->body('Gagal membuat QR Code: ' . $e->getMessage())
                ->danger()
                ->send();
            return;
        }

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
