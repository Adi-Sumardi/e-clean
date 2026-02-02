<?php

namespace App\Filament\Pages;

use App\Models\ActivityReport;
use App\Models\Unit;
use App\Models\User;
use App\Services\PDFExportService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class LaporanBulanan extends Page
{
    protected string $view = 'filament.pages.laporan-bulanan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Bulanan';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 3;

    public int $bulan;
    public int $tahun;
    public ?string $unitFilter = null;
    public ?string $petugasFilter = null;

    public function mount(): void
    {
        $this->bulan = now()->month;
        $this->tahun = now()->year;
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor', 'pengurus']);
    }

    public function getTitle(): string
    {
        return 'Laporan Bulanan';
    }

    public function getFilteredReports(): Collection
    {
        $startDate = Carbon::create($this->tahun, $this->bulan, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = ActivityReport::with(['petugas', 'lokasi.unit'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereHas('lokasi.unit');

        if ($this->unitFilter) {
            $query->whereHas('lokasi', fn ($q) => $q->where('unit_id', $this->unitFilter));
        }

        if ($this->petugasFilter) {
            $query->where('petugas_id', $this->petugasFilter);
        }

        return $query->orderBy('tanggal')->get();
    }

    public function getReportData(): array
    {
        $reports = $this->getFilteredReports();

        $grouped = $reports->groupBy(fn ($r) => $r->lokasi->unit->nama_unit ?? 'Tanpa Unit')
            ->map(fn ($unitReports) => $unitReports->groupBy(fn ($r) => $r->petugas->name ?? 'Unknown'));

        return $grouped->toArray();
    }

    public function getSummaryStats(): array
    {
        $reports = $this->getFilteredReports();
        $total = $reports->count();

        return [
            'total' => $total,
            'ontime' => $total ? $reports->where('reporting_status', 'ontime')->count() : 0,
            'ontime_pct' => $total ? round($reports->where('reporting_status', 'ontime')->count() / $total * 100, 1) : 0,
            'late' => $total ? $reports->where('reporting_status', 'late')->count() : 0,
            'late_pct' => $total ? round($reports->where('reporting_status', 'late')->count() / $total * 100, 1) : 0,
            'expired' => $total ? $reports->where('reporting_status', 'expired')->count() : 0,
            'expired_pct' => $total ? round($reports->where('reporting_status', 'expired')->count() / $total * 100, 1) : 0,
            'avg_rating' => round($reports->whereNotNull('rating')->avg('rating') ?? 0, 1),
        ];
    }

    public function getUnitOptions(): array
    {
        return Unit::where('is_active', true)->orderBy('nama_unit')->pluck('nama_unit', 'id')->toArray();
    }

    public function getPetugasOptions(): array
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', 'petugas'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function downloadPdf()
    {
        $reports = $this->getFilteredReports();
        $stats = $this->getSummaryStats();

        $grouped = $reports->groupBy(fn ($r) => $r->lokasi->unit->nama_unit ?? 'Tanpa Unit')
            ->map(fn ($unitReports) => $unitReports->groupBy(fn ($r) => $r->petugas->name ?? 'Unknown'));

        $monthName = Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y');

        $pdfService = new PDFExportService();
        $pdf = $pdfService->exportMonthlyReport($grouped, $stats, [
            'period' => $monthName,
        ]);

        $filename = "laporan-bulanan-{$this->tahun}-{$this->bulan}-" . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }
}
