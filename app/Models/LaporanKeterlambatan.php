<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $jadwal_kebersihan_id
 * @property int $petugas_id
 * @property int $lokasi_id
 * @property \Illuminate\Support\Carbon $tanggal
 * @property string $shift
 * @property \Illuminate\Support\Carbon $batas_waktu_mulai
 * @property \Illuminate\Support\Carbon $batas_waktu_selesai
 * @property string $status
 * @property string|null $keterangan
 * @property \Illuminate\Support\Carbon $waktu_terdeteksi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\JadwalKebersihan $jadwalKebersihan
 * @property-read \App\Models\Lokasi $lokasi
 * @property-read \App\Models\User $petugas
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereBatasWaktuMulai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereBatasWaktuSelesai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereJadwalKebersihanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereKeterangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereLokasiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan wherePetugasId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereShift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanKeterlambatan whereWaktuTerdeteksi($value)
 * @mixin \Eloquent
 */
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
