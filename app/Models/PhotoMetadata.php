<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $activity_report_id
 * @property string|null $photo_path
 * @property string $photo_type
 * @property numeric $latitude
 * @property numeric $longitude
 * @property float $gps_accuracy
 * @property string|null $gps_address
 * @property bool $gps_validated
 * @property float|null $gps_distance_from_location
 * @property \Illuminate\Support\Carbon $captured_at
 * @property \Illuminate\Support\Carbon $server_time_at_capture
 * @property string $timezone
 * @property string|null $device_model
 * @property string|null $device_os
 * @property string|null $browser_agent
 * @property string|null $screen_resolution
 * @property string|null $ip_address
 * @property string|null $network_type
 * @property string $photo_hash
 * @property string $watermark_hash
 * @property array<array-key, mixed>|null $exif_data
 * @property bool $is_tampered
 * @property float|null $tamper_detection_score
 * @property int|null $file_size
 * @property string|null $original_dimensions
 * @property string|null $compressed_dimensions
 * @property float|null $compression_ratio
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ActivityReport|null $activityReport
 * @property-read string $confidence_badge_color
 * @property-read string $confidence_level
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereActivityReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereBrowserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereCapturedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereCompressedDimensions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereCompressionRatio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereDeviceModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereDeviceOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereExifData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereGpsAccuracy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereGpsAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereGpsDistanceFromLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereGpsValidated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereIsTampered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereNetworkType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereOriginalDimensions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata wherePhotoHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata wherePhotoType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereScreenResolution($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereServerTimeAtCapture($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereTamperDetectionScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoMetadata whereWatermarkHash($value)
 * @mixin \Eloquent
 */
class PhotoMetadata extends Model
{
    protected $fillable = [
        'activity_report_id',
        'photo_path',
        'photo_type',
        // GPS Data
        'latitude',
        'longitude',
        'gps_accuracy',
        'gps_address',
        'gps_validated',
        'gps_distance_from_location',
        // Timestamp Data
        'captured_at',
        'server_time_at_capture',
        'timezone',
        // Device Data
        'device_model',
        'device_os',
        'browser_agent',
        'screen_resolution',
        'ip_address',
        'network_type',
        // Verification Data
        'photo_hash',
        'watermark_hash',
        'exif_data',
        'is_tampered',
        'tamper_detection_score',
        // Metadata
        'file_size',
        'original_dimensions',
        'compressed_dimensions',
        'compression_ratio',
    ];

    protected $casts = [
        'gps_validated' => 'boolean',
        'is_tampered' => 'boolean',
        'captured_at' => 'datetime',
        'server_time_at_capture' => 'datetime',
        'exif_data' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'gps_accuracy' => 'float',
        'gps_distance_from_location' => 'float',
        'tamper_detection_score' => 'float',
        'file_size' => 'integer',
        'compression_ratio' => 'float',
    ];

    /**
     * Get the activity report that owns this photo metadata
     */
    public function activityReport(): BelongsTo
    {
        return $this->belongsTo(ActivityReport::class);
    }

    /**
     * Calculate confidence score based on various factors
     */
    public function calculateConfidenceScore(): float
    {
        $score = 0;

        // GPS validation (30 points)
        if ($this->gps_validated && $this->gps_distance_from_location !== null) {
            if ($this->gps_distance_from_location <= 10) {
                $score += 30;
            } elseif ($this->gps_distance_from_location <= 30) {
                $score += 20;
            } elseif ($this->gps_distance_from_location <= 50) {
                $score += 10;
            }
        }

        // GPS accuracy (15 points)
        if ($this->gps_accuracy <= 10) {
            $score += 15;
        } elseif ($this->gps_accuracy <= 20) {
            $score += 10;
        } elseif ($this->gps_accuracy <= 50) {
            $score += 5;
        }

        // Timestamp validation (25 points)
        $timeDiff = abs($this->captured_at->diffInSeconds($this->server_time_at_capture));
        if ($timeDiff <= 5) {
            $score += 25;
        } elseif ($timeDiff <= 30) {
            $score += 15;
        } elseif ($timeDiff <= 120) {
            $score += 10;
        }

        // Hash integrity (15 points)
        if (!$this->is_tampered) {
            $score += 15;
        }

        // Device consistency (15 points)
        if ($this->device_model && $this->browser_agent) {
            $score += 15;
        }

        return round($score, 2);
    }

    /**
     * Get confidence level badge
     */
    public function getConfidenceLevelAttribute(): string
    {
        $score = $this->calculateConfidenceScore();

        if ($score >= 90) {
            return 'high';
        } elseif ($score >= 70) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get confidence badge color
     */
    public function getConfidenceBadgeColorAttribute(): string
    {
        return match ($this->confidence_level) {
            'high' => 'success',
            'medium' => 'warning',
            'low' => 'danger',
        };
    }
}
