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
 * @property \Illuminate\Support\Carbon $tanggal
 * @property string $shift
 * @property \Illuminate\Support\Carbon $jam_mulai
 * @property \Illuminate\Support\Carbon $jam_selesai
 * @property string $prioritas
 * @property string|null $catatan
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $status
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Lokasi $lokasi
 * @property-read \App\Models\User $petugas
 * @method static \Database\Factories\JadwalKebersihanFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereJamMulai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereJamSelesai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereLokasiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan wherePetugasId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan wherePrioritas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereShift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKebersihan withoutTrashed()
 * @mixin \Eloquent
 */
class JadwalKebersihan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'jadwal_kebersihanans';

    protected $fillable = [
        'petugas_id',
        'lokasi_id',
        'tanggal',
        'shift',
        'jam_mulai',
        'jam_selesai',
        'prioritas',
        'catatan',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
    ];

    // Relationships
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
