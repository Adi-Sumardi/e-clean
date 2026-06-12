<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityReport;
use App\Models\LaporanOb;
use App\Models\LaporanSatpam;
use App\Models\LaporanToko;
use App\Services\PDFExportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Laporan bulanan + export PDF untuk panel admin PWA. Port dari halaman
 * Filament LaporanBulanan (rekap kebersihan per unit/petugas) ditambah
 * export PDF daftar laporan generik untuk semua domain petugas.
 *
 * @group Reports
 */
class ReportExportController extends Controller
{
    use ApiResponse;

    private const VIEWER_ROLES = ['super_admin', 'admin', 'supervisor', 'pengurus'];

    /** Model laporan per domain (sinkron dengan lib/domain.ts di PWA). */
    private const DOMAIN_MODELS = [
        'kebersihan' => ActivityReport::class,
        'satpam' => LaporanSatpam::class,
        'ob' => LaporanOb::class,
        'toko' => LaporanToko::class,
    ];

    private const DOMAIN_LABELS = [
        'kebersihan' => 'Kebersihan',
        'satpam' => 'Keamanan',
        'ob' => 'Office Boy',
        'toko' => 'Toko',
    ];

    private function canView(Request $request): bool
    {
        return $request->user()->hasAnyRole(self::VIEWER_ROLES);
    }

    /** Laporan kebersihan satu bulan, terfilter unit/petugas (parity Filament). */
    private function monthlyReports(Request $request): Collection
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $start = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $query = ActivityReport::with(['petugas', 'lokasi.unit'])
            ->whereBetween('tanggal', [$start, $end])
            ->whereHas('lokasi.unit');

        if ($request->filled('unit_id')) {
            $unitId = $request->unit_id;
            $query->whereHas('lokasi', fn ($q) => $q->where('unit_id', $unitId));
        }
        if ($request->filled('petugas_id')) {
            $query->where('petugas_id', $request->petugas_id);
        }

        return $query->orderBy('tanggal')->get();
    }

    private function summaryStats(Collection $reports): array
    {
        $total = $reports->count();
        $ontime = $reports->where('reporting_status', 'ontime')->count();
        $late = $reports->where('reporting_status', 'late')->count();
        $expired = $reports->where('reporting_status', 'expired')->count();

        return [
            'total' => $total,
            'ontime' => $ontime,
            'ontime_pct' => $total ? round($ontime / $total * 100, 1) : 0,
            'late' => $late,
            'late_pct' => $total ? round($late / $total * 100, 1) : 0,
            'expired' => $expired,
            'expired_pct' => $total ? round($expired / $total * 100, 1) : 0,
            'avg_rating' => round($reports->whereNotNull('rating')->avg('rating') ?? 0, 1),
        ];
    }

    /** Rekap bulanan (JSON) untuk halaman Laporan Bulanan PWA. */
    public function monthly(Request $request): JsonResponse
    {
        try {
            if (! $this->canView($request)) {
                return $this->forbiddenResponse('You are not allowed to view monthly reports.');
            }

            $reports = $this->monthlyReports($request);

            $units = $reports
                ->groupBy(fn ($r) => $r->lokasi->unit->nama_unit ?? 'Tanpa Unit')
                ->map(function ($unitReports, $unitName) {
                    $petugas = $unitReports
                        ->groupBy(fn ($r) => $r->petugas->name ?? 'Unknown')
                        ->map(fn ($rows, $name) => [
                            'name' => $name,
                            'total' => $rows->count(),
                            'ontime' => $rows->where('reporting_status', 'ontime')->count(),
                            'late' => $rows->where('reporting_status', 'late')->count(),
                            'expired' => $rows->where('reporting_status', 'expired')->count(),
                            'approved' => $rows->where('status', 'approved')->count(),
                            'avg_rating' => round($rows->whereNotNull('rating')->avg('rating') ?? 0, 1),
                        ])
                        ->values();

                    return [
                        'unit' => $unitName,
                        'total' => $unitReports->count(),
                        'petugas' => $petugas,
                    ];
                })
                ->values();

            return $this->successResponse([
                'stats' => $this->summaryStats($reports),
                'units' => $units,
            ], 'Monthly report retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve monthly report: ' . $e->getMessage(), 500);
        }
    }

    /** Unduh PDF laporan bulanan (rekap per unit & petugas, parity Filament). */
    public function monthlyPdf(Request $request)
    {
        if (! $this->canView($request)) {
            return $this->forbiddenResponse('You are not allowed to export monthly reports.');
        }

        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $reports = $this->monthlyReports($request);
        $stats = $this->summaryStats($reports);

        $grouped = $reports->groupBy(fn ($r) => $r->lokasi->unit->nama_unit ?? 'Tanpa Unit')
            ->map(fn ($unitReports) => $unitReports->groupBy(fn ($r) => $r->petugas->name ?? 'Unknown'));

        $monthName = Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y');

        $pdf = app(PDFExportService::class)->exportMonthlyReport($grouped, $stats, [
            'period' => $monthName,
        ]);

        $filename = "laporan-bulanan-{$tahun}-{$bulan}-" . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    /** Unduh PDF daftar laporan (semua domain) sesuai filter aktif. */
    public function listPdf(Request $request)
    {
        try {
            if (! $this->canView($request)) {
                return $this->forbiddenResponse('You are not allowed to export reports.');
            }

            $validated = $request->validate([
                'domain' => ['required', 'in:kebersihan,satpam,ob,toko'],
                'bulan' => ['nullable', 'integer', 'min:1', 'max:12'],
                'tahun' => ['nullable', 'integer', 'min:2020', 'max:2100'],
                'status' => ['nullable', 'in:draft,submitted,approved,rejected'],
                'unit_id' => ['nullable', 'integer'],
                'petugas_id' => ['nullable', 'integer'],
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        }

        $bulan = (int) ($validated['bulan'] ?? now()->month);
        $tahun = (int) ($validated['tahun'] ?? now()->year);

        $model = self::DOMAIN_MODELS[$validated['domain']];
        $query = $model::with(['petugas', 'lokasi.unit'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['unit_id'])) {
            $query->whereHas('lokasi', fn ($q) => $q->where('unit_id', $validated['unit_id']));
        }
        if (! empty($validated['petugas_id'])) {
            $query->where('petugas_id', $validated['petugas_id']);
        }

        $reports = $query->orderBy('tanggal')->orderBy('jam_mulai')->get();

        $label = self::DOMAIN_LABELS[$validated['domain']];
        $monthName = Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y');

        $pdf = app(PDFExportService::class)->exportLaporanList($reports, [
            'title' => "Laporan {$label}",
            'period' => $monthName,
        ]);

        $filename = "laporan-{$validated['domain']}-{$tahun}-{$bulan}-" . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }
}
