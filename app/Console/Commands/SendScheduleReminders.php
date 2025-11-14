<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalKebersihan;
use App\Services\FontteService;
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
        $fontte = new FontteService();
        $templates = new NotificationTemplateService();

        // Get tomorrow's schedules
        $tomorrow = Carbon::tomorrow()->toDateString();

        $jadwals = JadwalKebersihan::with(['petugas', 'lokasi'])
            ->whereDate('tanggal', $tomorrow)
            ->whereHas('petugas', function ($query) {
                $query->whereNotNull('phone');
            })
            ->get();

        $this->info("Found {$jadwals->count()} schedules for tomorrow ({$tomorrow})");

        $sent = 0;
        $failed = 0;

        foreach ($jadwals as $jadwal) {
            try {
                $message = $templates->scheduleReminder($jadwal);

                $fontte->sendMessage(
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
        $this->info("  Total schedules: {$jadwals->count()}");
        $this->info("  Successfully sent: {$sent}");
        $this->info("  Failed: {$failed}");

        return Command::SUCCESS;
    }
}
