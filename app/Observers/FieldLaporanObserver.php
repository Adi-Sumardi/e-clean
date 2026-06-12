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

    public function __construct()
    {
        $this->penilaianService = new PenilaianService();
    }

    public function updated(Model $report): void
    {
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
