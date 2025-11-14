<?php

namespace App\Services;

use App\Models\Lokasi;
use Illuminate\Support\Facades\Log;

class GPSService
{
    /**
     * Hitung jarak antara dua koordinat GPS menggunakan Haversine formula
     *
     * @param float $lat1 Latitude titik 1
     * @param float $lon1 Longitude titik 1
     * @param float $lat2 Latitude titik 2
     * @param float $lon2 Longitude titik 2
     * @return float Jarak dalam meter
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Validasi apakah koordinat GPS berada dalam radius yang diizinkan
     *
     * @param float $currentLat Current latitude
     * @param float $currentLon Current longitude
     * @param float $targetLat Target latitude
     * @param float $targetLon Target longitude
     * @param int $allowedRadius Radius yang diizinkan dalam meter (default: 100m)
     * @return array [is_valid => bool, distance => float, message => string]
     */
    public function validateLocation(
        float $currentLat,
        float $currentLon,
        float $targetLat,
        float $targetLon,
        int $allowedRadius = 100
    ): array {
        $distance = $this->calculateDistance($currentLat, $currentLon, $targetLat, $targetLon);

        $isValid = $distance <= $allowedRadius;

        return [
            'is_valid' => $isValid,
            'distance' => round($distance, 2),
            'allowed_radius' => $allowedRadius,
            'message' => $isValid
                ? "Lokasi valid (jarak: {$distance}m dari target)"
                : "Lokasi terlalu jauh! Jarak: {$distance}m (max: {$allowedRadius}m)",
        ];
    }


    /**
     * Validasi laporan kegiatan berdasarkan GPS lokasi cleaning
     *
     * @param float $latitude
     * @param float $longitude
     * @param Lokasi $lokasi
     * @param int $allowedRadius Radius dalam meter (default: 50m)
     * @return array
     */
    public function validateActivityLocation(float $latitude, float $longitude, Lokasi $lokasi, int $allowedRadius = 50): array
    {
        if (!$lokasi->latitude || !$lokasi->longitude) {
            return [
                'is_valid' => true, // Jika lokasi belum punya GPS, tidak perlu validasi
                'distance' => null,
                'allowed_radius' => $allowedRadius,
                'message' => 'Lokasi belum memiliki koordinat GPS',
                'warning' => true,
            ];
        }

        return $this->validateLocation($latitude, $longitude, $lokasi->latitude, $lokasi->longitude, $allowedRadius);
    }

    /**
     * Parse koordinat GPS dari browser Geolocation API
     *
     * @param array $gpsData Data dari browser format: {latitude, longitude, accuracy, altitude, etc}
     * @return array
     */
    public function parseGeolocationData(array $gpsData): array
    {
        return [
            'latitude' => $gpsData['latitude'] ?? null,
            'longitude' => $gpsData['longitude'] ?? null,
            'accuracy' => $gpsData['accuracy'] ?? null,
            'altitude' => $gpsData['altitude'] ?? null,
            'heading' => $gpsData['heading'] ?? null,
            'speed' => $gpsData['speed'] ?? null,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Format koordinat GPS untuk tampilan
     *
     * @param float|null $latitude
     * @param float|null $longitude
     * @return string
     */
    public function formatCoordinates(?float $latitude, ?float $longitude): string
    {
        if (!$latitude || !$longitude) {
            return 'N/A';
        }

        $latDir = $latitude >= 0 ? 'N' : 'S';
        $lonDir = $longitude >= 0 ? 'E' : 'W';

        return sprintf(
            '%.6f°%s, %.6f°%s',
            abs($latitude),
            $latDir,
            abs($longitude),
            $lonDir
        );
    }

    /**
     * Generate Google Maps link dari koordinat
     *
     * @param float $latitude
     * @param float $longitude
     * @return string
     */
    public function getGoogleMapsLink(float $latitude, float $longitude): string
    {
        return "https://www.google.com/maps?q={$latitude},{$longitude}";
    }

    /**
     * Generate OpenStreetMap link dari koordinat
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $zoom Default zoom level
     * @return string
     */
    public function getOpenStreetMapLink(float $latitude, float $longitude, int $zoom = 18): string
    {
        return "https://www.openstreetmap.org/?mlat={$latitude}&mlon={$longitude}&zoom={$zoom}";
    }

    /**
     * Check akurasi GPS cukup baik untuk validasi
     *
     * @param float|null $accuracy GPS accuracy dalam meter
     * @param int $maxAccuracy Maximum acceptable accuracy (default: 50m)
     * @return array
     */
    public function checkAccuracy(?float $accuracy, int $maxAccuracy = 50): array
    {
        if ($accuracy === null) {
            return [
                'is_acceptable' => false,
                'message' => 'Akurasi GPS tidak tersedia',
            ];
        }

        $isAcceptable = $accuracy <= $maxAccuracy;

        return [
            'is_acceptable' => $isAcceptable,
            'accuracy' => round($accuracy, 2),
            'max_accuracy' => $maxAccuracy,
            'message' => $isAcceptable
                ? "Akurasi GPS bagus ({$accuracy}m)"
                : "Akurasi GPS kurang baik ({$accuracy}m). Harap aktifkan GPS dengan akurat.",
        ];
    }

    /**
     * Log GPS data untuk debugging
     *
     * @param string $type Type of GPS log (attendance, activity, etc)
     * @param array $data GPS data
     * @return void
     */
    public function logGPSData(string $type, array $data): void
    {
        Log::channel('gps')->info("GPS {$type}", $data);
    }
}
