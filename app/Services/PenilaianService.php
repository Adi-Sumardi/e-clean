<?php

namespace App\Services;

use App\Models\ActivityReport;
use App\Models\LaporanKeterlambatan;
use App\Models\LaporanOb;
use App\Models\LaporanSatpam;
use App\Models\LaporanToko;
use App\Models\Penilaian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
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
    public function generateOrUpdateMonthlyPenilaian(
        int $petugasId,
        int $bulan,
        int $tahun,
        int $penilaiId,
        string $reportClass = ActivityReport::class,
        string $jadwalTable = 'jadwal_kebersihanans',
        bool $hasLate = true
    ): Penilaian {
        // Ambil semua laporan yang sudah approved bulan ini (per domain)
        $approvedReports = $reportClass::where('petugas_id', $petugasId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('status', 'approved')
            ->get();

        // Laporan keterlambatan hanya ada untuk domain kebersihan
        $lateCount = 0;
        if ($hasLate) {
            $lateCount = LaporanKeterlambatan::where('petugas_id', $petugasId)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->count();
        }

        // Ambil total jadwal bulan ini (tabel jadwal per domain)
        $totalSchedules = DB::table($jadwalTable)
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
        $latePercentage = $totalSchedules > 0 ? ($lateCount / $totalSchedules) * 100 : 0;
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
            $lateCount,
            $averageRating
        );

        // Cari atau buat penilaian. withTrashed() agar baris yang pernah
        // di-soft-delete ikut ditemukan (mencegah bentrok UNIQUE
        // petugas_id+periode), lalu dipulihkan bila perlu.
        $penilaian = Penilaian::withTrashed()->updateOrCreate(
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

        if ($penilaian->trashed()) {
            $penilaian->restore();
        }

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
     * Konfigurasi per domain: tabel jadwal + apakah punya laporan keterlambatan.
     */
    private function domainConfig(string $reportClass): ?array
    {
        return match ($reportClass) {
            ActivityReport::class => ['jadwal_table' => 'jadwal_kebersihanans', 'has_late' => true],
            LaporanSatpam::class => ['jadwal_table' => 'jadwal_satpam', 'has_late' => true],
            LaporanOb::class => ['jadwal_table' => 'jadwal_ob', 'has_late' => true],
            LaporanToko::class => ['jadwal_table' => 'jadwal_toko', 'has_late' => true],
            default => null,
        };
    }

    /**
     * Update penilaian saat ada approval baru. Mendukung semua domain
     * (kebersihan/satpam/ob/toko) — laporan apa pun yang punya petugas_id,
     * tanggal, status, approved_by.
     */
    public function updatePenilaianAfterApproval(Model $report): ?Penilaian
    {
        if (($report->status ?? null) !== 'approved' || empty($report->approved_by)) {
            return null;
        }

        $config = $this->domainConfig(get_class($report));
        if ($config === null) {
            return null;
        }

        return $this->generateOrUpdateMonthlyPenilaian(
            $report->petugas_id,
            (int) $report->tanggal->month,
            (int) $report->tanggal->year,
            $report->approved_by,
            get_class($report),
            $config['jadwal_table'],
            $config['has_late'],
        );
    }
}
