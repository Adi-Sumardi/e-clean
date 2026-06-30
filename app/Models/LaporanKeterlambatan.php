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
        'domain',
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

    /**
     * Hapus catatan keterlambatan yang sudah "terjawab" oleh laporan masuk.
     *
     * Penting untuk PWA offline-first: laporan bisa dibuat petugas SEBELUM
     * batas waktu tapi baru tersinkron SETELAH CheckMissedSchedules terlanjur
     * mencatat "tidak lapor". Saat laporan akhirnya tiba (created/submitted),
     * catatan keterlambatan yang cocok dihapus agar tidak jadi false positive.
     */
    public static function resolveForReport(Model $report, string $domain): int
    {
        if (! in_array($report->status, ['submitted', 'approved'], true)) {
            return 0;
        }

        $query = static::where('domain', $domain)
            ->where('petugas_id', $report->petugas_id)
            ->where('lokasi_id', $report->lokasi_id)
            ->whereDate('tanggal', $report->tanggal);

        // Persempit ke shift jadwal terkait bila ada, agar shift lain yang
        // benar-benar terlewat tetap tercatat.
        $shift = $report->jadwal?->shift;
        if ($shift) {
            $query->where('shift', $shift);
        }

        return $query->delete();
    }

    // Helper methods
    public static function getShiftTimeRanges(): array
    {
        $shifts = Setting::get('work_shifts');
        if ($shifts && is_array($shifts)) {
            $ranges = [];
            foreach ($shifts as $shift) {
                if (isset($shift['value'], $shift['mulai'], $shift['selesai'])) {
                    $ranges[$shift['value']] = [
                        'start' => $shift['mulai'],
                        'end' => $shift['selesai'],
                    ];
                }
            }
            return $ranges;
        }

        // Gunakan WorkShift enum sebagai single source of truth fallback
        $ranges = [];
        foreach (\App\Enums\WorkShift::cases() as $shift) {
            $ranges[$shift->value] = [
                'start' => $shift->jamMulai(),
                'end' => $shift->jamSelesai(),
            ];
        }
        return $ranges;
    }

    public static function getShiftTimeRange(string $shift): array
    {
        $shifts = Setting::get('work_shifts');
        if ($shifts && is_array($shifts)) {
            foreach ($shifts as $s) {
                if (isset($s['value'], $s['mulai'], $s['selesai']) && $s['value'] === $shift) {
                    return [
                        'start' => $s['mulai'],
                        'end' => $s['selesai'],
                    ];
                }
            }
        }

        // Lookup dari WorkShift enum langsung
        $workShift = \App\Enums\WorkShift::tryFrom($shift);
        if ($workShift) {
            return [
                'start' => $workShift->jamMulai(),
                'end' => $workShift->jamSelesai(),
            ];
        }

        return ['start' => '00:00', 'end' => '00:00'];
    }
}
