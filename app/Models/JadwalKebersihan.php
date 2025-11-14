<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
