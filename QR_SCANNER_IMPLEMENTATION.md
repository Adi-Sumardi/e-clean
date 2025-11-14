# QR Code Scanner Implementation

## Overview
Fitur QR Code Scanner telah diimplementasikan dengan validasi jadwal petugas untuk meningkatkan keamanan dan kontrol akses lokasi pembersihan.

## Features Implemented

### 1. **QR Code Scanning with Jadwal Validation**
   - Petugas hanya dapat membuat laporan untuk lokasi yang ada dalam jadwal mereka hari ini
   - Validasi otomatis berdasarkan `petugas_id`, `lokasi_id`, dan `tanggal`
   - Real-time feedback dengan notifikasi yang jelas

### 2. **Access Control**
   - Hanya role `petugas` yang dapat mengakses halaman QR Scanner
   - Non-petugas tidak akan melihat menu Scan QR Code

### 3. **User Experience Improvements**
   - **Sukses Case**: Tampilan hijau dengan informasi jadwal lengkap (shift, jam kerja, prioritas, catatan)
   - **Gagal Case**: Tampilan kuning/warning dengan pesan untuk menghubungi supervisor
   - Button "Buat Laporan" hanya muncul jika petugas memiliki jadwal
   - Auto-redirect ke create report form dengan lokasi dan jadwal pre-filled

## File Changes

### 1. **app/Filament/Pages/QRScanner.php**
```php
Key Changes:
- Added `hasJadwal` boolean property
- Added `jadwal` property to store JadwalKebersihan instance
- Added `canView()` method - only petugas can access
- Enhanced `handleQRScanned()` with jadwal validation
- Updated `createReport()` to include jadwal_id in redirect
- Added Carbon for date comparison
```

### 2. **resources/views/filament/pages/qr-scanner.blade.php**
```blade
Key Changes:
- Added conditional success/warning banner based on `hasJadwal`
- Added jadwal information display (shift, jam, prioritas, catatan)
- Conditional "Buat Laporan" button - only visible if hasJadwal
- Enhanced warning message for unauthorized access
```

## User Flow

### Scenario 1: Petugas with Valid Jadwal
1. Petugas opens "Scan QR Code" page
2. Allows camera access and starts scanning
3. Scans QR code at assigned location
4. ✅ **Success**: Green banner appears with message "QR Code Berhasil Dipindai!"
5. Shows jadwal details: Shift, Jam kerja, Prioritas (if any), Catatan (if any)
6. Shows lokasi information
7. Button "Buat Laporan Kegiatan" is visible and enabled
8. Click button → Redirects to create report form with pre-filled data

### Scenario 2: Petugas without Jadwal
1. Petugas opens "Scan QR Code" page
2. Allows camera access and starts scanning
3. Scans QR code at non-assigned location
4. ⚠️ **Warning**: Yellow banner appears with message "Akses Ditolak"
5. Shows message: "Anda tidak memiliki jadwal untuk lokasi ini hari ini"
6. Shows instruction: "Silakan hubungi supervisor Anda"
7. Shows lokasi information (for reference)
8. Button "Buat Laporan Kegiatan" is **hidden**
9. Only "Scan Lagi" button is available

## Database Query Logic

```php
$today = Carbon::today();
$jadwal = JadwalKebersihan::where('petugas_id', auth()->id())
    ->where('lokasi_id', $scannedLokasi->id)
    ->whereDate('tanggal', $today)
    ->first();
```

**Validation Rules:**
- Must match logged-in petugas (`petugas_id`)
- Must match scanned location (`lokasi_id`)
- Must be scheduled for today (`tanggal = today`)

## Security Features

1. **Role-Based Access**: Only petugas role can access QR Scanner page
2. **Jadwal Validation**: Prevents unauthorized reporting at non-assigned locations
3. **Date Validation**: Only today's jadwal are considered valid
4. **Double Check**: Server-side validation in `createReport()` method prevents bypass

## Integration with Activity Reports

When petugas scans a valid QR code and has jadwal:
- Redirects to: `/admin/resources/activity-reports/activity-reports/create`
- URL Parameters:
  - `lokasi_id`: Pre-fills lokasi field
  - `jadwal_id`: Pre-fills jadwal field

This ensures:
- ✅ Correct lokasi is selected
- ✅ Correct jadwal reference is recorded
- ✅ Faster report creation (less manual input)
- ✅ Data consistency and accuracy

## Testing Checklist

### Setup Test Data
- [ ] Create at least 2 petugas accounts
- [ ] Create at least 3 lokasi with QR codes
- [ ] Create jadwal for Petugas A at Lokasi 1 for today
- [ ] Create jadwal for Petugas B at Lokasi 2 for today
- [ ] Leave Lokasi 3 without any jadwal for today

### Test Cases

#### TC1: Valid Scan (Petugas with Jadwal)
- [ ] Login as Petugas A
- [ ] Open "Scan QR Code" page
- [ ] Scan QR Code of Lokasi 1
- [ ] Verify green success banner appears
- [ ] Verify jadwal info is displayed correctly
- [ ] Verify "Buat Laporan Kegiatan" button is visible
- [ ] Click button and verify redirect works
- [ ] Verify lokasi and jadwal are pre-filled in form

#### TC2: Invalid Scan (Petugas without Jadwal)
- [ ] Login as Petugas A
- [ ] Open "Scan QR Code" page
- [ ] Scan QR Code of Lokasi 2 (assigned to Petugas B)
- [ ] Verify yellow warning banner appears
- [ ] Verify message says "tidak memiliki jadwal"
- [ ] Verify "Buat Laporan Kegiatan" button is hidden
- [ ] Verify "Scan Lagi" button is visible

#### TC3: Non-existent Location
- [ ] Login as any Petugas
- [ ] Scan invalid/fake QR code
- [ ] Verify error notification appears
- [ ] Verify no data is displayed

#### TC4: Role Access Control
- [ ] Login as Supervisor
- [ ] Verify "Scan QR Code" menu is NOT visible
- [ ] Try to access URL directly: `/admin/qr-scanner`
- [ ] Verify access is denied (403 or redirect)

#### TC5: Scanner Camera Functionality
- [ ] Test on desktop with webcam
- [ ] Test on mobile with back camera
- [ ] Test on mobile with front camera
- [ ] Verify camera selection dropdown works
- [ ] Verify "Mulai Scan" button works
- [ ] Verify "Berhenti" button works
- [ ] Verify auto-stop after successful scan

## API Endpoints (Future Enhancement)

For mobile app integration, consider creating API endpoints:

```php
POST /api/qr/validate
{
    "qr_data": "encoded_json_string",
    "petugas_id": 123
}

Response (Success):
{
    "success": true,
    "has_jadwal": true,
    "lokasi": {...},
    "jadwal": {...}
}

Response (No Jadwal):
{
    "success": true,
    "has_jadwal": false,
    "lokasi": {...},
    "message": "Anda tidak memiliki jadwal untuk lokasi ini"
}
```

## Future Improvements

1. **Shift Time Validation**
   - Check if current time is within shift time range
   - Show different warning if scanning outside shift hours

2. **Already Reported Check**
   - Check if report already exists for this lokasi+jadwal
   - Prevent duplicate reports

3. **Offline Mode**
   - Cache jadwal data for offline scanning
   - Queue reports for sync when online

4. **Analytics**
   - Track scan attempts (successful vs failed)
   - Monitor unauthorized access attempts
   - Generate reports on petugas punctuality

5. **Multi-shift Support**
   - Allow scanning for multiple shifts in one day
   - Show all shifts for the day

## Troubleshooting

### Issue: Camera not accessible
**Solution**: Ensure HTTPS is enabled or use localhost for testing

### Issue: QR code not detected
**Solution**:
- Ensure good lighting
- Hold camera steady
- Try different distance from QR code
- Check QR code quality (not damaged/blurry)

### Issue: "Scan Lagi" doesn't work
**Solution**: Check browser console for Livewire errors

### Issue: Redirect not working after successful scan
**Solution**:
- Verify route name is correct
- Check if ActivityReport resource is published
- Verify petugas has permission to create reports

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console for JavaScript errors
3. Verify database has correct jadwal data
4. Test QR code encoding/decoding with QRCodeService

---

**Last Updated**: 2025-11-13
**Version**: 1.0.0
**Author**: Claude Code
