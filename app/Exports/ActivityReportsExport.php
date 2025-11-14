<?php

namespace App\Exports;

use App\Models\ActivityReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ActivityReportsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $query;
    protected $rowNumber = 1;

    public function __construct($query = null)
    {
        $this->query = $query;
    }

    public function collection()
    {
        $query = $this->query ?: ActivityReport::query();

        return $query->with(['petugas', 'lokasi'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Petugas',
            'Lokasi',
            'Kode Lokasi',
            'Kategori',
            'Waktu Mulai',
            'Waktu Selesai',
            'Durasi (menit)',
            'Rating',
            'Catatan',
            'Status',
            'Koordinat GPS',
            'Akurasi GPS (m)',
        ];
    }

    public function map($report): array
    {
        $startTime = $report->waktu_mulai ? \Carbon\Carbon::parse($report->waktu_mulai) : null;
        $endTime = $report->waktu_selesai ? \Carbon\Carbon::parse($report->waktu_selesai) : null;
        $duration = $startTime && $endTime ? $startTime->diffInMinutes($endTime) : '-';

        $gpsCoords = '-';
        if ($report->latitude && $report->longitude) {
            $gpsCoords = number_format($report->latitude, 6) . ', ' . number_format($report->longitude, 6);
        }

        return [
            $this->rowNumber++,
            $report->created_at ? $report->created_at->format('d/m/Y H:i') : '-',
            $report->petugas->name ?? '-',
            $report->lokasi->nama_lokasi ?? '-',
            $report->lokasi->kode_lokasi ?? '-',
            $report->lokasi->kategori ?? '-',
            $startTime ? $startTime->format('H:i') : '-',
            $endTime ? $endTime->format('H:i') : '-',
            $duration,
            $report->rating ? $report->rating . '/5' : '-',
            $report->catatan ?? '-',
            $this->getStatusLabel($report->status),
            $gpsCoords,
            $report->gps_accuracy ? number_format($report->gps_accuracy, 2) : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 18,  // Tanggal
            'C' => 20,  // Petugas
            'D' => 25,  // Lokasi
            'E' => 15,  // Kode Lokasi
            'F' => 12,  // Kategori
            'G' => 12,  // Waktu Mulai
            'H' => 12,  // Waktu Selesai
            'I' => 15,  // Durasi
            'J' => 10,  // Rating
            'K' => 35,  // Catatan
            'L' => 12,  // Status
            'M' => 25,  // GPS
            'N' => 15,  // Akurasi
        ];
    }

    private function getStatusLabel($status): string
    {
        return match($status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => $status,
        };
    }
}
