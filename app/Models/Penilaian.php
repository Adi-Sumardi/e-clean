<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
