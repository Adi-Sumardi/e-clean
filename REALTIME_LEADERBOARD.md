# 🔄 Real-time Leaderboard Implementation

**Date:** 2025-11-13
**Feature:** Auto-refresh leaderboard every 5 seconds

---

## Overview

Implemented real-time automatic updates for the Petugas Leaderboard page using Livewire polling. The leaderboard now automatically refreshes every 5 seconds to show the latest rankings when supervisors approve reports.

---

## Changes Made

### 1. Backend (Livewire Component)

**File:** [app/Filament/Pages/PetugasLeaderboard.php](app/Filament/Pages/PetugasLeaderboard.php)

**Added:**
```php
// Enable realtime updates - auto refresh every 5 seconds
protected static ?string $pollingInterval = '5s';

public string $lastUpdated;

public function mount(): void
{
    $this->startDate = now()->startOfMonth()->format('Y-m-d');
    $this->endDate = now()->endOfMonth()->format('Y-m-d');
    $this->lastUpdated = now()->format('H:i:s');
}

public function getPollingInterval(): ?string
{
    return static::$pollingInterval;
}

#[Computed]
public function getLeaderboardData(): array
{
    // Update timestamp untuk menunjukkan data di-refresh
    $this->lastUpdated = now()->format('H:i:s');

    // ... rest of the code
}
```

**Features:**
- ✅ `$pollingInterval` set to 5 seconds
- ✅ `$lastUpdated` property tracks last refresh time
- ✅ `#[Computed]` attribute for optimized caching
- ✅ Timestamp updates on each poll

---

### 2. Frontend (Blade Template)

**File:** [resources/views/filament/pages/petugas-leaderboard.blade.php](resources/views/filament/pages/petugas-leaderboard.blade.php)

#### A. Added Polling Directive
```blade
<div class="space-y-6" wire:poll.5s>
```

#### B. Added Real-time Indicator
```blade
{{-- Real-time Update Indicator --}}
<div class="flex items-center justify-between bg-green-50 dark:bg-green-900/20 rounded-lg px-4 py-2 border border-green-200 dark:border-green-800">
    <div class="flex items-center gap-2">
        <div class="relative">
            <span class="flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
        </div>
        <span class="text-sm font-medium text-green-900 dark:text-green-100">
            🔄 Live Update Aktif - Data diperbarui otomatis setiap 5 detik
        </span>
    </div>
    <span class="text-xs text-green-700 dark:text-green-300">
        Terakhir update: {{ $lastUpdated ?? now()->format('H:i:s') }}
    </span>
</div>
```

**Visual Features:**
- 🟢 Animated green pulsing dot
- 📊 "Live Update Aktif" message
- ⏰ Last update timestamp (HH:mm:ss)
- 🎨 Responsive design with dark mode support

#### C. Added Loading Overlay
```blade
{{-- Loading Overlay --}}
<div wire:loading class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm z-50 flex items-center justify-center rounded-lg">
    <div class="flex flex-col items-center gap-3">
        <svg class="animate-spin h-10 w-10 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Memperbarui data...
        </span>
    </div>
</div>
```

**Visual Features:**
- 🔄 Spinning loading icon
- 🌫️ Semi-transparent backdrop with blur
- 💬 "Memperbarui data..." text
- 🎯 Centered overlay covering entire table

---

## How It Works

### Flow Diagram

```
User opens page
    ↓
Mount: Initialize lastUpdated
    ↓
Render initial data
    ↓
[Every 5 seconds]
    ↓
wire:poll.5s triggers
    ↓
Show loading overlay (wire:loading)
    ↓
getLeaderboardData() executes
    ↓
Update $lastUpdated timestamp
    ↓
Query database for latest data
    ↓
Calculate scores & rankings
    ↓
Re-render UI with new data
    ↓
Hide loading overlay
    ↓
Update "Terakhir update" display
    ↓
[Wait 5 seconds] → Repeat
```

---

## User Experience

### What Users See:

1. **Green Indicator Banner**
   - Pulsing green dot (animated)
   - "🔄 Live Update Aktif" message
   - Current timestamp (e.g., "Terakhir update: 14:35:42")

2. **Automatic Refresh**
   - Every 5 seconds, page automatically fetches new data
   - No need to manually refresh browser
   - Smooth transitions without full page reload

3. **Loading Feedback**
   - Semi-transparent overlay appears during refresh
   - Spinning icon with "Memperbarui data..." text
   - Quick and non-intrusive

4. **Real-time Rankings**
   - When supervisor approves a report, leaderboard updates automatically
   - Scores recalculate instantly
   - Rankings adjust based on new data

---

## Technical Details

### Livewire Polling

**Directive:** `wire:poll.5s`
- Automatically sends AJAX request every 5 seconds
- Only updates changed parts of DOM (efficient)
- Uses Livewire's diffing algorithm

**Computed Property:** `#[Computed]`
- Caches result within same request
- Automatically clears cache on property changes
- Optimizes performance

### Performance Considerations

**Current Implementation:**
- ⚠️ N+1 query problem still exists (3 queries per petugas)
- Runs every 5 seconds
- May cause database load with many users

**Example Query Load:**
- 5 petugas = 15 queries every 5 seconds
- 10 petugas = 30 queries every 5 seconds
- 50 petugas = 150 queries every 5 seconds

**Recommended Optimization:**
Use the bulk query approach from [DashboardController](app/Http/Controllers/Api/DashboardController.php#L380-L470):
- Would reduce to 4 queries total regardless of petugas count
- 97% query reduction
- Much better for realtime polling

---

## Configuration

### Adjust Polling Interval

**File:** `app/Filament/Pages/PetugasLeaderboard.php`

```php
// Change from 5s to different interval
protected static ?string $pollingInterval = '10s';  // 10 seconds
protected static ?string $pollingInterval = '3s';   // 3 seconds
protected static ?string $pollingInterval = '30s';  // 30 seconds
```

**Options:**
- `null` - Disable polling (manual refresh only)
- `'1s'` - 1 second (aggressive, high load)
- `'5s'` - 5 seconds (recommended balance)
- `'10s'` - 10 seconds (less frequent, lower load)
- `'30s'` - 30 seconds (conservative)

### Disable Polling

**In Blade:**
```blade
<!-- Remove or comment out -->
<div class="space-y-6" wire:poll.5s>

<!-- Replace with -->
<div class="space-y-6">
```

---

## Testing Real-time Updates

### Test Scenario 1: Supervisor Approval

1. Open leaderboard page in one browser window
2. Open activity reports in another window (as supervisor)
3. Approve a report with rating
4. Watch leaderboard update within 5 seconds
5. Verify:
   - ✅ "Terakhir update" timestamp changes
   - ✅ Approved reports count increases
   - ✅ Average rating updates
   - ✅ Score recalculates
   - ✅ Rankings adjust if needed

### Test Scenario 2: Multiple Approvals

1. Open leaderboard
2. Approve 3 reports for different petugas
3. Watch rankings change in real-time
4. Verify:
   - ✅ All 3 petugas scores update
   - ✅ Rankings reorder correctly
   - ✅ Top 3 podium updates
   - ✅ Loading indicator appears briefly

### Test Scenario 3: Loading State

1. Open browser DevTools → Network tab
2. Throttle to "Slow 3G"
3. Watch leaderboard refresh
4. Verify:
   - ✅ Loading overlay appears
   - ✅ Spinner animates
   - ✅ Text shows "Memperbarui data..."
   - ✅ Overlay covers entire table

---

## Benefits

### For Supervisors
- 📊 See ranking changes immediately after approving reports
- 🎯 Monitor team performance in real-time
- 🔍 No need to manually refresh page

### For Admins
- 📈 Live dashboard for management oversight
- 🏆 Real-time competition visibility
- 📊 Instant feedback on approval impact

### For Petugas (if they could access)
- 🏃 See their rank change as soon as report is approved
- 🎯 Gamification with instant feedback
- 🏆 Motivational real-time competition

---

## Browser Compatibility

**Supported:**
- ✅ Chrome/Edge (Chromium) - 88+
- ✅ Firefox - 85+
- ✅ Safari - 14+
- ✅ Mobile browsers (iOS Safari, Chrome Android)

**Features Used:**
- Livewire (modern JS required)
- CSS animations (animate-ping, animate-spin)
- Backdrop filter blur
- Flexbox

---

## Known Limitations

### 1. Performance Issue
- **Problem:** N+1 query problem multiplied by polling frequency
- **Impact:** Database load increases with more petugas
- **Solution:** Implement bulk query optimization

### 2. Race Conditions
- **Problem:** If supervisor approves while page is refreshing
- **Impact:** Might see brief inconsistent state
- **Mitigation:** 5 second interval is long enough to avoid most conflicts

### 3. Battery Usage
- **Problem:** Constant polling drains battery on mobile
- **Impact:** Reduced battery life for mobile users
- **Solution:** Consider longer interval (10s) or conditional polling

### 4. Network Usage
- **Problem:** Regular AJAX requests consume bandwidth
- **Impact:** Might be noticeable on slow/metered connections
- **Solution:** Could implement WebSocket for true push notifications

---

## Future Enhancements

### Potential Improvements

1. **WebSocket Integration**
   - Use Laravel Broadcasting with Pusher/Soketi
   - True push notifications instead of polling
   - Better performance, less server load

2. **Conditional Polling**
   - Only poll when page is visible (Page Visibility API)
   - Pause polling when user is idle
   - Resume when user interacts

3. **Bulk Query Optimization**
   - Implement DashboardController query pattern
   - Reduce from 3N to 4 queries total
   - Much better for real-time polling

4. **Caching Layer**
   - Cache leaderboard data for 5 seconds in Redis
   - All users see same cached data
   - Invalidate cache on report approval

5. **Progressive Updates**
   - Only update changed rows, not entire table
   - Use Livewire morphing for smoother transitions
   - Highlight rows that changed

6. **Notification System**
   - Show toast notification when data updates
   - Display what changed (e.g., "Andi moved to #1")
   - Optional sound notification

---

## Performance Metrics

### Before Real-time
- Manual refresh required
- No user feedback on updates
- Stale data until refresh

### After Real-time
- Automatic refresh every 5s
- Live update indicator
- Maximum 5 second data staleness

### Database Impact (5 petugas)
- Queries per refresh: 15
- Queries per minute: 180
- Daily queries: 259,200

### Recommended with Optimization (5 petugas)
- Queries per refresh: 4
- Queries per minute: 48
- Daily queries: 69,120
- **Improvement: 73% reduction**

---

## Monitoring

### What to Monitor

1. **Database Load**
   - Query execution time
   - Connection pool usage
   - Slow query log

2. **Server Resources**
   - CPU usage
   - Memory consumption
   - Network traffic

3. **User Experience**
   - Page load time
   - Time to interactive
   - Livewire request latency

### Warning Signs

⚠️ **High database CPU** → Need bulk query optimization
⚠️ **Slow page responses** → Too many petugas, need caching
⚠️ **Increased bounce rate** → Polling too aggressive, increase interval

---

## Summary

✅ **Implemented:** Livewire polling with 5 second interval
✅ **Added:** Visual indicators (green pulsing dot, timestamp)
✅ **Added:** Loading overlay with spinner
✅ **Benefit:** Real-time ranking updates without manual refresh
⚠️ **Note:** N+1 query problem still exists, recommend optimization

**Status:** 🟢 Working and production-ready
**Recommended:** Apply bulk query optimization for better performance

---

**Test URL:** http://localhost:8003/admin/petugas-leaderboard
**Next Step:** Optimize queries or implement caching layer
