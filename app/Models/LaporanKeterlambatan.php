<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanKeterlambatan extends Model
{
    protected $table = 'laporan_keterlambatan';

    protected $fillable = [
        'jadwal_kebersihan_id',
        'petugas_id',
        'lokasi_id',
        'tanggal',
        'shift',
        'batas_waktu_mulai',
        'batas_waktu_selesai',
        'status',
        'keterangan',
        'waktu_terdeteksi',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'batas_waktu_mulai' => 'datetime:H:i',
        'batas_waktu_selesai' => 'datetime:H:i',
        'waktu_terdeteksi' => 'datetime',
    ];

    // Relationships
    public function jadwalKebersihan(): BelongsTo
    {
        return $this->belongsTo(JadwalKebersihan::class, 'jadwal_kebersihan_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    // Helper methods
    public static function getShiftTimeRanges(): array
    {
        return [
            'pagi' => ['start' => '05:00', 'end' => '08:00'],
            'siang' => ['start' => '10:00', 'end' => '14:00'],
            'sore' => ['start' => '15:00', 'end' => '18:00'],
        ];
    }

    public static function getShiftTimeRange(string $shift): array
    {
        return self::getShiftTimeRanges()[$shift] ?? ['start' => '00:00', 'end' => '00:00'];
    }
}
