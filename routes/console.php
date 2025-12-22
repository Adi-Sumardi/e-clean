<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic WhatsApp notifications
Schedule::command('notifications:daily-schedule')->dailyAt('06:00'); // Send today's schedule at 6 AM
Schedule::command('notifications:schedule-reminders')->dailyAt('18:00'); // Reminder for tomorrow's schedule

// Schedule to check missed schedules
// Run after each shift ends
Schedule::command('schedule:check-missed')->dailyAt('08:30'); // Check after Pagi shift (05:00-08:00)
Schedule::command('schedule:check-missed')->dailyAt('14:30'); // Check after Siang shift (10:00-14:00)
Schedule::command('schedule:check-missed')->dailyAt('18:30'); // Check after Sore shift (15:00-18:00)
