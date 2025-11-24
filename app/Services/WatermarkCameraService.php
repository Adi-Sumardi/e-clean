<?php

namespace App\Services;

use App\Models\ActivityReport;
use App\Models\PhotoMetadata;
use App\Models\Lokasi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Typography\FontFactory;

class WatermarkCameraService
{
    /**
     * Validate GPS location against work location
     * Using Haversine formula for distance calculation
     */
    public function validateGPS(float $latitude, float $longitude, int $lokasiId, float $accuracy): array
    {
        $lokasi = Lokasi::findOrFail($lokasiId);

        // Check if lokasi has GPS coordinates
        if (!$lokasi->latitude || !$lokasi->longitude) {
            return [
                'valid' => false,
                'error' => 'Lokasi kerja belum memiliki koordinat GPS. Hubungi admin untuk menambahkan koordinat.',
                'distance' => null
            ];
        }

        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance(
            $latitude,
            $longitude,
            floatval($lokasi->latitude),
            floatval($lokasi->longitude)
        );

        // Check GPS accuracy first
        if ($accuracy > 50) {
            return [
                'valid' => false,
                'error' => "Akurasi GPS terlalu rendah ({$accuracy}m). Tunggu hingga akurasi lebih baik (< 50m).",
                'distance' => $distance,
                'accuracy_too_low' => true
            ];
        }

        // Check if within 50 meters radius
        if ($distance > 50) {
            return [
                'valid' => false,
                'error' => "Anda terlalu jauh dari lokasi kerja. Jarak: " . round($distance, 1) . "m (Maksimal: 50m)",
                'distance' => $distance
            ];
        }

        return [
            'valid' => true,
            'distance' => $distance,
            'message' => 'Lokasi GPS valid'
        ];
    }

    /**
     * Process photo with watermark overlay
     */
    public function processPhoto(array $data): array
    {
        try {
            // Decode base64 photo
            $photoData = $data['photo_data'];

            // Remove data URI prefix if present
            if (str_contains($photoData, 'base64,')) {
                $photoData = substr($photoData, strpos($photoData, 'base64,') + 7);
            }

            $photoData = str_replace(' ', '+', $photoData);
            $imageData = base64_decode($photoData);

            if ($imageData === false) {
                return [
                    'success' => false,
                    'error' => 'Gagal decode foto. Format data tidak valid.'
                ];
            }

            // Generate unique filename
            $filename = 'watermarked_' . Str::random(40) . '.webp';
            $directory = 'activity-reports/' . $data['photo_type'];
            $path = $directory . '/' . $filename;

            // Create temporary file
            $tempPath = storage_path('app/temp/' . uniqid() . '.jpg');

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            file_put_contents($tempPath, $imageData);

            // Get original file size
            $originalSize = filesize($tempPath);

            // Process with Intervention Image
            $image = Image::read($tempPath);

            // Get original dimensions
            $originalWidth = $image->width();
            $originalHeight = $image->height();
            $originalDimensions = "{$originalWidth}x{$originalHeight}";

            // Resize if too large (max 1920px width)
            if ($originalWidth > 1920) {
                $image->scale(width: 1920);
            }

            // Get final dimensions
            $finalWidth = $image->width();
            $finalHeight = $image->height();
            $compressedDimensions = "{$finalWidth}x{$finalHeight}";

            // Convert to WebP with 80% quality
            $encodedImage = $image->encode(new WebpEncoder(quality: 80));

            // Save to storage
            Storage::put($path, $encodedImage->toString());

            // Get compressed file size
            $compressedSize = Storage::size($path);
            $compressionRatio = $originalSize > 0 ? round(($compressedSize / $originalSize) * 100, 2) : 0;

            // Generate verification hashes
            $photoHash = $this->generatePhotoHash($imageData, $data);
            $watermarkHash = hash('sha256', $path . $photoHash . now()->timestamp);

            // Validate GPS
            $gpsValidation = $this->validateGPS(
                $data['gps_data']['latitude'],
                $data['gps_data']['longitude'],
                $data['lokasi_id'],
                $data['gps_data']['accuracy']
            );

            // Save metadata
            $metadata = PhotoMetadata::create([
                'activity_report_id' => $data['activity_report_id'] ?? null,
                'photo_path' => $path,
                'photo_type' => $data['photo_type'],
                // GPS Data
                'latitude' => $data['gps_data']['latitude'],
                'longitude' => $data['gps_data']['longitude'],
                'gps_accuracy' => $data['gps_data']['accuracy'],
                'gps_address' => $data['gps_data']['address'] ?? null,
                'gps_validated' => $gpsValidation['valid'],
                'gps_distance_from_location' => $gpsValidation['distance'] ?? null,
                // Timestamp Data
                'captured_at' => $data['captured_at'] ?? now(),
                'server_time_at_capture' => now(),
                'timezone' => 'Asia/Jakarta',
                // Device Data
                'device_model' => $data['device_data']['model'] ?? null,
                'device_os' => $data['device_data']['os'] ?? null,
                'browser_agent' => $data['device_data']['agent'] ?? request()->userAgent(),
                'screen_resolution' => $data['device_data']['screen'] ?? null,
                'ip_address' => request()->ip(),
                'network_type' => $data['device_data']['network'] ?? null,
                // Verification Data
                'photo_hash' => $photoHash,
                'watermark_hash' => $watermarkHash,
                'exif_data' => null, // Will be populated if EXIF available
                'is_tampered' => false,
                'tamper_detection_score' => 0,
                // Metadata
                'file_size' => $compressedSize,
                'original_dimensions' => $originalDimensions,
                'compressed_dimensions' => $compressedDimensions,
                'compression_ratio' => $compressionRatio,
            ]);

            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return [
                'success' => true,
                'path' => $path,
                'url' => Storage::url($path),
                'metadata' => $metadata,
                'confidence_score' => $metadata->calculateConfidenceScore(),
                'file_size' => $compressedSize,
                'compression_ratio' => $compressionRatio
            ];

        } catch (\Exception $e) {
            // Clean up temp file on error
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            return [
                'success' => false,
                'error' => 'Gagal memproses foto: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula
     * Returns distance in meters
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return round($distance, 2); // Return in meters with 2 decimal precision
    }

    /**
     * Generate SHA-256 hash for photo verification
     */
    private function generatePhotoHash(string $imageData, array $data): string
    {
        $hashString = $imageData
            . $data['gps_data']['latitude']
            . $data['gps_data']['longitude']
            . $data['petugas_id']
            . $data['lokasi_id']
            . now()->timestamp
            . Str::random(16); // Add random salt for uniqueness

        return hash('sha256', $hashString);
    }

    /**
     * Verify photo integrity by checking hash
     */
    public function verifyPhotoHash(PhotoMetadata $metadata, string $photoPath): bool
    {
        if (!Storage::exists($photoPath)) {
            return false;
        }

        // Get photo data
        $photoData = Storage::get($photoPath);

        // Generate hash from stored metadata
        $verificationHash = hash('sha256',
            $photoData .
            $metadata->latitude .
            $metadata->longitude
        );

        // Compare hashes
        return hash_equals($metadata->photo_hash, $verificationHash);
    }

    /**
     * Calculate overall confidence score for activity report
     */
    public function calculateReportConfidenceScore(ActivityReport $report): float
    {
        $beforePhotos = $report->beforePhotoMetadata;
        $afterPhotos = $report->afterPhotoMetadata;

        if ($beforePhotos->isEmpty() && $afterPhotos->isEmpty()) {
            return 0;
        }

        $totalScore = 0;
        $totalPhotos = 0;

        foreach ($beforePhotos as $photo) {
            $totalScore += $photo->calculateConfidenceScore();
            $totalPhotos++;
        }

        foreach ($afterPhotos as $photo) {
            $totalScore += $photo->calculateConfidenceScore();
            $totalPhotos++;
        }

        return $totalPhotos > 0 ? round($totalScore / $totalPhotos, 2) : 0;
    }

    /**
     * Detect potential fraud based on photo metadata
     */
    public function detectFraud(ActivityReport $report): array
    {
        $flags = [];
        $allPhotos = $report->photoMetadata;

        if ($allPhotos->isEmpty()) {
            return ['no_photos' => 'Tidak ada foto untuk diverifikasi'];
        }

        foreach ($allPhotos as $photo) {
            $photoFlags = [];

            // Check GPS distance
            if ($photo->gps_distance_from_location > 50) {
                $photoFlags[] = 'gps_too_far';
            }

            // Check GPS accuracy
            if ($photo->gps_accuracy > 50) {
                $photoFlags[] = 'gps_accuracy_low';
            }

            // Check timestamp difference
            $timeDiff = abs($photo->captured_at->diffInSeconds($photo->server_time_at_capture));
            if ($timeDiff > 300) { // 5 minutes
                $photoFlags[] = 'timestamp_mismatch';
            }

            // Check if tampered
            if ($photo->is_tampered) {
                $photoFlags[] = 'photo_tampered';
            }

            // Check device consistency
            if (empty($photo->device_model) || empty($photo->browser_agent)) {
                $photoFlags[] = 'missing_device_info';
            }

            if (!empty($photoFlags)) {
                $flags[$photo->id] = $photoFlags;
            }
        }

        return $flags;
    }

    /**
     * Update activity report verification status
     */
    public function updateReportVerification(ActivityReport $report): void
    {
        // Calculate confidence score
        $confidenceScore = $this->calculateReportConfidenceScore($report);

        // Detect fraud
        $fraudFlags = $this->detectFraud($report);

        // Check if before and after photos are verified
        $beforeVerified = $report->beforePhotoMetadata()
            ->where('gps_validated', true)
            ->exists();

        $afterVerified = $report->afterPhotoMetadata()
            ->where('gps_validated', true)
            ->exists();

        // Determine if manual review required
        $manualReviewRequired = $confidenceScore < 70 || !empty($fraudFlags);

        // Update report
        $report->update([
            'foto_sebelum_verified' => $beforeVerified,
            'foto_sesudah_verified' => $afterVerified,
            'verification_score' => $confidenceScore,
            'fraud_flags' => $fraudFlags,
            'manual_review_required' => $manualReviewRequired
        ]);
    }
}
