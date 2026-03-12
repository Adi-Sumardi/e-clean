<?php

namespace App\Services;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\LaporanKeterlambatan;
use App\Models\Penilaian;
use App\Models\User;

class NotificationTemplateService
{
    /**
     * Template for new schedule assignment
     *
     * @param JadwalKebersihan $jadwal
     * @return string
     */
    public function scheduleAssigned(JadwalKebersihan $jadwal): string
    {
        $unitInfo = $jadwal->lokasi->unit ? "🏢 Unit: {$jadwal->lokasi->unit->nama_unit}\n" : "";

        return "📅 *JADWAL KEBERSIHAN BARU*\n\n" .
            "Halo {$jadwal->petugas->name},\n\n" .
            "Anda mendapat jadwal kebersihan baru:\n\n" .
            $unitInfo .
            "📍 Lokasi: {$jadwal->lokasi->nama_lokasi}\n" .
            "📆 Tanggal: {$jadwal->tanggal->format('d/m/Y')}\n" .
            "⏰ Shift: " . ucfirst($jadwal->shift) . "\n" .
            "🕐 Waktu: {$jadwal->jam_mulai->format('H:i')} - {$jadwal->jam_selesai->format('H:i')}\n" .
            "⚠️ Prioritas: " . ucfirst($jadwal->prioritas) . "\n\n" .
            ($jadwal->catatan ? "📝 Catatan: {$jadwal->catatan}\n\n" : "") .
            "Silakan login ke sistem untuk melihat detail lengkap.\n\n" .
            "Terima kasih! 🙏";
    }

    /**
     * Template for schedule reminder (1 day before)
     *
     * @param JadwalKebersihan $jadwal
     * @return string
     */
    public function scheduleReminder(JadwalKebersihan $jadwal): string
    {
        $unitInfo = $jadwal->lokasi->unit ? "🏢 Unit: {$jadwal->lokasi->unit->nama_unit}\n" : "";

        return "🔔 *PENGINGAT JADWAL BESOK*\n\n" .
            "Halo {$jadwal->petugas->name},\n\n" .
            "Pengingat: Anda memiliki jadwal kebersihan besok:\n\n" .
            $unitInfo .
            "📍 Lokasi: {$jadwal->lokasi->nama_lokasi}\n" .
            "⏰ Shift: {$jadwal->shift} ({$jadwal->jam_mulai->format('H:i')} - {$jadwal->jam_selesai->format('H:i')})\n\n" .
            "Jangan lupa untuk:\n" .
            "✅ Scan QR Code lokasi\n" .
            "✅ Foto sebelum & sesudah pembersihan\n" .
            "✅ Submit laporan kegiatan tepat waktu\n\n" .
            "Terima kasih! 🙏";
    }

    /**
     * Template for activity report submitted
     *
     * @param ActivityReport $report
     * @param User $supervisor
     * @return string
     */
    public function reportSubmitted(ActivityReport $report, User $supervisor): string
    {
        $unitInfo = $report->lokasi->unit ? "🏢 Unit: {$report->lokasi->unit->nama_unit}\n" : "";

        return "📄 *LAPORAN KEGIATAN BARU*\n\n" .
            "Halo {$supervisor->name},\n\n" .
            "Ada laporan kegiatan baru yang perlu direview:\n\n" .
            "👤 Petugas: {$report->petugas->name}\n" .
            $unitInfo .
            "📍 Lokasi: {$report->lokasi->nama_lokasi}\n" .
            "📅 Tanggal: {$report->tanggal->format('d/m/Y')}\n" .
            "⏰ Waktu: {$report->jam_mulai->format('H:i')} - " .
            ($report->jam_selesai ? $report->jam_selesai->format('H:i') : 'belum selesai') . "\n\n" .
            "📝 Kegiatan: {$report->kegiatan}\n\n" .
            "Silakan login untuk review dan approve laporan ini.\n\n" .
            "Terima kasih! 🙏";
    }

    /**
     * Template for activity report approved
     *
     * @param ActivityReport $report
     * @return string
     */
    public function reportApproved(ActivityReport $report): string
    {
        $unitInfo = $report->lokasi->unit ? "🏢 Unit: {$report->lokasi->unit->nama_unit}\n" : "";

        return "✅ *LAPORAN DISETUJUI*\n\n" .
            "Halo {$report->petugas->name},\n\n" .
            "Laporan kegiatan Anda telah disetujui:\n\n" .
            $unitInfo .
            "📍 Lokasi: {$report->lokasi->nama_lokasi}\n" .
            "📅 Tanggal: {$report->tanggal->format('d/m/Y')}\n" .
            ($report->rating ? "⭐ Rating: {$report->rating}/5\n" : "") .
            "\n" .
            ($report->catatan_supervisor ?
                "💬 Catatan Supervisor:\n\"{$report->catatan_supervisor}\"\n\n" : "") .
            "Terima kasih atas pekerjaan yang baik! 👍\n\n" .
            "Terus pertahankan kualitas kerja Anda! 💪";
    }

    /**
     * Template for activity report rejected
     *
     * @param ActivityReport $report
     * @return string
     */
    public function reportRejected(ActivityReport $report): string
    {
        $unitInfo = $report->lokasi->unit ? "🏢 Unit: {$report->lokasi->unit->nama_unit}\n" : "";

        return "❌ *LAPORAN DITOLAK*\n\n" .
            "Halo {$report->petugas->name},\n\n" .
            "Laporan kegiatan Anda ditolak dan perlu diperbaiki:\n\n" .
            $unitInfo .
            "📍 Lokasi: {$report->lokasi->nama_lokasi}\n" .
            "📅 Tanggal: {$report->tanggal->format('d/m/Y')}\n\n" .
            "❗ Alasan Penolakan:\n\"{$report->rejected_reason}\"\n\n" .
            "Silakan perbaiki laporan dan submit kembali.\n\n" .
            "Terima kasih! 🙏";
    }

    /**
     * Template for morning work reminder
     *
     * @param User $petugas
     * @return string
     */
    public function morningWorkReminder(User $petugas): string
    {
        return "🔔 *PENGINGAT TUGAS HARI INI*\n\n" .
            "Selamat pagi {$petugas->name}!\n\n" .
            "Cek jadwal kebersihan Anda hari ini di aplikasi E-Cleaning.\n\n" .
            "📋 Yang perlu dilakukan:\n" .
            "1. Buka aplikasi E-Cleaning\n" .
            "2. Cek jadwal dan lokasi\n" .
            "3. Pastikan GPS aktif untuk validasi lokasi\n\n" .
            "Semangat bekerja! 💪";
    }

    /**
     * Template for shift end reminder
     *
     * @param User $petugas
     * @return string
     */
    public function shiftEndReminder(User $petugas): string
    {
        return "🔔 *PENGINGAT AKHIR SHIFT*\n\n" .
            "Halo {$petugas->name}!\n\n" .
            "Shift Anda hampir selesai. Jangan lupa untuk:\n\n" .
            "✅ Selesaikan pembersihan\n" .
            "✅ Foto hasil akhir\n" .
            "✅ Pastikan semua laporan sudah disubmit\n\n" .
            "Terima kasih atas kerja keras Anda hari ini! 🙏";
    }

    /**
     * Template for evaluation/penilaian given
     *
     * @param Penilaian $penilaian
     * @return string
     */
    public function evaluationGiven(Penilaian $penilaian): string
    {
        $avgRating = $penilaian->rata_rata;
        $stars = str_repeat('⭐', (int)round((float)$avgRating));

        $bulanNama = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $periode = ($bulanNama[$penilaian->periode_bulan] ?? '') . ' ' . $penilaian->periode_tahun;

        return "📊 *PENILAIAN KINERJA*\n\n" .
            "Halo {$penilaian->petugas->name},\n\n" .
            "Anda telah mendapat penilaian untuk periode:\n" .
            "{$periode}\n\n" .
            "📈 Hasil Penilaian:\n" .
            "• Kebersihan: {$penilaian->skor_kebersihan}/5\n" .
            "• Kualitas: {$penilaian->skor_kualitas}/5\n" .
            "• Ketepatan Waktu: {$penilaian->skor_ketepatan_waktu}/5\n\n" .
            "⭐ *Rating Rata-rata: {$avgRating}/5* {$stars}\n" .
            "📋 Kategori: {$penilaian->kategori}\n\n" .
            ($penilaian->catatan ? "💬 Catatan:\n\"{$penilaian->catatan}\"\n\n" : "") .
            "Terus tingkatkan kinerja Anda! 💪\n\n" .
            "Terima kasih! 🙏";
    }

    /**
     * Template for weekly performance summary
     *
     * @param User $petugas
     * @param array $stats - ['reports' => int, 'approved' => int, 'avg_rating' => float, 'attendance' => int]
     * @return string
     */
    public function weeklyPerformanceSummary(User $petugas, array $stats): string
    {
        return "📊 *RINGKASAN KINERJA MINGGUAN*\n\n" .
            "Halo {$petugas->name}!\n\n" .
            "Berikut ringkasan kinerja Anda minggu ini:\n\n" .
            "📄 Laporan Dibuat: {$stats['reports']}\n" .
            "✅ Laporan Disetujui: {$stats['approved']}\n" .
            "⭐ Rating Rata-rata: " . number_format($stats['avg_rating'], 1) . "/5\n" .
            "📋 Kehadiran: {$stats['attendance']} hari\n\n" .
            ($stats['avg_rating'] >= 4 ?
                "🎉 Kerja bagus! Pertahankan kinerja Anda!" :
                "💪 Terus tingkatkan kinerja Anda!") .
            "\n\nTerima kasih! 🙏";
    }

    /**
     * Template for late submission warning
     *
     * @param LaporanKeterlambatan $laporan
     * @return string
     */
    public function lateSubmissionWarning(LaporanKeterlambatan $laporan): string
    {
        return "⚠️ *PERINGATAN KETERLAMBATAN*\n\n" .
            "Halo {$laporan->petugas->name},\n\n" .
            "Anda tercatat terlambat menyelesaikan tugas:\n\n" .
            "📅 Tanggal: {$laporan->tanggal->format('d/m/Y')}\n" .
            "📍 Lokasi: {$laporan->lokasi->nama_lokasi}\n" .
            "⏰ Shift: " . ucfirst($laporan->shift) . "\n" .
            "⏱️ Status: " . ucwords(str_replace('_', ' ', $laporan->status)) . "\n\n" .
            "Harap lebih tepat waktu untuk kedepannya.\n\n" .
            "Terima kasih! 🙏";
    }
}
