<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $petugas_id
 * @property int $penilai_id
 * @property int $periode_bulan
 * @property int $periode_tahun
 * @property numeric $skor_kehadiran
 * @property numeric $skor_kualitas
 * @property numeric $skor_ketepatan_waktu
 * @property numeric $skor_kebersihan
 * @property numeric $total_skor
 * @property numeric $rata_rata
 * @property string $kategori
 * @property string|null $catatan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $penilai
 * @property-read \App\Models\User $petugas
 * @method static \Database\Factories\PenilaianFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereKategori($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian wherePenilaiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian wherePeriodeBulan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian wherePeriodeTahun($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian wherePetugasId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereRataRata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereSkorKebersihan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereSkorKehadiran($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereSkorKetepatanWaktu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereSkorKualitas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereTotalSkor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Penilaian withoutTrashed()
 * @mixin \Eloquent
 */
class Penilaian extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'petugas_id',
        'penilai_id',
        'periode_bulan',
        'periode_tahun',
        'skor_kehadiran',
        'skor_kualitas',
        'skor_ketepatan_waktu',
        'skor_kebersihan',
        'total_skor',
        'rata_rata',
        'kategori',
        'catatan',
    ];

    protected $casts = [
        'periode_bulan' => 'integer',
        'periode_tahun' => 'integer',
        'skor_kehadiran' => 'decimal:2',
        'skor_kualitas' => 'decimal:2',
        'skor_ketepatan_waktu' => 'decimal:2',
        'skor_kebersihan' => 'decimal:2',
        'total_skor' => 'decimal:2',
        'rata_rata' => 'decimal:2',
    ];

    // Relationships
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function penilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penilai_id');
    }
}
