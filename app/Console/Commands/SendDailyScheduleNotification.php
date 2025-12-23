<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalKebersihan;
use App\Models\User;
use App\Services\WatZapService;
use Carbon\Carbon;

class SendDailyScheduleNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:daily-schedule {--test : Run in test mode without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily schedule notification to petugas with all their tasks for today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $watzap = new WatZapService();
        $today = Carbon::today();
        $isTest = $this->option('test');

        if (!$watzap->isConfigured() && !$isTest) {
            $this->error('WatZap is not configured. Please set WATZAP_API_KEY and WATZAP_NUMBER_KEY in .env');
            return Command::FAILURE;
        }

        $this->info("Sending daily schedule notifications for {$today->format('d/m/Y')}...");

        // Check for petugas without phone numbers (for logging)
        $petugasWithoutPhone = User::role('petugas')
            ->where('is_active', true)
            ->whereNull('phone')
            ->whereHas('jadwalKebersihan', function ($query) use ($today) {
                $query->whereDate('tanggal', $today)
                    ->where('status', 'active');
            })
            ->get();

        if ($petugasWithoutPhone->isNotEmpty()) {
            $this->warn("Warning: {$petugasWithoutPhone->count()} petugas have schedules but no phone number:");
            foreach ($petugasWithoutPhone as $p) {
                $this->warn("  - {$p->name} (ID: {$p->id})");
            }
            $this->newLine();
        }

        // Get all petugas who have schedules today AND have phone
        $petugasWithSchedules = User::role('petugas')
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->whereHas('jadwalKebersihan', function ($query) use ($today) {
                $query->whereDate('tanggal', $today)
                    ->where('status', 'active');
            })
            ->with(['jadwalKebersihan' => function ($query) use ($today) {
                $query->whereDate('tanggal', $today)
                    ->where('status', 'active')
                    ->with(['lokasi.unit'])
                    ->orderBy('jam_mulai');
            }])
            ->get();

        if ($petugasWithSchedules->isEmpty()) {
            $this->info('No petugas with phone numbers have schedules for today.');
            return Command::SUCCESS;
        }

        $this->info("Found {$petugasWithSchedules->count()} petugas with schedules today.");

        $sent = 0;
        $failed = 0;

        foreach ($petugasWithSchedules as $petugas) {
            $jadwals = $petugas->jadwalKebersihan;

            if ($jadwals->isEmpty()) {
                continue;
            }

            $message = $this->buildDailyScheduleMessage($petugas, $jadwals, $today);

            if ($isTest) {
                $this->line("---");
                $this->info("To: {$petugas->name} ({$petugas->phone})");
                $this->line($message);
                $sent++;
                continue;
            }

            try {
                $result = $watzap->sendMessage(
                    $petugas->phone,
                    $message,
                    [
                        'type' => 'daily_schedule_notification',
                        'petugas_id' => $petugas->id,
                        'date' => $today->toDateString(),
                    ]
                );

                if ($result['success']) {
                    $sent++;
                    $this->info("✓ Sent to {$petugas->name} ({$petugas->phone}) - {$jadwals->count()} jadwal");
                } else {
                    $failed++;
                    $this->error("✗ Failed to send to {$petugas->name}: {$result['message']}");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ Error sending to {$petugas->name}: {$e->getMessage()}");
            }

            // Rate limiting: 0.5 second delay between messages
            usleep(500000);
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Total petugas with phone: {$petugasWithSchedules->count()}");
        $this->info("  Petugas without phone: {$petugasWithoutPhone->count()}");
        $this->info("  Successfully sent: {$sent}");
        $this->info("  Failed: {$failed}");

        return Command::SUCCESS;
    }

    /**
     * Build the daily schedule notification message
     */
    protected function buildDailyScheduleMessage(User $petugas, $jadwals, Carbon $today): string
    {
        $dayName = $this->getIndonesianDayName($today->dayOfWeek);
        $dateFormatted = $today->format('d/m/Y');

        $message = "*JADWAL TUGAS HARI INI*\n";
        $message .= "_{$dayName}, {$dateFormatted}_\n\n";
        $message .= "Selamat pagi {$petugas->name}!\n\n";
        $message .= "Berikut jadwal kebersihan Anda hari ini:\n\n";

        $counter = 1;
        foreach ($jadwals as $jadwal) {
            $lokasi = $jadwal->lokasi;
            $jamMulai = $jadwal->jam_mulai->format('H:i');
            $jamSelesai = $jadwal->jam_selesai->format('H:i');
            $shift = ucfirst($jadwal->shift);

            $message .= "*{$counter}. {$lokasi->nama_lokasi}*\n";
            if ($lokasi->unit) {
                $message .= "   Unit: {$lokasi->unit->nama_unit}\n";
            }
            $message .= "   Kode: {$lokasi->kode_lokasi}\n";
            if ($lokasi->lantai) {
                $message .= "   Lantai: {$lokasi->lantai}\n";
            }
            $message .= "   Shift: {$shift}\n";
            $message .= "   Jam: {$jamMulai} - {$jamSelesai}\n";
            if ($jadwal->prioritas && $jadwal->prioritas !== 'normal') {
                $message .= "   Prioritas: " . ucfirst($jadwal->prioritas) . "\n";
            }
            if ($jadwal->catatan) {
                $message .= "   Catatan: {$jadwal->catatan}\n";
            }
            $message .= "\n";
            $counter++;
        }

        $message .= "---\n";
        $message .= "Total: *{$jadwals->count()} lokasi*\n\n";
        $message .= "Jangan lupa:\n";
        $message .= "- Scan QR Code lokasi\n";
        $message .= "- Foto sebelum & sesudah\n";
        $message .= "- Submit laporan tepat waktu\n\n";
        $message .= "Semangat bekerja!";

        return $message;
    }

    /**
     * Get Indonesian day name
     */
    protected function getIndonesianDayName(int $dayOfWeek): string
    {
        $days = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return $days[$dayOfWeek] ?? '';
    }
}
