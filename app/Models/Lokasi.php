<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lokasi extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
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
}
