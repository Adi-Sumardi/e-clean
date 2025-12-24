<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $petugas_id
 * @property int $lokasi_id
 * @property int|null $jadwal_id
 * @property \Illuminate\Support\Carbon $tanggal
 * @property \Illuminate\Support\Carbon $jam_mulai
 * @property \Illuminate\Support\Carbon|null $jam_selesai
 * @property string $kegiatan
 * @property array<array-key, mixed>|null $foto_sebelum
 * @property array<array-key, mixed>|null $foto_sesudah
 * @property array<array-key, mixed>|null $koordinat_lokasi
 * @property string $status
 * @property string|null $catatan_petugas
 * @property string|null $catatan_supervisor
 * @property int|null $rating
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $rejected_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $gps_accuracy
 * @property string|null $gps_address
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property bool $foto_sebelum_verified
 * @property bool $foto_sesudah_verified
 * @property float $verification_score
 * @property array<array-key, mixed>|null $fraud_flags
 * @property bool $manual_review_required
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PhotoMetadata> $afterPhotoMetadata
 * @property-read int|null $after_photo_metadata_count
 * @property-read \App\Models\User|null $approver
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PhotoMetadata> $beforePhotoMetadata
 * @property-read int|null $before_photo_metadata_count
 * @property-read \App\Models\JadwalKebersihan|null $jadwal
 * @property-read \App\Models\Lokasi $lokasi
 * @property-read \App\Models\User $petugas
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PhotoMetadata> $photoMetadata
 * @property-read int|null $photo_metadata_count
 * @method static \Database\Factories\ActivityReportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereCatatanPetugas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereCatatanSupervisor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereFotoSebelum($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereFotoSebelumVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereFotoSesudah($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereFotoSesudahVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereFraudFlags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereGpsAccuracy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereGpsAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereJadwalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereJamMulai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereJamSelesai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereKegiatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereKoordinatLokasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereLokasiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereManualReviewRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport wherePetugasId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereRejectedReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport whereVerificationScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityReport withoutTrashed()
 * @mixin \Eloquent
 */
class ActivityReport extends Model
{
    use HasFactory, SoftDeletes;

    // Reporting status constants
    public const REPORTING_STATUS_ONTIME = 'ontime';
    public const REPORTING_STATUS_LATE = 'late';
    public const REPORTING_STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'petugas_id',
        'lokasi_id',
        'jadwal_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'kegiatan',
        'foto_sebelum',
        'foto_sesudah',
        'koordinat_lokasi',
        'status',
        'catatan_petugas',
        'catatan_supervisor',
        'rating',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'foto_sebelum_verified',
        'foto_sesudah_verified',
        'verification_score',
        'fraud_flags',
        'manual_review_required',
        'reporting_status',
        'is_auto_generated',
        'late_minutes',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
        'foto_sebelum' => 'array',
        'foto_sesudah' => 'array',
        'koordinat_lokasi' => 'array',
        'approved_at' => 'datetime',
        'foto_sebelum_verified' => 'boolean',
        'foto_sesudah_verified' => 'boolean',
        'verification_score' => 'float',
        'fraud_flags' => 'array',
        'manual_review_required' => 'boolean',
        'is_auto_generated' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Set default status to 'submitted' when creating
        static::creating(function ($activityReport) {
            if (empty($activityReport->status)) {
                $activityReport->status = 'submitted';
            }
        });
    }

    // Relationships
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalKebersihan::class, 'jadwal_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function photoMetadata()
    {
        return $this->hasMany(PhotoMetadata::class);
    }

    public function beforePhotoMetadata()
    {
        return $this->hasMany(PhotoMetadata::class)->where('photo_type', 'before');
    }

    public function afterPhotoMetadata()
    {
        return $this->hasMany(PhotoMetadata::class)->where('photo_type', 'after');
    }

    // Reporting status helper methods
    public static function getReportingStatusOptions(): array
    {
        return [
            self::REPORTING_STATUS_ONTIME => 'Tepat Waktu',
            self::REPORTING_STATUS_LATE => 'Terlambat',
            self::REPORTING_STATUS_EXPIRED => 'Tidak Lapor',
        ];
    }

    public function isOntime(): bool
    {
        return $this->reporting_status === self::REPORTING_STATUS_ONTIME;
    }

    public function isLate(): bool
    {
        return $this->reporting_status === self::REPORTING_STATUS_LATE;
    }

    public function isExpired(): bool
    {
        return $this->reporting_status === self::REPORTING_STATUS_EXPIRED;
    }

    public function isAutoGenerated(): bool
    {
        return (bool) $this->is_auto_generated;
    }

    public static function getMaxRatingForExpired(): int
    {
        return 3;
    }
}
