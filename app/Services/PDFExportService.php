<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class PDFExportService
{
    /**
     * Export Activity Reports to PDF
     *
     * @param Collection $reports
     * @param array $options
     * @return \Barryvdh\DomPDF\PDF
     */
    public function exportActivityReports(Collection $reports, array $options = [])
    {
        $title = $options['title'] ?? 'Laporan Kegiatan Cleaning Service';
        $period = $options['period'] ?? null;

        $data = [
            'title' => $title,
            'period' => $period,
            'reports' => $reports,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'totalReports' => $reports->count(),
            'approvedReports' => $reports->where('status', 'approved')->count(),
            'pendingReports' => $reports->where('status', 'pending')->count(),
            'rejectedReports' => $reports->where('status', 'rejected')->count(),
        ];

        $pdf = Pdf::loadView('pdf.activity-reports', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf;
    }


    /**
     * Export Monthly Summary to PDF
     *
     * @param array $data
     * @return \Barryvdh\DomPDF\PDF
     */
    public function exportMonthlySummary(array $data)
    {
        $pdf = Pdf::loadView('pdf.monthly-summary', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Export Monthly Report to PDF (grouped by Unit & Petugas)
     *
     * @param \Illuminate\Support\Collection $grouped
     * @param array $stats
     * @param array $options
     * @return \Barryvdh\DomPDF\PDF
     */
    public function exportMonthlyReport($grouped, array $stats, array $options = [])
    {
        $data = [
            'title' => $options['title'] ?? 'Laporan Bulanan',
            'period' => $options['period'] ?? null,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'grouped' => $grouped,
            'stats' => $stats,
        ];

        $pdf = Pdf::loadView('pdf.monthly-report', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf;
    }

    /**
     * Export Performance Report to PDF
     *
     * @param Collection $petugasData
     * @param array $options
     * @return \Barryvdh\DomPDF\PDF
     */
    public function exportPerformanceReport(Collection $petugasData, array $options = [])
    {
        $title = $options['title'] ?? 'Laporan Performa Petugas';
        $period = $options['period'] ?? null;

        $data = [
            'title' => $title,
            'period' => $period,
            'petugasData' => $petugasData,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.performance-report', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf;
    }
}
