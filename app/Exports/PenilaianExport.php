<?php

namespace App\Exports;

use App\Models\Penilaian;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PenilaianExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $query;
    protected $rowNumber = 1;

    public function __construct($query = null)
    {
        $this->query = $query;
    }

    public function collection()
    {
        $query = $this->query ?: Penilaian::query();

        return $query->with(['petugas', 'penilai'])
            ->orderBy('periode_bulan', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Petugas',
            'Periode Bulan',
            'Penilai',
            'Skor Kualitas',
            'Skor Kecepatan',
            'Skor Konsistensi',
            'Skor Total',
            'Grade',
            'Catatan Penilai',
            'Tanggal Penilaian',
        ];
    }

    public function map($penilaian): array
    {
        return [
            $this->rowNumber++,
            $penilaian->petugas->name ?? '-',
            $penilaian->periode_bulan ? \Carbon\Carbon::parse($penilaian->periode_bulan)->format('F Y') : '-',
            $penilaian->penilai->name ?? '-',
            $penilaian->skor_kualitas ?? '-',
            $penilaian->skor_kecepatan ?? '-',
            $penilaian->skor_konsistensi ?? '-',
            $penilaian->skor_total ?? '-',
            $this->getGrade($penilaian->skor_total),
            $penilaian->catatan ?? '-',
            $penilaian->created_at ? $penilaian->created_at->format('d/m/Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F59E0B'],
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
            'B' => 20,  // Petugas
            'C' => 15,  // Periode Bulan
            'D' => 20,  // Penilai
            'E' => 15,  // Skor Kualitas
            'F' => 15,  // Skor Kecepatan
            'G' => 15,  // Skor Konsistensi
            'H' => 12,  // Skor Total
            'I' => 8,   // Grade
            'J' => 35,  // Catatan
            'K' => 18,  // Tanggal Penilaian
        ];
    }

    private function getGrade(?float $skor): string
    {
        if ($skor === null) {
            return '-';
        }

        return match (true) {
            $skor >= 90 => 'A',
            $skor >= 80 => 'B',
            $skor >= 70 => 'C',
            $skor >= 60 => 'D',
            default => 'E',
        };
    }
}
