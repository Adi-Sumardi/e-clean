<?php

namespace App\Services;

use App\Models\Lokasi;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    /**
     * Generate QR Code for a location
     * QR Code contains URL to complaint form for easy scanning
     *
     * @param Lokasi $lokasi
     * @param int $size - Size in pixels
     * @return string - Returns the stored file path
     */
    public function generateForLokasi(Lokasi $lokasi, int $size = 300): string
    {
        // Generate QR code with direct URL to complaint form
        // This allows external scanners (Google Lens, etc.) to redirect directly
        $complaintUrl = url('/keluhan/' . $lokasi->kode_lokasi);

        // Generate QR code image
        $qrCode = QrCode::format('png')
            ->size($size)
            ->errorCorrection('H') // High error correction
            ->margin(2)
            ->generate($complaintUrl);

        // Save to storage
        $filename = 'qrcodes/' . $lokasi->kode_lokasi . '.png';
        Storage::disk('public')->put($filename, $qrCode);

        // Update lokasi with QR code path
        $lokasi->update(['qr_code' => $filename]);

        return $filename;
    }

    /**
     * Generate QR codes for multiple locations
     *
     * @param array $lokasiIds - Array of location IDs
     * @param int $size
     * @return array - Returns array of ['lokasi_id' => 'path']
     */
    public function generateMultiple(array $lokasiIds, int $size = 300): array
    {
        $results = [];

        foreach ($lokasiIds as $lokasiId) {
            $lokasi = Lokasi::find($lokasiId);
            if ($lokasi) {
                $results[$lokasiId] = $this->generateForLokasi($lokasi, $size);
            }
        }

        return $results;
    }

    /**
     * Generate all QR codes for locations without QR codes
     * Uses chunk to prevent memory exhaustion with large datasets
     *
     * @param int $size
     * @return int - Returns count of generated QR codes
     */
    public function generateMissingQRCodes(int $size = 300): int
    {
        $count = 0;

        // Use chunk to avoid memory overload with large datasets
        Lokasi::whereNull('qr_code')
            ->orWhere('qr_code', '')
            ->chunk(100, function ($lokasis) use ($size, &$count) {
                foreach ($lokasis as $lokasi) {
                    $this->generateForLokasi($lokasi, $size);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Decode QR code data
     * Supports: URL format, JSON format, or simple kode_lokasi string
     *
     * @param string $qrData - URL, JSON string, or simple kode_lokasi
     * @return array|null - Returns decoded data or null if invalid
     */
    public function decodeQRData(string $qrData): ?array
    {
        // Try to extract kode_lokasi from URL format (e.g., https://domain.com/keluhan/ABC123)
        if (str_contains($qrData, '/keluhan/')) {
            $parts = explode('/keluhan/', $qrData);
            if (isset($parts[1])) {
                $kode = trim($parts[1], '/');
                $lokasi = Lokasi::where('kode_lokasi', $kode)->first();
                if ($lokasi) {
                    return [
                        'type' => 'lokasi',
                        'id' => $lokasi->id,
                        'kode' => $lokasi->kode_lokasi,
                        'nama' => $lokasi->nama_lokasi,
                        'kategori' => $lokasi->kategori,
                        'timestamp' => now()->toIso8601String(),
                    ];
                }
            }
        }

        // Try to decode as JSON (legacy QR Code format)
        try {
            $data = json_decode($qrData, true);

            if (isset($data['type']) && $data['type'] === 'lokasi') {
                return $data;
            }
        } catch (\Exception $e) {
            // Not JSON, continue
        }

        // Try as simple kode_lokasi string
        $lokasi = Lokasi::where('kode_lokasi', $qrData)->first();

        if ($lokasi) {
            return [
                'type' => 'lokasi',
                'id' => $lokasi->id,
                'kode' => $lokasi->kode_lokasi,
                'nama' => $lokasi->nama_lokasi,
                'kategori' => $lokasi->kategori,
                'timestamp' => now()->toIso8601String(),
            ];
        }

        return null;
    }

    /**
     * Validate QR code for a location
     *
     * @param string $qrData
     * @param int $lokasiId
     * @return bool
     */
    public function validateQRCode(string $qrData, int $lokasiId): bool
    {
        $decoded = $this->decodeQRData($qrData);

        if (!$decoded) {
            return false;
        }

        return isset($decoded['id']) && (int)$decoded['id'] === $lokasiId;
    }

    /**
     * Get QR code URL for a location
     *
     * @param Lokasi $lokasi
     * @return string|null
     */
    public function getQRCodeUrl(Lokasi $lokasi): ?string
    {
        if (!$lokasi->qr_code) {
            return null;
        }

        return Storage::disk('public')->url($lokasi->qr_code);
    }

    /**
     * Delete QR code for a location
     *
     * @param Lokasi $lokasi
     * @return bool
     */
    public function deleteQRCode(Lokasi $lokasi): bool
    {
        if ($lokasi->qr_code) {
            Storage::disk('public')->delete($lokasi->qr_code);
            $lokasi->update(['qr_code' => null]);
            return true;
        }

        return false;
    }

    /**
     * Regenerate QR code for a location
     *
     * @param Lokasi $lokasi
     * @param int $size
     * @return string
     */
    public function regenerateQRCode(Lokasi $lokasi, int $size = 300): string
    {
        // Delete old QR code
        $this->deleteQRCode($lokasi);

        // Generate new QR code
        return $this->generateForLokasi($lokasi, $size);
    }
}
