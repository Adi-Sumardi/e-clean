<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\FontteService;
use App\Services\NotificationTemplateService;

class SendAttendanceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:attendance-reminders {type=morning : morning or evening}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send attendance check-in/check-out reminders to petugas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fontte = new FontteService();
        $templates = new NotificationTemplateService();

        $type = $this->argument('type');

        if (!in_array($type, ['morning', 'evening'])) {
            $this->error('Type must be either "morning" or "evening"');
            return Command::FAILURE;
        }

        // Get all active petugas with phone numbers
        $petugasList = User::role('petugas')
            ->whereNotNull('phone')
            ->get();

        $this->info("Sending {$type} attendance reminders to {$petugasList->count()} petugas...");

        $sent = 0;
        $failed = 0;

        foreach ($petugasList as $petugas) {
            try {
                $message = $type === 'morning'
                    ? $templates->attendanceReminder($petugas)
                    : $templates->checkoutReminder($petugas);

                $fontte->sendMessage(
                    $petugas->phone,
                    $message,
                    [
                        'type' => $type === 'morning' ? 'attendance_checkin_reminder' : 'attendance_checkout_reminder',
                        'petugas_id' => $petugas->id,
                    ]
                );

                $sent++;
                $this->info("✓ Sent {$type} reminder to {$petugas->name} ({$petugas->phone})");
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ Failed to send reminder to {$petugas->name}: {$e->getMessage()}");
            }

            // Rate limiting: 1 second delay between messages
            sleep(1);
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Total petugas: {$petugasList->count()}");
        $this->info("  Successfully sent: {$sent}");
        $this->info("  Failed: {$failed}");

        return Command::SUCCESS;
    }
}
