# Leaderboard UI Changes

**Date:** 2025-11-13
**Change Type:** UI Simplification

---

## Summary

Removed "Rating Penilaian" column from the Petugas Leaderboard table because it was showing "N/A" for all entries. The column attempted to query a non-existent `rating_total` field from the `penilaians` table.

---

## Changes Made

### 1. Frontend (Blade Template)

**File:** [resources/views/filament/pages/petugas-leaderboard.blade.php](resources/views/filament/pages/petugas-leaderboard.blade.php)

**Removed:**
- Table header: "Rating Penilaian" column (line 104-106)
- Table cell: Display of avg_penilaian_rating with stars (line 196-212)

**Updated:**
- Score formula description: Removed reference to "Rating Penilaian × 20"

### 2. Backend (Livewire Page)

**File:** [app/Filament/Pages/PetugasLeaderboard.php](app/Filament/Pages/PetugasLeaderboard.php)

**Removed:**
- Query for `avg_penilaian_rating` using non-existent fields (line 89-95)
- Formula calculation component for penilaian rating (line 99-101)
- Array key `avg_penilaian_rating` from leaderboard data (line 115)

**Updated Score Formula:**
```php
// Before
$score = ($approvedReports * 10) + (($avgReportRating ?? 0) * 20) + (($avgPenilaianRating ?? 0) * 20);

// After
$score = ($approvedReports * 10) + (($avgReportRating ?? 0) * 20);
```

---

## Current Leaderboard Columns

| Column | Description | Source |
|--------|-------------|--------|
| **Peringkat** | Rank (1st, 2nd, 3rd, etc.) | Calculated |
| **Nama Petugas** | Worker name + email | users table |
| **Laporan Disetujui** | Approved reports / Total reports | activity_reports |
| **Tingkat Persetujuan** | Approval rate percentage | Calculated |
| **Rating Laporan** | Average rating from approved reports | activity_reports.rating |
| **Total Skor** | Overall score | Calculated |

---

## Score Calculation

**Current Formula:**
```
Total Skor = (Laporan Disetujui × 10) + (Rating Laporan × 20)
```

**Components:**
- **Laporan Disetujui × 10**: Each approved report contributes 10 points
- **Rating Laporan × 20**: Average rating (1-5) from approved reports multiplied by 20

**Example:**
- Petugas with 5 approved reports and average rating 4.5:
  - Score = (5 × 10) + (4.5 × 20) = 50 + 90 = **140 points**

---

## Why "Rating Penilaian" Was Removed

### Original Problem:
The column attempted to query:
```php
$avgPenilaianRating = Penilaian::where('petugas_id', $petugas->id)
    ->where(function ($query) use ($startDate, $endDate) {
        $query->whereBetween('periode_start', [$startDate, $endDate])
              ->orWhereBetween('periode_end', [$startDate, $endDate]);
    })
    ->avg('rating_total');
```

### Issues:
1. **Non-existent fields**: `periode_start`, `periode_end`, and `rating_total` don't exist in penilaians table
2. **Incorrect structure**: The penilaians table has:
   - `periode_bulan` (month)
   - `periode_tahun` (year)
   - `rata_rata` (average score, not rating_total)
3. **Different purpose**: The penilaian system calculates 3 aspects (kualitas, ketepatan, kebersihan), not a simple rating

### Result:
- Query always returned NULL
- UI displayed "N/A" for all entries
- Column added no value to users

---

## Related Systems

### Penilaian Auto-Calculation
The penilaian system still works correctly for tracking detailed performance metrics:

**Location:** [app/Services/PenilaianService.php](app/Services/PenilaianService.php)

**Triggered by:** Supervisor approval of activity reports

**Calculates:**
- Skor Kualitas (Quality score)
- Skor Ketepatan Waktu (Punctuality score)
- Skor Kebersihan (Cleanliness score)
- Rata-rata (Average of 3 scores)
- Kategori (Performance category)

**Used in:** API endpoint `/api/v1/dashboard/leaderboard` (different from Filament UI)

---

## Performance Note

⚠️ **This Filament leaderboard page still has N+1 query problem:**

```php
foreach ($allPetugas as $petugas) {
    // Query 1: Count approved reports
    $approvedReports = ActivityReport::where('petugas_id', $petugas->id)...

    // Query 2: Count total reports
    $totalReports = ActivityReport::where('petugas_id', $petugas->id)...

    // Query 3: Average rating
    $avgReportRating = ActivityReport::where('petugas_id', $petugas->id)...
}
```

**Current Performance:** 3 queries per petugas = **15 queries for 5 petugas**

**Recommended Fix:** Use bulk aggregation like in [DashboardController](app/Http/Controllers/Api/DashboardController.php#L380-L470) which uses only 4 queries total.

---

## Testing

After changes, verify:
1. ✅ Column "Rating Penilaian" removed from UI
2. ✅ No "N/A" displayed in table
3. ✅ Score calculation still works correctly
4. ✅ Leaderboard ranks properly by score
5. ✅ No PHP errors in logs

**Test URL:** http://localhost:8003/admin/petugas-leaderboard

---

## User Impact

**Before:**
- Confusing "N/A" column that provided no information
- Users couldn't understand what "Rating Penilaian" meant vs "Rating Laporan"

**After:**
- Cleaner, more focused leaderboard
- Only shows meaningful metrics
- Easier to understand ranking system

---

**Status:** ✅ Complete
**Breaking Changes:** None - simplified existing UI
