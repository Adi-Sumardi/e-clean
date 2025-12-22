<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalKebersihan;
use App\Services\WatZapService;
use App\Services\NotificationTemplateService;
use Carbon\Carbon;

class SendScheduleReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:schedule-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send schedule reminders to petugas for tomorrow\'s schedules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $watzap = new WatZapService();
        $templates = new NotificationTemplateService();

        if (!$watzap->isConfigured()) {
            $this->error('WatZap is not configured. Please set WATZAP_API_KEY and WATZAP_NUMBER_KEY in .env');
            return Command::FAILURE;
        }

        // Get tomorrow's schedules
        $tomorrow = Carbon::tomorrow()->toDateString();

        // Check schedules without phone
        $jadwalsWithoutPhone = JadwalKebersihan::with(['petugas'])
            ->whereDate('tanggal', $tomorrow)
            ->whereHas('petugas', function ($query) {
                $query->whereNull('phone');
            })
            ->get();

        if ($jadwalsWithoutPhone->isNotEmpty()) {
            $this->warn("Warning: {$jadwalsWithoutPhone->count()} schedules have petugas without phone number:");
            foreach ($jadwalsWithoutPhone as $j) {
                $this->warn("  - {$j->petugas->name} (ID: {$j->petugas->id})");
            }
            $this->newLine();
        }

        // Get schedules with phone
        $jadwals = JadwalKebersihan::with(['petugas', 'lokasi.unit'])
            ->whereDate('tanggal', $tomorrow)
            ->whereHas('petugas', function ($query) {
                $query->whereNotNull('phone')
                    ->where('is_active', true);
            })
            ->get();

        $this->info("Found {$jadwals->count()} schedules with phone for tomorrow ({$tomorrow})");

        $sent = 0;
        $failed = 0;

        foreach ($jadwals as $jadwal) {
            try {
                $message = $templates->scheduleReminder($jadwal);

                $watzap->sendMessage(
                    $jadwal->petugas->phone,
                    $message,
                    [
                        'type' => 'schedule_reminder',
                        'jadwal_id' => $jadwal->id,
                    ]
                );

                $sent++;
                $this->info("✓ Sent reminder to {$jadwal->petugas->name} ({$jadwal->petugas->phone})");
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ Failed to send reminder to {$jadwal->petugas->name}: {$e->getMessage()}");
            }

            // Rate limiting: 1 second delay between messages
            sleep(1);
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Schedules with phone: {$jadwals->count()}");
        $this->info("  Schedules without phone: {$jadwalsWithoutPhone->count()}");
        $this->info("  Successfully sent: {$sent}");
        $this->info("  Failed: {$failed}");

        return Command::SUCCESS;
    }
}
