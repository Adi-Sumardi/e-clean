<?php

namespace App\Services;

use App\Models\Lokasi;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeService
{
    /**
     * Generate Barcode for a location (Code 128 format)
     *
     * @param Lokasi $lokasi
     * @param int $widthFactor - Width of each bar (default 2)
     * @param int $height - Height of barcode in pixels (default 50)
     * @return string - Returns the stored file path
     */
    public function generateForLokasi(Lokasi $lokasi, int $widthFactor = 2, int $height = 50): string
    {
        $generator = new BarcodeGeneratorPNG();

        // Generate barcode using Code 128 (best for alphanumeric)
        // We'll encode the kode_lokasi which is unique
        $barcode = $generator->getBarcode(
            $lokasi->kode_lokasi,
            $generator::TYPE_CODE_128,
            $widthFactor,
            $height
        );

        // Save to storage
        $filename = 'barcodes/' . $lokasi->kode_lokasi . '.png';
        Storage::disk('public')->put($filename, $barcode);

        // Update lokasi with barcode path
        $lokasi->update(['qr_code' => $filename]); // We reuse qr_code column

        return $filename;
    }

    /**
     * Generate barcodes for multiple locations
     *
     * @param array $lokasiIds - Array of location IDs
     * @param int $widthFactor
     * @param int $height
     * @return array - Returns array of ['lokasi_id' => 'path']
     */
    public function generateMultiple(array $lokasiIds, int $widthFactor = 2, int $height = 50): array
    {
        $results = [];

        foreach ($lokasiIds as $lokasiId) {
            $lokasi = Lokasi::find($lokasiId);
            if ($lokasi) {
                $results[$lokasiId] = $this->generateForLokasi($lokasi, $widthFactor, $height);
            }
        }

        return $results;
    }

    /**
     * Generate all barcodes for locations without barcodes
     *
     * @param int $widthFactor
     * @param int $height
     * @return int - Returns count of generated barcodes
     */
    public function generateMissingBarcodes(int $widthFactor = 2, int $height = 50): int
    {
        $lokasis = Lokasi::whereNull('qr_code')
            ->orWhere('qr_code', '')
            ->get();

        $count = 0;
        foreach ($lokasis as $lokasi) {
            $this->generateForLokasi($lokasi, $widthFactor, $height);
            $count++;
        }

        return $count;
    }

    /**
     * Decode barcode data (for barcode, we just validate the kode_lokasi)
     *
     * @param string $barcodeData - Scanned barcode string
     * @return array|null - Returns decoded data or null if invalid
     */
    public function decodeBarcodeData(string $barcodeData): ?array
    {
        // For barcode, the data is just the kode_lokasi
        // We need to find the lokasi by kode_lokasi
        $lokasi = Lokasi::where('kode_lokasi', $barcodeData)->first();

        if (!$lokasi) {
            return null;
        }

        // Return standardized format for compatibility
        return [
            'type' => 'lokasi',
            'id' => $lokasi->id,
            'kode' => $lokasi->kode_lokasi,
            'nama' => $lokasi->nama_lokasi,
            'kategori' => $lokasi->kategori,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate barcode for a location
     *
     * @param string $barcodeData
     * @param int $lokasiId
     * @return bool
     */
    public function validateBarcode(string $barcodeData, int $lokasiId): bool
    {
        $lokasi = Lokasi::find($lokasiId);

        if (!$lokasi) {
            return false;
        }

        return $lokasi->kode_lokasi === $barcodeData;
    }

    /**
     * Get barcode URL for a location
     *
     * @param Lokasi $lokasi
     * @return string|null
     */
    public function getBarcodeUrl(Lokasi $lokasi): ?string
    {
        if (!$lokasi->qr_code) {
            return null;
        }

        return Storage::disk('public')->url($lokasi->qr_code);
    }

    /**
     * Delete barcode for a location
     *
     * @param Lokasi $lokasi
     * @return bool
     */
    public function deleteBarcode(Lokasi $lokasi): bool
    {
        if ($lokasi->qr_code) {
            Storage::disk('public')->delete($lokasi->qr_code);
            $lokasi->update(['qr_code' => null]);
            return true;
        }

        return false;
    }

    /**
     * Regenerate barcode for a location
     *
     * @param Lokasi $lokasi
     * @param int $widthFactor
     * @param int $height
     * @return string
     */
    public function regenerateBarcode(Lokasi $lokasi, int $widthFactor = 2, int $height = 50): string
    {
        // Delete old barcode
        $this->deleteBarcode($lokasi);

        // Generate new barcode
        return $this->generateForLokasi($lokasi, $widthFactor, $height);
    }
}
