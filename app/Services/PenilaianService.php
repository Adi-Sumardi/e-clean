<?php

namespace App\Services;

use App\Models\ActivityReport;
use App\Models\LaporanKeterlambatan;
use App\Models\Penilaian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PenilaianService
{
    /**
     * Generate atau update penilaian bulanan untuk petugas berdasarkan activity reports
     * Dipanggil setiap kali ada approval baru
     *
     * @param int $petugasId
     * @param int $bulan
     * @param int $tahun
     * @param int $penilaiId (Supervisor yang melakukan approval)
     * @return Penilaian
     */
    public function generateOrUpdateMonthlyPenilaian(int $petugasId, int $bulan, int $tahun, int $penilaiId): Penilaian
    {
        // Ambil semua laporan yang sudah approved bulan ini
        $approvedReports = ActivityReport::where('petugas_id', $petugasId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('status', 'approved')
            ->get();

        // Ambil laporan keterlambatan bulan ini
        $lateReports = LaporanKeterlambatan::where('petugas_id', $petugasId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        // Ambil total jadwal bulan ini
        $totalSchedules = DB::table('jadwal_kebersihanans')
            ->where('petugas_id', $petugasId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->count();

        // === HITUNG SKOR (3 ASPEK - Tidak ada Kehadiran) ===

        // 1. Skor Kualitas (average dari rating yang diberikan supervisor)
        $averageRating = $approvedReports->whereNotNull('rating')->avg('rating') ?? 0;
        $skorKualitas = round($averageRating, 2); // Rating sudah 1-5

        // 2. Skor Ketepatan Waktu (berdasarkan completion rate & laporan keterlambatan)
        // Kombinasi: apakah menyelesaikan semua jadwal + apakah tepat waktu
        $completionRate = $totalSchedules > 0 ? ($approvedReports->count() / $totalSchedules) * 100 : 0;
        $latePercentage = $totalSchedules > 0 ? ($lateReports->count() / $totalSchedules) * 100 : 0;
        $skorKetepatanWaktu = $this->calculateKetepatanWaktuScore($completionRate, $latePercentage);

        // 3. Skor Kebersihan (dari rating khusus atau sama dengan kualitas)
        // Bisa di-enhance dengan field khusus rating_kebersihan di activity_reports
        $skorKebersihan = $skorKualitas; // Sementara sama dengan kualitas

        // NOTE: Tidak ada skor_kehadiran karena sistem sudah tidak pakai Presensi
        // Aspek ketepatan waktu sudah cover completion rate

        // Hitung total & rata-rata (3 aspek saja)
        $totalSkor = $skorKualitas + $skorKetepatanWaktu + $skorKebersihan;
        $rataRata = $totalSkor / 3;

        // Tentukan kategori
        $kategori = $this->determineKategori($rataRata);

        // Generate catatan otomatis
        $catatan = $this->generateCatatan(
            $approvedReports->count(),
            $totalSchedules,
            $lateReports->count(),
            $averageRating
        );

        // Cari atau buat penilaian
        $penilaian = Penilaian::updateOrCreate(
            [
                'petugas_id' => $petugasId,
                'periode_bulan' => $bulan,
                'periode_tahun' => $tahun,
            ],
            [
                'penilai_id' => $penilaiId,
                'skor_kehadiran' => 0, // Deprecated - tidak dipakai lagi
                'skor_kualitas' => $skorKualitas,
                'skor_ketepatan_waktu' => $skorKetepatanWaktu,
                'skor_kebersihan' => $skorKebersihan,
                'total_skor' => $totalSkor,
                'rata_rata' => $rataRata,
                'kategori' => $kategori,
                'catatan' => $catatan,
            ]
        );

        return $penilaian;
    }

    /**
     * Hitung skor ketepatan waktu dari completion rate & late percentage
     * Kombinasi: apakah menyelesaikan semua jadwal + apakah tepat waktu
     *
     * @param float $completionRate (0-100) - Berapa % jadwal yang diselesaikan
     * @param float $latePercentage (0-100) - Berapa % yang terlambat
     * @return float (1-5)
     */
    private function calculateKetepatanWaktuScore(float $completionRate, float $latePercentage): float
    {
        // Completion Score (50% weight)
        $completionScore = match(true) {
            $completionRate >= 95 => 5.0,
            $completionRate >= 85 => 4.5,
            $completionRate >= 75 => 4.0,
            $completionRate >= 65 => 3.5,
            $completionRate >= 50 => 3.0,
            $completionRate >= 35 => 2.5,
            default => 2.0,
        };

        // Punctuality Score (50% weight) - Dari yang diselesaikan, berapa yang tepat waktu?
        $punctualityScore = match(true) {
            $latePercentage == 0 => 5.0,
            $latePercentage < 5 => 4.5,
            $latePercentage < 10 => 4.0,
            $latePercentage < 20 => 3.5,
            $latePercentage < 30 => 3.0,
            $latePercentage < 40 => 2.5,
            default => 2.0,
        };

        // Kombinasi keduanya (50-50)
        return round(($completionScore * 0.5) + ($punctualityScore * 0.5), 2);
    }

    /**
     * Tentukan kategori berdasarkan rata-rata
     *
     * @param float $rataRata
     * @return string
     */
    private function determineKategori(float $rataRata): string
    {
        if ($rataRata >= 4.5) return 'Sangat Baik';
        if ($rataRata >= 3.5) return 'Baik';
        if ($rataRata >= 2.5) return 'Cukup';
        return 'Kurang';
    }

    /**
     * Generate catatan otomatis
     *
     * @param int $completedReports
     * @param int $totalSchedules
     * @param int $lateCount
     * @param float $averageRating
     * @return string
     */
    private function generateCatatan(int $completedReports, int $totalSchedules, int $lateCount, float $averageRating): string
    {
        $completionRate = $totalSchedules > 0 ? round(($completedReports / $totalSchedules) * 100, 1) : 0;
        $latePercentage = $totalSchedules > 0 ? round(($lateCount / $totalSchedules) * 100, 1) : 0;

        $catatan = "📊 Penilaian Otomatis Bulan Ini:\n\n";
        $catatan .= "✅ Penyelesaian Tugas:\n";
        $catatan .= "   • {$completedReports} dari {$totalSchedules} jadwal ({$completionRate}%)\n\n";

        $catatan .= "⭐ Kualitas Kerja:\n";
        $catatan .= "   • Rating rata-rata: " . round($averageRating, 2) . "/5.0\n\n";

        $catatan .= "⏱️ Ketepatan Waktu:\n";
        $catatan .= "   • Keterlambatan: {$lateCount} kali ({$latePercentage}%)\n\n";

        // Feedback berdasarkan performa
        $catatan .= "💬 Catatan:\n";
        if ($averageRating >= 4.5 && $latePercentage < 10 && $completionRate >= 90) {
            $catatan .= "   Kinerja sangat memuaskan! Pertahankan konsistensi ini.";
        } elseif ($averageRating >= 4.0 && $completionRate >= 80) {
            $catatan .= "   Kinerja baik, terus tingkatkan ketepatan waktu.";
        } elseif ($completionRate < 70) {
            $catatan .= "   Perlu meningkatkan completion rate. Pastikan semua jadwal diselesaikan.";
        } elseif ($latePercentage > 30) {
            $catatan .= "   Terlalu banyak keterlambatan. Fokus pada manajemen waktu.";
        } else {
            $catatan .= "   Perlu peningkatan kualitas kerja secara keseluruhan.";
        }

        return $catatan;
    }

    /**
     * Update penilaian saat ada approval baru
     *
     * @param ActivityReport $report
     * @return Penilaian|null
     */
    public function updatePenilaianAfterApproval(ActivityReport $report): ?Penilaian
    {
        if ($report->status !== 'approved' || !$report->approved_by) {
            return null;
        }

        $bulan = $report->tanggal->month;
        $tahun = $report->tanggal->year;

        return $this->generateOrUpdateMonthlyPenilaian(
            $report->petugas_id,
            $bulan,
            $tahun,
            $report->approved_by
        );
    }
}
