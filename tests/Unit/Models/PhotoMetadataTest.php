<?php

namespace Tests\Unit\Models;

use App\Models\PhotoMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_confidence_score_high(): void
    {
        $metadata = new PhotoMetadata([
            'gps_validated' => true,
            'gps_distance_from_location' => 5.0,
            'gps_accuracy' => 8.0,
            'captured_at' => now(),
            'server_time_at_capture' => now(),
            'is_tampered' => false,
            'device_model' => 'iPhone 15',
            'browser_agent' => 'Safari',
        ]);

        $score = $metadata->calculateConfidenceScore();

        // GPS validated + distance <= 10 = 30
        // GPS accuracy <= 10 = 15
        // Time diff <= 5 = 25
        // Not tampered = 15
        // Device + browser = 15
        // Total = 100
        $this->assertEquals(100, $score);
    }

    public function test_calculate_confidence_score_medium_distance(): void
    {
        $metadata = new PhotoMetadata([
            'gps_validated' => true,
            'gps_distance_from_location' => 25.0,
            'gps_accuracy' => 15.0,
            'captured_at' => now(),
            'server_time_at_capture' => now()->subSeconds(15),
            'is_tampered' => false,
            'device_model' => 'Samsung',
            'browser_agent' => 'Chrome',
        ]);

        $score = $metadata->calculateConfidenceScore();

        // Distance <= 30 = 20, accuracy <= 20 = 10, time <= 30 = 15, not tampered = 15, device = 15
        $this->assertEquals(75, $score);
    }

    public function test_calculate_confidence_score_with_null_gps(): void
    {
        $metadata = new PhotoMetadata([
            'gps_validated' => false,
            'gps_distance_from_location' => null,
            'gps_accuracy' => null,
            'captured_at' => now(),
            'server_time_at_capture' => now(),
            'is_tampered' => false,
            'device_model' => 'Test',
            'browser_agent' => 'Test',
        ]);

        // Should not crash even with null GPS values
        // gps_accuracy null comparison with <= will be false
        $score = $metadata->calculateConfidenceScore();
        $this->assertIsFloat($score);
    }

    public function test_confidence_level_high(): void
    {
        $metadata = new PhotoMetadata([
            'gps_validated' => true,
            'gps_distance_from_location' => 5.0,
            'gps_accuracy' => 8.0,
            'captured_at' => now(),
            'server_time_at_capture' => now(),
            'is_tampered' => false,
            'device_model' => 'iPhone',
            'browser_agent' => 'Safari',
        ]);

        $this->assertEquals('high', $metadata->confidence_level);
    }

    public function test_confidence_level_low(): void
    {
        $metadata = new PhotoMetadata([
            'gps_validated' => false,
            'gps_distance_from_location' => null,
            'gps_accuracy' => 100.0,
            'captured_at' => now(),
            'server_time_at_capture' => now()->subMinutes(5),
            'is_tampered' => true,
            'device_model' => null,
            'browser_agent' => null,
        ]);

        $this->assertEquals('low', $metadata->confidence_level);
    }

    public function test_confidence_badge_color_mapping(): void
    {
        $high = new PhotoMetadata([
            'gps_validated' => true,
            'gps_distance_from_location' => 5.0,
            'gps_accuracy' => 8.0,
            'captured_at' => now(),
            'server_time_at_capture' => now(),
            'is_tampered' => false,
            'device_model' => 'Test',
            'browser_agent' => 'Test',
        ]);

        $this->assertEquals('success', $high->confidence_badge_color);
    }

    public function test_photo_metadata_has_correct_casts(): void
    {
        $metadata = new PhotoMetadata();
        $casts = $metadata->getCasts();

        $this->assertEquals('boolean', $casts['gps_validated']);
        $this->assertEquals('boolean', $casts['is_tampered']);
        $this->assertEquals('datetime', $casts['captured_at']);
        $this->assertEquals('array', $casts['exif_data']);
        $this->assertEquals('float', $casts['gps_accuracy']);
    }

    public function test_photo_metadata_fillable_fields(): void
    {
        $metadata = new PhotoMetadata();
        $fillable = $metadata->getFillable();

        $this->assertContains('latitude', $fillable);
        $this->assertContains('longitude', $fillable);
        $this->assertContains('photo_hash', $fillable);
        $this->assertContains('is_tampered', $fillable);
        $this->assertContains('device_model', $fillable);
    }
}
