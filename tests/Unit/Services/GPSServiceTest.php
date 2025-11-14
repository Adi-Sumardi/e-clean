<?php

namespace Tests\Unit\Services;

use App\Models\Lokasi;
use App\Services\GPSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GPSServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GPSService $gpsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gpsService = new GPSService();
    }

    public function test_calculate_distance_between_two_points(): void
    {
        // Jakarta coordinates
        $lat1 = -6.200000;
        $lon1 = 106.816666;

        // Nearby point (approx 100m away)
        $lat2 = -6.201000;
        $lon2 = 106.816666;

        $distance = $this->gpsService->calculateDistance($lat1, $lon1, $lat2, $lon2);

        // Distance should be approximately 111 meters (1 degree latitude ≈ 111km)
        $this->assertGreaterThan(100, $distance);
        $this->assertLessThan(150, $distance);
    }

    public function test_calculate_distance_same_point_returns_zero(): void
    {
        $lat = -6.200000;
        $lon = 106.816666;

        $distance = $this->gpsService->calculateDistance($lat, $lon, $lat, $lon);

        $this->assertEquals(0, $distance);
    }

    public function test_validate_location_within_radius(): void
    {
        $currentLat = -6.200000;
        $currentLon = 106.816666;
        $targetLat = -6.200001;
        $targetLon = 106.816666;

        $result = $this->gpsService->validateLocation(
            $currentLat,
            $currentLon,
            $targetLat,
            $targetLon,
            100 // 100 meter radius
        );

        $this->assertTrue($result['is_valid']);
        $this->assertLessThan(100, $result['distance']);
    }

    public function test_validate_location_outside_radius(): void
    {
        $currentLat = -6.200000;
        $currentLon = 106.816666;
        $targetLat = -6.210000; // ~1km away
        $targetLon = 106.816666;

        $result = $this->gpsService->validateLocation(
            $currentLat,
            $currentLon,
            $targetLat,
            $targetLon,
            100 // 100 meter radius
        );

        $this->assertFalse($result['is_valid']);
        $this->assertGreaterThan(100, $result['distance']);
    }

    public function test_validate_activity_location_with_valid_coordinates(): void
    {
        $lokasi = Lokasi::factory()->create([
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        // User is very close to the location
        $result = $this->gpsService->validateActivityLocation(
            -6.200010,
            106.816666,
            $lokasi,
            50 // 50 meter radius
        );

        $this->assertTrue($result['is_valid']);
    }

    public function test_validate_activity_location_without_lokasi_coordinates(): void
    {
        $lokasi = Lokasi::factory()->create([
            'latitude' => null,
            'longitude' => null,
        ]);

        $result = $this->gpsService->validateActivityLocation(
            -6.200000,
            106.816666,
            $lokasi
        );

        $this->assertTrue($result['is_valid']);
        $this->assertArrayHasKey('warning', $result);
    }

    public function test_format_coordinates(): void
    {
        $formatted = $this->gpsService->formatCoordinates(-6.200000, 106.816666);

        $this->assertStringContainsString('6.200000', $formatted);
        $this->assertStringContainsString('106.816666', $formatted);
        $this->assertStringContainsString('S', $formatted); // South
        $this->assertStringContainsString('E', $formatted); // East
    }

    public function test_format_null_coordinates_returns_na(): void
    {
        $formatted = $this->gpsService->formatCoordinates(null, null);

        $this->assertEquals('N/A', $formatted);
    }

    public function test_get_google_maps_link(): void
    {
        $link = $this->gpsService->getGoogleMapsLink(-6.200000, 106.816666);

        $this->assertStringContainsString('google.com/maps', $link);
        $this->assertStringContainsString('-6.2', $link);
        $this->assertStringContainsString('106.816666', $link);
    }

    public function test_check_accuracy_acceptable(): void
    {
        $result = $this->gpsService->checkAccuracy(30, 50);

        $this->assertTrue($result['is_acceptable']);
    }

    public function test_check_accuracy_not_acceptable(): void
    {
        $result = $this->gpsService->checkAccuracy(100, 50);

        $this->assertFalse($result['is_acceptable']);
    }

    public function test_check_accuracy_null_returns_false(): void
    {
        $result = $this->gpsService->checkAccuracy(null);

        $this->assertFalse($result['is_acceptable']);
    }

    public function test_parse_geolocation_data(): void
    {
        $input = [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10,
            'altitude' => 50,
        ];

        $parsed = $this->gpsService->parseGeolocationData($input);

        $this->assertEquals(-6.200000, $parsed['latitude']);
        $this->assertEquals(106.816666, $parsed['longitude']);
        $this->assertEquals(10, $parsed['accuracy']);
        $this->assertArrayHasKey('timestamp', $parsed);
    }
}
