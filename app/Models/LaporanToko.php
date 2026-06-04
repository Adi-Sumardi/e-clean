<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaporanToko extends Model
{
    use SoftDeletes;

    protected $table = 'laporan_toko';

    protected $fillable = [
        'jadwal_id',
        'petugas_id',
        'lokasi_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'checklist',
        'kondisi_stok',
        'catatan_stok',
        'foto',
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
        'checklist' => 'array',
        'foto' => 'array',
        'rating' => 'integer',
        'approved_at' => 'datetime',
    ];

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
        return $this->belongsTo(JadwalToko::class, 'jadwal_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
