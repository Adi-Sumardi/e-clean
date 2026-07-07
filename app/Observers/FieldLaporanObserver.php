<?php

namespace App\Observers;

use App\Services\PenilaianService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Observer bersama untuk laporan domain field (satpam/ob/toko).
 *
 * Saat laporan disetujui (status → approved), otomatis memicu generate/update
 * penilaian bulanan petugas terkait — sama seperti kebersihan
 * (ActivityReportObserver), tapi memakai tabel laporan & jadwal domainnya.
 */
class FieldLaporanObserver
{
    protected PenilaianService $penilaianService;

    /** Kunci domain keterlambatan per model laporan field. */
    private const DOMAINS = [
        \App\Models\LaporanSatpam::class => 'satpam',
        \App\Models\LaporanOb::class => 'ob',
        \App\Models\LaporanToko::class => 'toko',
    ];

    /** Model jadwal yang harus di-complete saat laporan masuk. */
    private const JADWAL_MODELS = [
        \App\Models\LaporanSatpam::class => \App\Models\JadwalSatpam::class,
        \App\Models\LaporanOb::class     => \App\Models\JadwalOb::class,
        \App\Models\LaporanToko::class   => \App\Models\JadwalToko::class,
    ];

    public function __construct()
    {
        $this->penilaianService = new PenilaianService();
    }

    public function created(Model $report): void
    {
        // Laporan masuk (termasuk hasil sync offline PWA yang telat tiba) →
        // hapus catatan keterlambatan yang terlanjur dibuat sistem.
        $domain = self::DOMAINS[get_class($report)] ?? null;
        if (! $domain) {
            return;
        }

        try {
            \App\Models\LaporanKeterlambatan::resolveForReport($report, $domain);
        } catch (\Throwable $e) {
            Log::error('Gagal resolve keterlambatan field', [
                'report_class' => get_class($report),
                'report_id' => $report->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        // Tandai jadwal sebagai completed agar tidak muncul lagi di beranda.
        if (!empty($report->jadwal_id)) {
            $jadwalClass = self::JADWAL_MODELS[get_class($report)] ?? null;
            if ($jadwalClass) {
                try {
                    $jadwalClass::where('id', $report->jadwal_id)
                        ->whereIn('status', ['pending', 'in_progress'])
                        ->update(['status' => 'completed']);
                } catch (\Throwable $e) {
                    Log::warning('Gagal update status jadwal field ke completed', [
                        'report_class' => get_class($report),
                        'jadwal_id'    => $report->jadwal_id,
                        'error'        => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    public function updated(Model $report): void
    {
        // Draft yang baru di-submit juga menggugurkan catatan keterlambatan.
        if ($report->wasChanged('status')) {
            $this->created($report);
        }

        if ($report->wasChanged('status') && $report->status === 'approved') {
            try {
                $penilaian = $this->penilaianService->updatePenilaianAfterApproval($report);

                if ($penilaian) {
                    Log::info('Penilaian field updated after approval', [
                        'report_class' => get_class($report),
                        'report_id' => $report->id,
                        'penilaian_id' => $penilaian->id,
                        'petugas_id' => $report->petugas_id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Gagal update penilaian field', [
                    'report_class' => get_class($report),
                    'report_id' => $report->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
