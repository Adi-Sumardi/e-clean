# WhatsApp Notifications - Complete Guide

## Overview

Sistem notifikasi WhatsApp otomatis menggunakan Fonnte API untuk memberitahu petugas dan supervisor tentang berbagai event dalam aplikasi.

---

## Setup & Configuration

### 1. Get Fonnte API Token

1. Register di [https://fonnte.com](https://fonnte.com)
2. Verifikasi akun Anda
3. Connect nomor WhatsApp Anda ke Fonnte
4. Copy API Token dari dashboard

### 2. Configure Environment

Edit `.env` file:

```env
FONNTE_URL=https://api.fonnte.com/send
FONNTE_TOKEN=your_actual_api_token_here
```

### 3. Add Phone Numbers to Users

Pastikan semua user yang ingin menerima notifikasi memiliki nomor telepon:

**Via Admin Panel:**
1. Login sebagai Super Admin
2. Go to Users
3. Edit user
4. Add phone number (format: `081234567890` tanpa +62)
5. Save

**Via Database:**
```sql
UPDATE users SET phone = '081234567890' WHERE email = 'user@example.com';
```

**Via Tinker:**
```php
php artisan tinker

$user = User::find(1);
$user->phone = '081234567890';
$user->save();
```

---

## Automatic Notifications

### Notification Triggers

Sistem secara otomatis mengirim notifikasi saat event tertentu terjadi melalui **Observers**.

#### 1. Jadwal Kebersihan Notifications

**File:** [app/Observers/JadwalKebersihanObserver.php](app/Observers/JadwalKebersihanObserver.php)

| Event | Trigger | Recipients | Message Template |
|-------|---------|------------|------------------|
| **Created** | Jadwal baru dibuat | Petugas yang ditugaskan | `scheduleAssigned()` |
| **Updated** | Jadwal diubah (tanggal, waktu, lokasi) | Petugas yang ditugaskan | Custom "Jadwal Diubah" |
| **Deleted** | Jadwal dibatalkan | Petugas yang ditugaskan | Custom "Jadwal Dibatalkan" |

**Example Flow:**
```
Admin creates new schedule
  ↓
JadwalKebersihanObserver::created()
  ↓
Automatic WhatsApp notification sent to assigned petugas
  ↓
Notification logged to database
```

#### 2. Activity Report Notifications

**File:** [app/Observers/ActivityReportObserver.php](app/Observers/ActivityReportObserver.php)

| Event | Trigger | Recipients | Message Template |
|-------|---------|------------|------------------|
| **Created** | Laporan baru (status: pending) | All supervisors | `reportSubmitted()` |
| **Updated** | Status changed to "approved" | Petugas yang submit | `reportApproved()` |
| **Updated** | Status changed to "rejected" | Petugas yang submit | `reportRejected()` |

**Example Flow:**
```
Petugas submits activity report
  ↓
ActivityReportObserver::created()
  ↓
Automatic WhatsApp sent to all supervisors
  ↓

Supervisor approves report
  ↓
ActivityReportObserver::updated()
  ↓
Automatic WhatsApp sent to petugas (approved message)
```

---

## Scheduled Notifications

### Daily Reminders

Sistem menjalankan scheduled commands untuk reminder harian.

#### 1. Schedule Reminders (Tomorrow's Schedule)

**Command:** `notifications:schedule-reminders`
**Schedule:** Daily at 18:00 (6 PM)
**File:** [app/Console/Commands/SendScheduleReminders.php](app/Console/Commands/SendScheduleReminders.php)

**What it does:**
- Checks tomorrow's schedules
- Sends reminder to each petugas with schedule
- Uses template: `scheduleReminder()`

**Manual execution:**
```bash
php artisan notifications:schedule-reminders
```

#### 2. Attendance Check-in Reminder

**Command:** `notifications:attendance-reminders morning`
**Schedule:** Daily at 07:00 (7 AM)
**File:** [app/Console/Commands/SendAttendanceReminders.php](app/Console/Commands/SendAttendanceReminders.php)

**What it does:**
- Sends reminder to all petugas with phone number
- Reminds them to check-in
- Uses template: `attendanceReminder()`

**Manual execution:**
```bash
php artisan notifications:attendance-reminders morning
```

#### 3. Attendance Check-out Reminder

**Command:** `notifications:attendance-reminders evening`
**Schedule:** Daily at 16:00 (4 PM)

**What it does:**
- Sends reminder to all petugas
- Reminds them to check-out before going home
- Uses template: `checkoutReminder()`

**Manual execution:**
```bash
php artisan notifications:attendance-reminders evening
```

### Schedule Configuration

**File:** [routes/console.php](routes/console.php)

```php
Schedule::command('notifications:schedule-reminders')->dailyAt('18:00');
Schedule::command('notifications:attendance-reminders morning')->dailyAt('07:00');
Schedule::command('notifications:attendance-reminders evening')->dailyAt('16:00');
```

**To run scheduler:**
```bash
# In development (run in background)
php artisan schedule:work

# In production (add to crontab)
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Notification Templates

### Available Templates

**File:** [app/Services/NotificationTemplateService.php](app/Services/NotificationTemplateService.php)

| Template Method | Usage | Parameters |
|----------------|-------|------------|
| `scheduleAssigned()` | New schedule assigned | `JadwalKebersihan $jadwal` |
| `scheduleReminder()` | Reminder 1 day before | `JadwalKebersihan $jadwal` |
| `reportSubmitted()` | New report notification | `ActivityReport $report` |
| `reportApproved()` | Report approved | `ActivityReport $report` |
| `reportRejected()` | Report rejected | `ActivityReport $report` |
| `attendanceReminder()` | Morning check-in reminder | `User $petugas` |
| `checkoutReminder()` | Evening check-out reminder | `User $petugas` |
| `evaluationGiven()` | Performance evaluation | `Penilaian $penilaian` |
| `weeklyPerformanceSummary()` | Weekly stats | `User $petugas, array $stats` |
| `lateAttendanceWarning()` | Late attendance warning | `Presensi $presensi` |

### Template Example

```php
public function scheduleAssigned(JadwalKebersihan $jadwal): string
{
    return "📅 *JADWAL KEBERSIHAN BARU*\n\n" .
           "Halo {$jadwal->petugas->name},\n\n" .
           "Anda mendapat jadwal kebersihan baru:\n\n" .
           "📍 Lokasi: {$jadwal->lokasi->nama_lokasi}\n" .
           "📆 Tanggal: {$jadwal->tanggal->format('d/m/Y')}\n" .
           "⏰ Waktu: {$jadwal->jam_mulai->format('H:i')} - {$jadwal->jam_selesai->format('H:i')}\n\n" .
           "Silakan cek jadwal Anda di aplikasi.\n\n" .
           "Terima kasih! 🙏";
}
```

---

## Manual Notification Sending

### Using FontteService Directly

```php
use App\Services\FontteService;
use App\Services\NotificationTemplateService;

$fontte = new FontteService();
$templates = new NotificationTemplateService();

// Send single message
$jadwal = JadwalKebersihan::find(1);
$message = $templates->scheduleAssigned($jadwal);
$fontte->sendMessage($jadwal->petugas->phone, $message);

// Send custom message
$fontte->sendMessage('081234567890', 'Custom WhatsApp message here');

// Send bulk messages
$messages = [
    ['phone' => '081234567890', 'message' => 'Message 1'],
    ['phone' => '081298765432', 'message' => 'Message 2'],
];
$fontte->sendBulkMessages($messages);
```

### Via Tinker (Testing)

```bash
php artisan tinker
```

```php
// Test single message
$fontte = new App\Services\FontteService();
$fontte->sendMessage('081234567890', 'Test message from E-Cleaning!');

// Test with template
$templates = new App\Services\NotificationTemplateService();
$jadwal = App\Models\JadwalKebersihan::first();
$message = $templates->scheduleAssigned($jadwal);
$fontte->sendMessage($jadwal->petugas->phone, $message);

// Test schedule reminder command
Artisan::call('notifications:schedule-reminders');
echo Artisan::output();

// Test attendance reminder
Artisan::call('notifications:attendance-reminders morning');
echo Artisan::output();
```

---

## Notification Logs

### Database Table

**Table:** `notification_logs`

**Columns:**
- `id` - Auto increment
- `user_id` - User yang menerima (nullable)
- `phone_number` - Nomor HP tujuan
- `type` - Jenis notifikasi (schedule_assigned, report_approved, etc)
- `message` - Isi pesan
- `metadata` - Data tambahan (JSON)
- `status` - pending, sent, failed
- `response` - Response dari Fonnte API
- `sent_at` - Waktu terkirim
- `created_at` - Waktu dibuat
- `updated_at` - Waktu update

### View Logs

**Via Admin Panel:**
(Implement Filament resource for NotificationLog if needed)

**Via Database:**
```sql
SELECT * FROM notification_logs ORDER BY created_at DESC LIMIT 10;
```

**Via Tinker:**
```php
$logs = App\Models\NotificationLog::latest()->take(10)->get();
foreach($logs as $log) {
    echo "{$log->created_at} - {$log->type} - {$log->phone_number} - {$log->status}\n";
}
```

### Check Failed Notifications

```php
$failed = App\Models\NotificationLog::where('status', 'failed')->get();
```

---

## Testing Checklist

### Basic Setup
- [ ] Fonnte API token configured in .env
- [ ] Test API connection via tinker
- [ ] Phone numbers added to test users
- [ ] Phone number format correct (081xxx, not +62)

### Automatic Notifications
- [ ] Create new schedule → Petugas receives WhatsApp
- [ ] Update schedule → Petugas receives update notification
- [ ] Delete schedule → Petugas receives cancellation notification
- [ ] Submit new report → Supervisors receive notification
- [ ] Approve report → Petugas receives approval notification
- [ ] Reject report → Petugas receives rejection notification

### Scheduled Reminders
- [ ] Run schedule reminder command manually
- [ ] Check tomorrow's schedules get reminders
- [ ] Run morning attendance reminder
- [ ] Run evening checkout reminder
- [ ] Verify cron job configured in production

### Notification Logs
- [ ] Notifications logged to database
- [ ] Status correctly updated (sent/failed)
- [ ] Response from Fonnte API saved
- [ ] Can query logs by type/user/status

### Error Handling
- [ ] Invalid phone number handled gracefully
- [ ] Missing API token shows clear error
- [ ] Failed send logged with error message
- [ ] Rate limiting works (1 second delay between messages)

---

## Troubleshooting

### Notifications Not Sending

**Check 1: API Token**
```php
// Via tinker
config('services.fonnte.token');
// Should return your actual token, not null
```

**Check 2: Phone Number Format**
```php
$user = User::find(1);
echo $user->phone; // Should be 081234567890 (not +62 or 62)
```

**Check 3: Observer Registered**
```php
// Check AppServiceProvider.php boot() method
JadwalKebersihan::observe(JadwalKebersihanObserver::class);
ActivityReport::observe(ActivityReportObserver::class);
```

**Check 4: Fonnte API Status**
- Visit Fonnte dashboard
- Check API token status (active/suspended)
- Check WhatsApp connection status
- Check credit balance

### Scheduled Commands Not Running

**Check 1: Scheduler Running**
```bash
# Development
php artisan schedule:work

# Production - check crontab
crontab -l
```

**Check 2: Manual Test**
```bash
php artisan notifications:schedule-reminders
php artisan notifications:attendance-reminders morning
```

**Check 3: Laravel Logs**
```bash
tail -f storage/logs/laravel.log
```

### Rate Limiting Issues

If sending to many users:

```php
// Adjust sleep time in commands
sleep(2); // 2 seconds instead of 1

// Or use queued jobs
Mail::queue(...)
```

---

## Customization Guide

### Add New Notification Template

1. **Add method to NotificationTemplateService:**

```php
// app/Services/NotificationTemplateService.php

public function customNotification($data): string
{
    return "🔔 *CUSTOM NOTIFICATION*\n\n" .
           "Your custom message here...\n\n" .
           "Data: {$data}\n\n" .
           "Terima kasih! 🙏";
}
```

2. **Use in Observer or Command:**

```php
$templates = new NotificationTemplateService();
$message = $templates->customNotification($yourData);
$fontte->sendMessage($phone, $message);
```

### Add New Scheduled Command

1. **Create command:**
```bash
php artisan make:command SendWeeklyReport
```

2. **Implement handle() method:**
```php
public function handle()
{
    $fontte = new FontteService();
    // Your logic here
}
```

3. **Register in routes/console.php:**
```php
Schedule::command('your:command')->weekly();
```

### Disable Automatic Notifications

**Temporarily disable all:**

Comment out in `AppServiceProvider.php`:
```php
// JadwalKebersihan::observe(JadwalKebersihanObserver::class);
// ActivityReport::observe(ActivityReportObserver::class);
```

**Disable specific notification:**

In Observer, wrap in condition:
```php
if (config('app.notifications_enabled', true)) {
    $fontte->sendMessage(...);
}
```

Add to `.env`:
```env
NOTIFICATIONS_ENABLED=false
```

---

## Production Checklist

- [ ] Fonnte API token configured and active
- [ ] Cron job configured for scheduler
- [ ] All users have valid phone numbers
- [ ] Notification logs monitored regularly
- [ ] Error handling tested
- [ ] Rate limiting configured
- [ ] Backup WhatsApp number connected to Fonnte
- [ ] Credit balance monitored
- [ ] Log rotation configured

---

## API Rate Limits

**Fonnte Free Plan:**
- 500 messages/month
- 1 message/second recommended

**Fonnte Paid Plans:**
- Higher limits based on plan
- Check [https://fonnte.com/pricing](https://fonnte.com/pricing)

**Our Implementation:**
- 1 second delay between messages (sleep(1))
- Bulk sending with rate limiting
- Failed messages logged for retry

---

## Support & Resources

**Fonnte Documentation:**
- API Docs: [https://docs.fonnte.com](https://docs.fonnte.com)
- Dashboard: [https://fonnte.com/dashboard](https://fonnte.com/dashboard)
- Support: [https://fonnte.com/support](https://fonnte.com/support)

**Application Files:**
- FontteService: [app/Services/FontteService.php](app/Services/FontteService.php)
- Templates: [app/Services/NotificationTemplateService.php](app/Services/NotificationTemplateService.php)
- Observers: [app/Observers/](app/Observers/)
- Commands: [app/Console/Commands/](app/Console/Commands/)
- Schedule: [routes/console.php](routes/console.php)

---

**Last Updated:** October 21, 2025
**Version:** 1.0
**Status:** Production Ready ✅
