<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kode_unit',
        'nama_unit',
        'deskripsi',
        'alamat',
        'penanggung_jawab',
        'telepon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function lokasis(): HasMany
    {
        return $this->hasMany(Lokasi::class);
    }

    // Accessor for display name
    public function getFullNameAttribute(): string
    {
        return "[{$this->kode_unit}] {$this->nama_unit}";
    }
}
