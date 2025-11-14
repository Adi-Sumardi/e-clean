<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityReport extends Model
{
    use HasFactory, SoftDeletes;
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
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
        'foto_sebelum' => 'array',
        'foto_sesudah' => 'array',
        'koordinat_lokasi' => 'array',
        'approved_at' => 'datetime',
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
}
