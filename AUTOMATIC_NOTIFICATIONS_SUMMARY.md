# Automatic WhatsApp Notifications - Implementation Summary

## Overview

Sistem notifikasi WhatsApp otomatis telah selesai diimplementasikan menggunakan **Observer Pattern** dan **Laravel Task Scheduling** untuk memberikan notifikasi real-time kepada petugas dan supervisor.

---

## ✅ What Has Been Implemented

### 1. Automatic Event-Based Notifications (Observers)

#### JadwalKebersihanObserver
**File:** [app/Observers/JadwalKebersihanObserver.php](app/Observers/JadwalKebersihanObserver.php)

Automatically sends WhatsApp notifications when:
- ✅ **New schedule created** → Petugas receives assignment notification
- ✅ **Schedule updated** (date, time, location changed) → Petugas receives update notification
- ✅ **Schedule deleted/cancelled** → Petugas receives cancellation notification

#### ActivityReportObserver
**File:** [app/Observers/ActivityReportObserver.php](app/Observers/ActivityReportObserver.php)

Automatically sends WhatsApp notifications when:
- ✅ **New report submitted** (pending status) → All supervisors notified
- ✅ **Report approved** → Petugas receives approval notification
- ✅ **Report rejected** → Petugas receives rejection with reason

### 2. Scheduled Daily Reminders (Commands)

#### Schedule Reminders Command
**File:** [app/Console/Commands/SendScheduleReminders.php](app/Console/Commands/SendScheduleReminders.php)
- **Schedule:** Daily at 18:00 (6 PM)
- **Command:** `php artisan notifications:schedule-reminders`
- **Purpose:** Remind petugas about tomorrow's cleaning schedules

#### Attendance Check-in Reminder
**File:** [app/Console/Commands/SendAttendanceReminders.php](app/Console/Commands/SendAttendanceReminders.php)
- **Schedule:** Daily at 07:00 (7 AM)
- **Command:** `php artisan notifications:attendance-reminders morning`
- **Purpose:** Remind petugas to check-in when arriving

#### Attendance Check-out Reminder
**Schedule:** Daily at 16:00 (4 PM)
- **Command:** `php artisan notifications:attendance-reminders evening`
- **Purpose:** Remind petugas to check-out before leaving

### 3. Task Scheduler Registration
**File:** [routes/console.php](routes/console.php)

```php
Schedule::command('notifications:schedule-reminders')->dailyAt('18:00');
Schedule::command('notifications:attendance-reminders morning')->dailyAt('07:00');
Schedule::command('notifications:attendance-reminders evening')->dailyAt('16:00');
```

### 4. Observer Registration
**File:** [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php)

```php
JadwalKebersihan::observe(JadwalKebersihanObserver::class);
ActivityReport::observe(ActivityReportObserver::class);
```

---

## 📋 Files Created/Modified

### New Files
- ✅ [app/Observers/JadwalKebersihanObserver.php](app/Observers/JadwalKebersihanObserver.php)
- ✅ [app/Observers/ActivityReportObserver.php](app/Observers/ActivityReportObserver.php)
- ✅ [app/Console/Commands/SendScheduleReminders.php](app/Console/Commands/SendScheduleReminders.php)
- ✅ [app/Console/Commands/SendAttendanceReminders.php](app/Console/Commands/SendAttendanceReminders.php)
- ✅ [database/migrations/2025_10_21_063003_create_notification_logs_table.php](database/migrations/2025_10_21_063003_create_notification_logs_table.php)
- ✅ [WHATSAPP_NOTIFICATIONS_GUIDE.md](WHATSAPP_NOTIFICATIONS_GUIDE.md) - Complete documentation

### Modified Files
- ✅ [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) - Registered observers
- ✅ [routes/console.php](routes/console.php) - Registered scheduled tasks

---

## 🔄 Notification Flow Diagram

### Event-Based Flow (Real-time)

```
Admin creates new schedule
         ↓
JadwalKebersihan::create()
         ↓
JadwalKebersihanObserver::created()
         ↓
FontteService::sendMessage()
         ↓
WhatsApp notification sent to Petugas
         ↓
NotificationLog created in database
```

### Scheduled Flow (Daily)

```
Cron runs at 18:00
         ↓
php artisan schedule:run
         ↓
notifications:schedule-reminders command
         ↓
Query tomorrow's schedules
         ↓
Loop through each schedule
         ↓
Send WhatsApp to each petugas
         ↓
Log results (sent/failed)
```

---

## 🎯 Notification Types & Templates

| Type | Trigger | Template Method | Recipients |
|------|---------|----------------|------------|
| Schedule Assigned | New schedule created | `scheduleAssigned()` | Assigned petugas |
| Schedule Reminder | 1 day before schedule | `scheduleReminder()` | Assigned petugas |
| Schedule Updated | Schedule modified | Custom message | Assigned petugas |
| Schedule Cancelled | Schedule deleted | Custom message | Assigned petugas |
| Report Submitted | New report created | `reportSubmitted()` | All supervisors |
| Report Approved | Status → approved | `reportApproved()` | Report creator |
| Report Rejected | Status → rejected | `reportRejected()` | Report creator |
| Check-in Reminder | Daily 07:00 | `attendanceReminder()` | All petugas |
| Check-out Reminder | Daily 16:00 | `checkoutReminder()` | All petugas |

---

## 🔧 Configuration Required

### 1. Environment Variables (.env)

```env
# Required
FONNTE_URL=https://api.fonnte.com/send
FONNTE_TOKEN=your_actual_api_token_here

# Optional (with defaults)
NOTIFICATIONS_ENABLED=true
```

### 2. User Phone Numbers

All users who should receive notifications must have phone numbers:

```php
// Via Admin Panel: Users → Edit → Phone field

// Via Database
UPDATE users SET phone = '081234567890' WHERE id = 1;

// Via Tinker
$user = User::find(1);
$user->phone = '081234567890';
$user->save();
```

**Format:** `081234567890` (no +62 prefix, no spaces)

### 3. Laravel Scheduler (Production)

Add to crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

**Development:** Run manually:
```bash
php artisan schedule:work
```

---

## ✅ Testing Checklist

### Setup Tests
- [ ] Fonnte API token configured in .env
- [ ] Phone numbers added to test users (correct format)
- [ ] Test single notification via tinker
- [ ] Verify notification logs in database

### Automatic Notification Tests
- [ ] Create new schedule → Petugas receives WhatsApp
- [ ] Update schedule details → Petugas receives update notification
- [ ] Delete schedule → Petugas receives cancellation
- [ ] Submit activity report → Supervisors receive notification
- [ ] Approve report → Petugas receives approval
- [ ] Reject report → Petugas receives rejection with reason

### Scheduled Command Tests
- [ ] Run `php artisan notifications:schedule-reminders` manually
- [ ] Verify tomorrow's schedules get notifications
- [ ] Run `php artisan notifications:attendance-reminders morning`
- [ ] Run `php artisan notifications:attendance-reminders evening`
- [ ] Check notification logs after each command

### Error Handling Tests
- [ ] Invalid phone number handled gracefully
- [ ] Missing API token shows error (not crash)
- [ ] Failed sends logged to database
- [ ] Rate limiting works (1 sec delay between messages)

---

## 🧪 Testing Commands

### Manual Testing via Tinker

```bash
php artisan tinker
```

```php
// Test basic connection
$fonnte = new App\Services\FontteService();
$fontte->sendMessage('081234567890', 'Test message from E-Cleaning!');

// Test template
$templates = new App\Services\NotificationTemplateService();
$jadwal = App\Models\JadwalKebersihan::first();
$message = $templates->scheduleAssigned($jadwal);
echo $message;

// Test send with template
$fontte->sendMessage($jadwal->petugas->phone, $message);

// Check logs
$logs = App\Models\NotificationLog::latest()->take(5)->get();
foreach($logs as $log) {
    echo "{$log->type} - {$log->status} - {$log->phone_number}\n";
}
```

### Manual Testing Commands

```bash
# Test schedule reminders (tomorrow's schedules)
php artisan notifications:schedule-reminders

# Test morning attendance reminder
php artisan notifications:attendance-reminders morning

# Test evening checkout reminder
php artisan notifications:attendance-reminders evening

# View scheduled tasks
php artisan schedule:list
```

### Trigger Observer Manually

```php
// Via tinker - test schedule assignment notification
$jadwal = new App\Models\JadwalKebersihan([
    'petugas_id' => 2,
    'lokasi_id' => 1,
    'tanggal' => now()->addDay(),
    'jam_mulai' => '08:00',
    'jam_selesai' => '12:00',
]);
$jadwal->save(); // This triggers JadwalKebersihanObserver::created()

// Test report notification
$report = new App\Models\ActivityReport([
    'petugas_id' => 2,
    'lokasi_id' => 1,
    'status' => 'pending',
    // ... other fields
]);
$report->save(); // This triggers ActivityReportObserver::created()
```

---

## 📊 Monitoring & Logs

### Database Logs

All notifications are logged to `notification_logs` table:

```sql
-- View recent notifications
SELECT type, phone_number, status, sent_at
FROM notification_logs
ORDER BY created_at DESC
LIMIT 10;

-- Count by status
SELECT status, COUNT(*) as count
FROM notification_logs
GROUP BY status;

-- Failed notifications
SELECT *
FROM notification_logs
WHERE status = 'failed'
ORDER BY created_at DESC;
```

### Laravel Logs

Check `storage/logs/laravel.log` for:
- Observer execution logs
- Command execution logs
- Error messages
- API responses

```bash
# Follow logs in real-time
tail -f storage/logs/laravel.log

# Search for notification errors
grep "notification" storage/logs/laravel.log
```

---

## 🚨 Troubleshooting

### Notifications Not Sending

**1. Check API Token**
```php
// Via tinker
config('services.fonnte.token');
// Should return your token, not null
```

**2. Check Phone Numbers**
```sql
SELECT id, name, phone FROM users WHERE phone IS NOT NULL;
```

**3. Check Observer Registration**
```bash
# Look for this in AppServiceProvider::boot()
grep "observe" app/Providers/AppServiceProvider.php
```

**4. Manual Test**
```bash
php artisan tinker
>>> App\Services\FontteService::class
>>> (new App\Services\FontteService())->sendMessage('081234567890', 'Test');
```

### Scheduled Commands Not Running

**1. Check Crontab (Production)**
```bash
crontab -l
```

**2. Run Scheduler Manually**
```bash
php artisan schedule:work  # Development
php artisan schedule:run   # Production (one-time)
```

**3. Check Command Registration**
```bash
php artisan schedule:list
```

### Common Errors & Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| "FONNTE_TOKEN not found" | Missing .env config | Add FONNTE_TOKEN to .env |
| "Phone number invalid" | Wrong format | Use 081xxx (not +62 or 62) |
| "Observer not firing" | Not registered | Check AppServiceProvider.php |
| "Schedule not running" | Cron not configured | Add to crontab |
| "Too many requests" | Rate limiting | Check Fonnte plan limits |

---

## 🎉 Summary

### ✅ Completed Features

1. **Automatic Notifications** (Real-time via Observers)
   - New schedule assignment
   - Schedule updates & cancellations
   - Report submissions to supervisors
   - Report approvals/rejections to petugas

2. **Scheduled Notifications** (Daily reminders)
   - Tomorrow's schedule reminders (18:00)
   - Morning check-in reminders (07:00)
   - Evening check-out reminders (16:00)

3. **Complete Documentation**
   - [WHATSAPP_NOTIFICATIONS_GUIDE.md](WHATSAPP_NOTIFICATIONS_GUIDE.md) - Full guide
   - [AUTOMATIC_NOTIFICATIONS_SUMMARY.md](AUTOMATIC_NOTIFICATIONS_SUMMARY.md) - This file

4. **Error Handling & Logging**
   - All notifications logged to database
   - Failed sends logged with error messages
   - Try-catch blocks prevent crashes
   - Rate limiting (1 sec delay)

### 📝 Next Steps for User

1. **Get Fonnte API Token**
   - Register at https://fonnte.com
   - Connect WhatsApp number
   - Copy API token

2. **Configure Application**
   - Add FONNTE_TOKEN to .env
   - Add phone numbers to all users

3. **Test Notifications**
   - Test manual send via tinker
   - Create test schedule (triggers notification)
   - Run schedule reminder command

4. **Setup Scheduler (Production)**
   - Add cron job
   - Monitor notification logs
   - Check Fonnte dashboard for delivery status

### 🔗 Related Documentation

- [PHASE_6_7_SUMMARY.md](PHASE_6_7_SUMMARY.md) - QR Code & WhatsApp setup
- [PHASE_8_9_SUMMARY.md](PHASE_8_9_SUMMARY.md) - GPS & Export features
- [WHATSAPP_NOTIFICATIONS_GUIDE.md](WHATSAPP_NOTIFICATIONS_GUIDE.md) - Complete WhatsApp guide
- [README.md](README.md) - Main documentation

---

**Implementation Date:** October 21, 2025
**Status:** Complete & Ready to Use ✅
**Requires:** Fonnte API Token configuration
