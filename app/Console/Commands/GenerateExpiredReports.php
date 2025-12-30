<?php

namespace App\Console\Commands;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Setting;
use App\Models\User;
use App\Services\WatZapService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateExpiredReports extends Command
{
    protected $signature = 'reports:generate-expired';

    protected $description = 'Generate expired activity reports for jadwal that exceeded tolerance time without reporting';

    public function handle(): int
    {
        $this->info('Checking for expired jadwal...');

        $toleranceMinutes = Setting::get('reporting_tolerance_minutes', 10);
        $now = Carbon::now();
        $today = $now->toDateString();

        // Get all active jadwal for today that have ended (jam_selesai + tolerance has passed)
        $expiredJadwals = JadwalKebersihan::where('tanggal', $today)
            ->where('status', 'active')
            ->get()
            ->filter(function ($jadwal) use ($now, $toleranceMinutes) {
                // Create datetime from tanggal and jam_selesai
                $jadwalEndTime = Carbon::parse($jadwal->tanggal->format('Y-m-d') . ' ' . $jadwal->jam_selesai->format('H:i:s'));
                $toleranceEndTime = $jadwalEndTime->copy()->addMinutes($toleranceMinutes);

                // Only expired if we've passed the tolerance time
                return $now->greaterThan($toleranceEndTime);
            });

        $generatedCount = 0;
        $skippedCount = 0;
        $supervisorsToNotify = [];

        foreach ($expiredJadwals as $jadwal) {
            // Check if report already exists for this jadwal
            $existingReport = ActivityReport::where('jadwal_id', $jadwal->id)->first();

            if ($existingReport) {
                $skippedCount++;
                continue;
            }

            // Calculate how many minutes late (from jam_selesai)
            $jadwalEndTime = Carbon::parse($jadwal->tanggal->format('Y-m-d') . ' ' . $jadwal->jam_selesai->format('H:i:s'));
            $lateMinutes = (int) abs($now->diffInMinutes($jadwalEndTime));

            // Create auto-generated expired report
            $report = ActivityReport::create([
                'petugas_id' => $jadwal->petugas_id,
                'lokasi_id' => $jadwal->lokasi_id,
                'jadwal_id' => $jadwal->id,
                'tanggal' => $jadwal->tanggal,
                'jam_mulai' => $jadwal->jam_mulai,
                'jam_selesai' => $jadwal->jam_selesai,
                'kegiatan' => '[AUTO] Laporan tidak dibuat oleh petugas',
                'status' => 'submitted',
                'reporting_status' => ActivityReport::REPORTING_STATUS_EXPIRED,
                'is_auto_generated' => true,
                'late_minutes' => $lateMinutes,
                'catatan_petugas' => 'Laporan ini dibuat otomatis oleh sistem karena petugas tidak membuat laporan dalam waktu yang ditentukan.',
            ]);

            $generatedCount++;

            // Collect supervisor info for notification
            $lokasi = $jadwal->lokasi;
            $unit = $lokasi->unit;

            if ($unit) {
                // Get supervisors for this unit
                $unitSupervisors = User::role('supervisor')
                    ->where('is_active', true)
                    ->whereNotNull('phone')
                    ->get();

                foreach ($unitSupervisors as $supervisor) {
                    if (!isset($supervisorsToNotify[$supervisor->id])) {
                        $supervisorsToNotify[$supervisor->id] = [
                            'user' => $supervisor,
                            'reports' => [],
                        ];
                    }
                    $supervisorsToNotify[$supervisor->id]['reports'][] = [
                        'report' => $report,
                        'jadwal' => $jadwal,
                        'petugas' => $jadwal->petugas,
                        'lokasi' => $lokasi,
                    ];
                }
            }

            $this->info("Generated expired report for jadwal #{$jadwal->id} - {$jadwal->petugas->name} at {$jadwal->lokasi->nama_lokasi}");
            Log::info('Generated expired report', [
                'jadwal_id' => $jadwal->id,
                'report_id' => $report->id,
                'petugas_id' => $jadwal->petugas_id,
                'lokasi_id' => $jadwal->lokasi_id,
                'late_minutes' => $lateMinutes,
            ]);
        }

        // Send WhatsApp notifications to supervisors
        $this->sendSupervisorNotifications($supervisorsToNotify);

        $this->info("Completed. Generated: {$generatedCount}, Skipped (already reported): {$skippedCount}");

        return Command::SUCCESS;
    }

    protected function sendSupervisorNotifications(array $supervisorsToNotify): void
    {
        if (empty($supervisorsToNotify)) {
            return;
        }

        $watzapService = new WatZapService();

        if (!$watzapService->isConfigured()) {
            $this->warn('WatZap not configured, skipping notifications');
            return;
        }

        foreach ($supervisorsToNotify as $supervisorData) {
            $supervisor = $supervisorData['user'];
            $reports = $supervisorData['reports'];

            $message = "*PERINGATAN: PETUGAS TIDAK MELAPOR*\n\n"
                . "Ditemukan " . count($reports) . " jadwal yang tidak dilaporkan:\n\n";

            foreach ($reports as $index => $data) {
                $message .= ($index + 1) . ". " . $data['petugas']->name . "\n"
                    . "   Lokasi: " . $data['lokasi']->nama_lokasi . "\n"
                    . "   Jadwal: " . $data['jadwal']->jam_mulai->format('H:i') . " - " . $data['jadwal']->jam_selesai->format('H:i') . "\n\n";
            }

            $message .= "_Laporan otomatis telah dibuat dengan status 'Tidak Lapor'. Silakan review dan berikan penilaian._";

            $result = $watzapService->sendToUser($supervisor, $message, 'expired_report', [
                'report_count' => count($reports),
            ]);

            if ($result['success']) {
                $this->info("Notification sent to supervisor: {$supervisor->name}");
            } else {
                $this->warn("Failed to send notification to {$supervisor->name}: {$result['message']}");
            }

            // Small delay between notifications
            usleep(300000);
        }
    }
}
