# 🧪 MANUAL TESTING GUIDE - E-CLEAN APPLICATION

**Date:** 24 November 2025
**Version:** 1.1.0
**Tester:** [Your Name]
**Status:** Ready for Testing

---

## 🎯 TESTING OBJECTIVES

1. Verify all IntelliSense fixes work correctly in runtime
2. Test all dashboards for each role
3. Test CRUD operations on all resources
4. Test Google OAuth integration
5. Identify any bugs or issues
6. Document all findings

---

## ⚙️ PRE-TESTING SETUP

### Server Status Check

```bash
# 1. Check if server is running
ps aux | grep "php artisan serve"

# 2. If not running, start server
php artisan serve --port=8000

# 3. Access application
# URL: http://localhost:8000/admin/login
```

### Database Status Check

```bash
# Check users in database
php artisan tinker --execute="User::with('roles')->get()->each(fn(\$u) => print(\$u->email . ' - ' . \$u->roles->pluck('name')->implode(',') . PHP_EOL));"
```

**Expected Output:**
```
superadmin@eclean.test - super_admin
admin@eclean.test - admin
supervisor@eclean.test - supervisor
pengurus@eclean.test - pengurus
petugas1@eclean.test - petugas
```

---

## 📋 TESTING CHECKLIST

### Phase 1: Authentication Testing

#### Test 1.1: Login Page Access
- [ ] Navigate to http://localhost:8000/admin/login
- [ ] Page loads without errors
- [ ] Google OAuth button is visible
- [ ] Form fields are present (Email, Password)
- [ ] Remember me checkbox is visible
- [ ] No console errors (F12)

**Expected Result:** ✅ Login page loads perfectly with Google button

---

#### Test 1.2: Super Admin Login
**Credentials:** superadmin@eclean.test / password

- [ ] Enter credentials
- [ ] Click "Sign in"
- [ ] Redirected to dashboard
- [ ] No errors displayed
- [ ] Dashboard widgets load

**Expected Result:** ✅ Successfully logged in and dashboard visible

**Screenshot Location:** _[Save screenshot here]_

**Issues Found:** _[Describe any issues]_

---

#### Test 1.3: Admin Login
**Credentials:** admin@eclean.test / password

- [ ] Logout from super admin
- [ ] Login as admin
- [ ] Dashboard accessible
- [ ] Appropriate menus visible

**Expected Result:** ✅ Admin has appropriate access level

---

#### Test 1.4: Supervisor Login
**Credentials:** supervisor@eclean.test / password

- [ ] Logout from previous user
- [ ] Login as supervisor
- [ ] Dashboard shows supervisor-specific widgets
- [ ] Pending reports widget visible

**Expected Result:** ✅ Supervisor dashboard loads correctly

---

#### Test 1.5: Pengurus Login
**Credentials:** pengurus@eclean.test / password

- [ ] Logout from previous user
- [ ] Login as pengurus
- [ ] Read-only dashboard visible
- [ ] Cannot create/edit/delete

**Expected Result:** ✅ Pengurus has read-only access

---

#### Test 1.6: Petugas Login
**Credentials:** petugas1@eclean.test / password

- [ ] Logout from previous user
- [ ] Login as petugas
- [ ] Personal dashboard visible
- [ ] Quick actions available
- [ ] Can only see own reports

**Expected Result:** ✅ Petugas sees personal dashboard

---

### Phase 2: Dashboard Widget Testing

#### Test 2.1: Super Admin Dashboard
**Login as:** superadmin@eclean.test

**Widgets to Check:**
- [ ] Stats Overview Widget
  - [ ] Total Active Locations
  - [ ] Total Active Petugas
  - [ ] Today's Schedules
  - [ ] Reports This Month
  - [ ] Pending Approvals
  - [ ] Average Rating

- [ ] Activity Report Chart (Line Chart)
  - [ ] Chart renders without errors
  - [ ] Can filter by date range
  - [ ] Can filter by petugas
  - [ ] Can filter by location

- [ ] Petugas Performance Chart (Bar Chart)
  - [ ] Shows top 10 performers
  - [ ] Data displays correctly

- [ ] Recent Activity Table
  - [ ] Shows latest 10 reports
  - [ ] Data is accurate

**Expected Result:** ✅ All widgets load and display data correctly

**Issues Found:**
```
[Describe any widget errors or data issues]
```

---

#### Test 2.2: Admin Dashboard
**Login as:** admin@eclean.test

- [ ] Similar widgets to Super Admin
- [ ] All charts render correctly
- [ ] No access errors
- [ ] Statistics are accurate

**Issues Found:** _[List issues]_

---

#### Test 2.3: Supervisor Dashboard
**Login as:** supervisor@eclean.test

**Specific Widgets:**
- [ ] Pending Reports Widget
  - [ ] Shows reports waiting approval
  - [ ] Can approve/reject from widget

- [ ] Today's Schedule Widget
  - [ ] Shows today's cleaning schedules
  - [ ] Data is correct

**Issues Found:** _[List issues]_

---

#### Test 2.4: Pengurus Dashboard
**Login as:** pengurus@eclean.test

**Widgets to Check:**
- [ ] Stats Overview Widget (Read-only)
- [ ] Monthly Summary Widget (Doughnut Chart)
- [ ] Performance Trend Widget (7 days)
- [ ] Location Status Widget (Stacked Bar)
- [ ] Top Performers Leaderboard
- [ ] Recent Reports Table (Read-only)

**Expected Result:** ✅ All widgets display, no edit buttons

**Issues Found:** _[List issues]_

---

#### Test 2.5: Petugas Dashboard
**Login as:** petugas1@eclean.test

**Widgets to Check:**
- [ ] My Today's Schedules
- [ ] My Recent Reports
- [ ] My Performance Stats
  - [ ] Total working hours this month
  - [ ] Total reports this month
  - [ ] Average rating
- [ ] Pending Reports Count
- [ ] Quick Action Buttons
  - [ ] Create New Report button works
  - [ ] View My Schedule button works

**Expected Result:** ✅ Personal dashboard with limited data

**Issues Found:** _[List issues]_

---

### Phase 3: Resource CRUD Testing

#### Test 3.1: Users Management
**Login as:** superadmin@eclean.test

**Create User:**
- [ ] Click "Users" in sidebar
- [ ] Click "New" button
- [ ] Fill form:
  - Name: Test User
  - Email: test@example.com
  - Password: password123
  - Phone: 081234567890
  - Role: petugas
  - Active: Yes
- [ ] Click "Create"
- [ ] User appears in list

**Edit User:**
- [ ] Click edit icon on test user
- [ ] Change name to "Test User Updated"
- [ ] Click "Save changes"
- [ ] Name updated successfully

**Delete User:**
- [ ] Click delete icon on test user
- [ ] Confirm deletion
- [ ] User removed from list

**Expected Result:** ✅ All CRUD operations work

**Issues Found:** _[List issues]_

---

#### Test 3.2: Lokasi Management
**Login as:** admin@eclean.test

**Create Location:**
- [ ] Navigate to "Lokasi"
- [ ] Click "New"
- [ ] Fill form:
  - Kode: Auto-generated
  - Nama Lokasi: Test Toilet
  - Kategori: Toilet
  - Lantai: Lantai 1
  - Luas: 10
  - Is Active: Yes
- [ ] Upload photo (optional)
- [ ] Click "Create"
- [ ] QR code generated automatically

**View QR Code:**
- [ ] Click on location
- [ ] View QR code
- [ ] Download QR code (PNG)

**Expected Result:** ✅ Location created with QR code

**Issues Found:** _[List issues]_

---

#### Test 3.3: Jadwal Kebersihan Management
**Login as:** admin@eclean.test

**Create Schedule:**
- [ ] Navigate to "Jadwal Kebersihan"
- [ ] Click "New"
- [ ] Fill form:
  - Petugas: Select petugas1
  - Lokasi: Select test location
  - Tanggal: Tomorrow
  - Shift: Pagi
  - Priority: Normal
- [ ] Click "Create"
- [ ] Schedule appears in list

**Expected Result:** ✅ Schedule created successfully

**Issues Found:** _[List issues]_

---

#### Test 3.4: Activity Reports
**Login as:** petugas1@eclean.test

**Create Activity Report:**
- [ ] Navigate to "Laporan Kegiatan" or use Quick Action
- [ ] Fill wizard form:
  - Step 1: Select location
  - Step 2: Upload "before" photos
  - Step 3: Describe activity
  - Step 4: Upload "after" photos
  - Step 5: Capture GPS
  - Step 6: Review and submit
- [ ] Click "Submit"
- [ ] Report created with status "submitted"

**Login as supervisor and Approve:**
- [ ] Login as supervisor@eclean.test
- [ ] Navigate to "Laporan Kegiatan"
- [ ] Find the pending report
- [ ] Click edit
- [ ] Change status to "Approved"
- [ ] Add rating (1-5)
- [ ] Add supervisor notes
- [ ] Click "Save"

**Expected Result:** ✅ Report workflow works correctly

**Issues Found:** _[List issues]_

---

#### Test 3.5: Penilaian (Performance Evaluation)
**Login as:** supervisor@eclean.test

**Create Evaluation:**
- [ ] Navigate to "Penilaian"
- [ ] Click "New"
- [ ] Fill form:
  - Petugas: Select petugas1
  - Period: Select date range
  - Aspek Kebersihan: 4
  - Aspek Kerapihan: 5
  - Aspek Ketepatan Waktu: 4
  - Aspek Kelengkapan Laporan: 5
  - Notes: "Good performance"
- [ ] Click "Create"
- [ ] Evaluation saved with auto-calculated average

**Expected Result:** ✅ Evaluation created successfully

**Issues Found:** _[List issues]_

---

### Phase 4: Special Features Testing

#### Test 4.1: Google OAuth Login
**Prerequisites:** Google OAuth credentials configured in .env

**Test Steps:**
1. [ ] Logout from current session
2. [ ] Go to http://localhost:8000/admin/login
3. [ ] Click "Continue with Google" button
4. [ ] Redirected to Google OAuth consent screen
5. [ ] Select Google account
6. [ ] Grant permissions
7. [ ] Redirected back to application
8. [ ] Logged in successfully

**Test Auto-Linking:**
1. [ ] Admin creates user with email matching Google account
2. [ ] User logs in with Google
3. [ ] Account auto-links (provider → 'hybrid')
4. [ ] User can now login with BOTH methods

**Expected Result:** ✅ Google OAuth works, auto-linking successful

**Issues Found:** _[List issues]_

---

#### Test 4.2: QR Code Scanner
**Login as:** petugas1@eclean.test

- [ ] Navigate to "QR Scanner" page
- [ ] Allow camera access
- [ ] Scan a location QR code
- [ ] Location details display
- [ ] "Create Report" button available
- [ ] Clicking button auto-fills location

**Expected Result:** ✅ QR Scanner works correctly

**Issues Found:** _[List issues]_

---

#### Test 4.3: Leaderboard
**Login as:** any role

- [ ] Navigate to "Leaderboard" page
- [ ] Top 10 petugas displayed
- [ ] Ranking based on performance
- [ ] Trophy icons visible (🥇🥈🥉)
- [ ] Performance badges shown
- [ ] Real-time updates work

**Expected Result:** ✅ Leaderboard displays correctly

**Issues Found:** _[List issues]_

---

#### Test 4.4: Export Features

**PDF Export:**
- [ ] Login as admin
- [ ] Navigate to "Laporan Kegiatan"
- [ ] Click "Export" → "PDF"
- [ ] PDF generated successfully
- [ ] Contains photos and data
- [ ] Layout is professional

**Excel Export:**
- [ ] Click "Export" → "Excel"
- [ ] Excel file downloaded
- [ ] Contains 14 columns
- [ ] Data is formatted correctly
- [ ] Photo URLs included

**Expected Result:** ✅ Both export formats work

**Issues Found:** _[List issues]_

---

### Phase 5: Performance Testing

#### Test 5.1: Page Load Times

| Page | Role | Load Time | Status |
|------|------|-----------|--------|
| Dashboard | Super Admin | _____ms | [ ] OK |
| Dashboard | Admin | _____ms | [ ] OK |
| Dashboard | Supervisor | _____ms | [ ] OK |
| Dashboard | Pengurus | _____ms | [ ] OK |
| Dashboard | Petugas | _____ms | [ ] OK |
| Users List | Admin | _____ms | [ ] OK |
| Lokasi List | Admin | _____ms | [ ] OK |
| Activity Reports | Supervisor | _____ms | [ ] OK |

**Expected:** All pages load within 2 seconds

**Issues Found:** _[List slow pages]_

---

#### Test 5.2: Chart Rendering

- [ ] All charts render within 1 second
- [ ] No visual glitches
- [ ] Interactive features work (hover, click)
- [ ] Filters apply correctly
- [ ] Data updates in real-time

**Issues Found:** _[List chart issues]_

---

### Phase 6: Error Handling Testing

#### Test 6.1: Invalid Login
- [ ] Enter wrong password
- [ ] Error message displayed
- [ ] User not logged in
- [ ] Can retry

**Expected Result:** ✅ Proper error handling

---

#### Test 6.2: Unauthorized Access
- [ ] Login as petugas
- [ ] Try to access http://localhost:8000/admin/users
- [ ] Access denied
- [ ] Proper error message

**Expected Result:** ✅ 403 Forbidden or redirect

---

#### Test 6.3: Form Validation
- [ ] Try to create user with duplicate email
- [ ] Validation error shown
- [ ] Try to submit form with missing required fields
- [ ] Validation errors displayed

**Expected Result:** ✅ All validations work

---

### Phase 7: Mobile Responsiveness

#### Test 7.1: Mobile View (375px width)
- [ ] Dashboard adapts to mobile
- [ ] Sidebar becomes collapsible
- [ ] Tables are scrollable
- [ ] Forms are usable
- [ ] Buttons are tappable
- [ ] No horizontal scroll

**Expected Result:** ✅ Fully responsive on mobile

---

#### Test 7.2: Tablet View (768px width)
- [ ] Layout optimized for tablet
- [ ] All features accessible
- [ ] Charts render correctly

**Expected Result:** ✅ Fully responsive on tablet

---

## 📊 BUG REPORT TEMPLATE

When you find a bug, document it using this template:

```markdown
### Bug #[Number]: [Short Description]

**Severity:** [Critical / High / Medium / Low]

**User Role:** [Super Admin / Admin / Supervisor / Pengurus / Petugas]

**Steps to Reproduce:**
1. [Step 1]
2. [Step 2]
3. [Step 3]

**Expected Behavior:**
[What should happen]

**Actual Behavior:**
[What actually happens]

**Error Message (if any):**
```
[Paste error message or screenshot]
```

**Console Errors (F12):**
```
[Paste console errors]
```

**Screenshots:**
[Attach screenshots]

**Environment:**
- Browser: [Chrome / Firefox / Safari]
- OS: [macOS / Windows / Linux]
- Screen Size: [Desktop / Tablet / Mobile]

**Additional Notes:**
[Any other relevant information]
```

---

## 🎯 TESTING COMPLETION CHECKLIST

### Phase 1: Authentication
- [ ] All 6 role logins tested
- [ ] No login errors
- [ ] Proper redirects

### Phase 2: Dashboards
- [ ] All 5 dashboards tested
- [ ] All widgets working
- [ ] Data displays correctly

### Phase 3: CRUD Operations
- [ ] Users CRUD works
- [ ] Lokasi CRUD works
- [ ] Jadwal CRUD works
- [ ] Activity Reports CRUD works
- [ ] Penilaian CRUD works

### Phase 4: Special Features
- [ ] Google OAuth tested
- [ ] QR Scanner tested
- [ ] Leaderboard tested
- [ ] Export features tested

### Phase 5: Performance
- [ ] Page loads < 2 seconds
- [ ] Charts render < 1 second
- [ ] No performance issues

### Phase 6: Error Handling
- [ ] Invalid inputs handled
- [ ] Unauthorized access blocked
- [ ] Proper error messages

### Phase 7: Responsiveness
- [ ] Mobile view works
- [ ] Tablet view works
- [ ] Desktop view works

---

## 📝 FINAL TEST REPORT

**Testing Date:** _______________

**Total Tests Performed:** _______

**Tests Passed:** _______

**Tests Failed:** _______

**Critical Bugs Found:** _______

**Non-Critical Issues:** _______

**Overall Status:** [✅ PASS / ❌ FAIL / ⚠️ PARTIAL]

**Recommendations:**
```
[Your recommendations here]
```

**Sign-off:**
```
Tester Name: _______________
Date: _______________
Signature: _______________
```

---

## 🚀 NEXT STEPS AFTER TESTING

1. **If All Tests Pass:**
   - ✅ Application is production-ready
   - ✅ Can proceed with deployment
   - ✅ Create production backup
   - ✅ Update documentation

2. **If Bugs Found:**
   - ❌ Document all bugs
   - ❌ Prioritize by severity
   - ❌ Fix critical bugs first
   - ❌ Re-test after fixes

3. **If Performance Issues:**
   - ⚠️ Identify bottlenecks
   - ⚠️ Optimize slow queries
   - ⚠️ Add caching where needed
   - ⚠️ Re-test performance

---

**✅ Good luck with testing!**
**📧 Report issues to: developer@email.com**

---

**© 2025 E-Clean Project - Manual Testing Guide**
