<?php

namespace App\Models;

use App\Services\QRCodeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $kode_lokasi
 * @property string $nama_lokasi
 * @property string|null $deskripsi
 * @property string $kategori
 * @property string|null $lantai
 * @property numeric|null $luas_area
 * @property string|null $foto_lokasi
 * @property string|null $qr_code
 * @property string $status_kebersihan
 * @property \Illuminate\Support\Carbon|null $last_cleaned_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $address
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityReport> $activityReports
 * @property-read int|null $activity_reports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JadwalKebersihan> $jadwalKebersihanans
 * @property-read int|null $jadwal_kebersihanans_count
 * @method static \Database\Factories\LokasiFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereFotoLokasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereKategori($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereKodeLokasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereLantai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereLastCleanedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereLuasArea($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereNamaLokasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereQrCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereStatusKebersihan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lokasi withoutTrashed()
 * @mixin \Eloquent
 */
class Lokasi extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::created(function (Lokasi $lokasi) {
            // Auto-generate QR Code when a new Lokasi is created
            $qrCodeService = new QRCodeService();
            $qrCodeService->generateForLokasi($lokasi);
        });
    }

    protected $fillable = [
        'unit_id',
        'kode_lokasi',
        'nama_lokasi',
        'deskripsi',
        'kategori',
        'lantai',
        'luas_area',
        'foto_lokasi',
        'qr_code',
        'status_kebersihan',
        'last_cleaned_at',
        'is_active',
    ];

    protected $casts = [
        'luas_area' => 'decimal:2',
        'last_cleaned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function jadwalKebersihanans(): HasMany
    {
        return $this->hasMany(JadwalKebersihan::class);
    }

    public function activityReports(): HasMany
    {
        return $this->hasMany(ActivityReport::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function guestComplaints(): HasMany
    {
        return $this->hasMany(GuestComplaint::class);
    }
}
