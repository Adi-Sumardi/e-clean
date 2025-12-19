<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestComplaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lokasi_id',
        'nama_pelapor',
        'email_pelapor',
        'telepon_pelapor',
        'jenis_keluhan',
        'deskripsi_keluhan',
        'foto_keluhan',
        'status',
        'handled_by',
        'handled_at',
        'catatan_penanganan',
        'foto_penanganan',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_REJECTED = 'rejected';

    // Jenis keluhan constants
    public const JENIS_TUMPAHAN = 'tumpahan';
    public const JENIS_KOTOR = 'kotor';
    public const JENIS_BAU = 'bau';
    public const JENIS_RUSAK = 'rusak';
    public const JENIS_LAINNYA = 'lainnya';

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_IN_PROGRESS => 'Sedang Ditangani',
            self::STATUS_RESOLVED => 'Selesai',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }

    public static function getJenisKeluhanOptions(): array
    {
        return [
            self::JENIS_TUMPAHAN => 'Tumpahan',
            self::JENIS_KOTOR => 'Kotor',
            self::JENIS_BAU => 'Bau',
            self::JENIS_RUSAK => 'Fasilitas Rusak',
            self::JENIS_LAINNYA => 'Lainnya',
        ];
    }

    // Relationships
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    public function scopeForLokasi($query, $lokasiId)
    {
        return $query->where('lokasi_id', $lokasiId);
    }
}
