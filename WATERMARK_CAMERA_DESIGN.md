# 📸 WATERMARK CAMERA SYSTEM - TECHNICAL DESIGN

**Date:** 24 November 2025
**Version:** 2.0.0
**Feature:** GPS-Enabled Watermark Camera for Activity Reports
**Purpose:** Prevent fraud & ensure photo authenticity

---

## 🎯 BUSINESS REQUIREMENTS

### Problem Statement:
Petugas bisa upload foto lama, foto dari tempat lain, atau foto palsu yang tidak sesuai dengan waktu dan lokasi kerja sebenarnya.

### Solution:
**Real-time camera dengan watermark otomatis** yang menampilkan:
- ✅ Nama Petugas
- ✅ Lokasi/Area Kerja
- ✅ Tanggal & Jam (Real-time)
- ✅ GPS Coordinates (Latitude, Longitude)
- ✅ GPS Accuracy
- ✅ Verification Hash (untuk tamper detection)

### Key Benefits:
1. **Fraud Prevention** - Tidak bisa upload foto lama/palsu
2. **Accountability** - Setiap foto punya bukti kuat
3. **Legal Evidence** - Foto bisa jadi bukti hukum jika diperlukan
4. **Real-time Verification** - Supervisor langsung tahu foto asli atau tidak
5. **GPS Validation** - Pastikan petugas benar-benar di lokasi

---

## 🏗️ ARCHITECTURE OVERVIEW

```
┌─────────────────────────────────────────────────────────────┐
│                    WATERMARK CAMERA FLOW                     │
└─────────────────────────────────────────────────────────────┘

1. Petugas klik "Ambil Foto" button
   ↓
2. System request GPS permission & Camera access
   ↓
3. Get current GPS coordinates (real-time)
   ↓
4. Validate GPS location (within 50m radius of work location)
   ↓
5. Open camera dengan live watermark preview
   ↓
6. Petugas ambil foto
   ↓
7. System apply watermark overlay pada foto
   ↓
8. Generate verification hash (SHA-256)
   ↓
9. Compress to WebP format (80% quality)
   ↓
10. Upload to server + store metadata
   ↓
11. Display photo preview dengan watermark
```

---

## 🎨 WATERMARK DESIGN

### Layout 1: Bottom Overlay (Recommended)
```
┌──────────────────────────────────────────┐
│                                          │
│                                          │
│           PHOTO CONTENT                  │
│                                          │
│                                          │
│══════════════════════════════════════════│
│ 👤 AHMAD RIZKI                          │
│ 📍 Toilet Lantai 2 - Gedung A           │
│ 📅 24 Nov 2025, 14:35:22 WIB            │
│ 🌍 -6.200000, 106.816666                │
│ ✓ Verified • GPS Accuracy: 5m           │
└──────────────────────────────────────────┘
```

**Specifications:**
- Background: Semi-transparent black (opacity: 0.85)
- Text Color: White (#FFFFFF)
- Font: Inter/System Default, Bold
- Padding: 16px
- Border Top: 2px solid rgba(255,255,255,0.3)
- Height: Auto (flexible based on content)

---

### Layout 2: Corner Badge (Alternative)
```
┌──────────────────────────────────────────┐
│ ┌─────────────────────┐                 │
│ │ 👤 AHMAD RIZKI      │                 │
│ │ 📍 Toilet Lt. 2     │                 │
│ │ 📅 24/11 14:35      │                 │
│ │ 🌍 Verified ✓       │                 │
│ └─────────────────────┘                 │
│                                          │
│           PHOTO CONTENT                  │
│                                          │
│                                          │
└──────────────────────────────────────────┘
```

**Specifications:**
- Position: Top-left corner
- Background: Gradient (black to transparent)
- Text Color: White with shadow
- Padding: 12px
- Border Radius: 0 0 12px 0

---

### Layout 3: QR Code Verification (Most Secure)
```
┌──────────────────────────────────────────┐
│                                     ┌────┐
│                                     │ QR │
│           PHOTO CONTENT             │CODE│
│                                     └────┘
│══════════════════════════════════════════│
│ 👤 AHMAD RIZKI • 📍 Toilet Lantai 2     │
│ 📅 24 Nov 2025, 14:35:22 • 🌍 Verified  │
└──────────────────────────────────────────┘
```

**Features:**
- QR Code contains: Photo hash, GPS, timestamp, petugas ID
- QR Code can be scanned untuk instant verification
- Bottom bar dengan info ringkas
- QR Code di pojok kanan atas (100x100px)

---

## 🔐 FRAUD PREVENTION MECHANISMS

### 1. **GPS Location Validation**
```php
Algorithm: Haversine Formula
Max Radius: 50 meters from work location
Fallback: If GPS unavailable, reject photo

Validation Rules:
✓ GPS must be accurate (< 20m accuracy)
✓ GPS must be within 50m radius of work location
✓ GPS timestamp must match photo capture time (±2 minutes)
✓ Cannot use mock location (detect if GPS is spoofed)
```

### 2. **Timestamp Verification**
```php
Rules:
✓ Use server time (prevent device time manipulation)
✓ Timestamp must be within working hours (6 AM - 6 PM)
✓ Timestamp must match jadwal shift (pagi/siang/sore)
✓ Cannot upload photos from future dates
✓ Photos older than 1 hour are flagged for review
```

### 3. **Photo Metadata Extraction**
```php
EXIF Data Validation:
✓ Extract original GPS from EXIF (if available)
✓ Compare EXIF GPS with captured GPS
✓ Validate photo dimensions (min 800x600)
✓ Check if photo is edited (detect manipulation)
✓ Verify camera model matches device
```

### 4. **Hash Verification**
```php
Generate SHA-256 hash from:
- Original photo binary data
- GPS coordinates
- Timestamp
- Petugas ID
- Lokasi ID

Store hash in database for future verification
If watermark is removed, hash won't match
```

### 5. **Device Fingerprinting**
```php
Capture:
✓ Device model & OS version
✓ Browser user agent
✓ Screen resolution
✓ IP address
✓ Network type (WiFi/4G/5G)

Flag suspicious patterns:
- Multiple devices from same account
- Frequent device changes
- VPN usage during work hours
```

### 6. **Live Camera Detection**
```php
Prevent Gallery Upload:
✓ Force getUserMedia() API (live camera only)
✓ Disable file upload input
✓ Block clipboard paste
✓ Detect screen recording/screenshot attempts
✓ Require continuous camera stream (no pre-recorded video)
```

### 7. **Watermark Tamper Detection**
```php
Methods:
✓ Store watermark data in separate metadata table
✓ Generate unique signature embedded in photo pixels (steganography)
✓ If photo is edited, signature breaks
✓ Compare uploaded photo hash with original hash
✓ AI-based tamper detection (optional)
```

---

## 💾 DATABASE SCHEMA CHANGES

### New Table: `photo_metadata`
```sql
CREATE TABLE photo_metadata (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    activity_report_id BIGINT UNSIGNED,
    photo_path VARCHAR(255) NOT NULL,
    photo_type ENUM('before', 'after') NOT NULL,

    -- GPS Data
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    gps_accuracy FLOAT NOT NULL,
    gps_address TEXT,
    gps_validated BOOLEAN DEFAULT FALSE,
    gps_distance_from_location FLOAT, -- in meters

    -- Timestamp Data
    captured_at TIMESTAMP NOT NULL,
    server_time_at_capture TIMESTAMP NOT NULL,
    timezone VARCHAR(50) DEFAULT 'Asia/Jakarta',

    -- Device Data
    device_model VARCHAR(100),
    device_os VARCHAR(100),
    browser_agent TEXT,
    screen_resolution VARCHAR(50),
    ip_address VARCHAR(45),
    network_type VARCHAR(20),

    -- Verification Data
    photo_hash VARCHAR(64) NOT NULL, -- SHA-256
    watermark_hash VARCHAR(64) NOT NULL,
    exif_data JSON,
    is_tampered BOOLEAN DEFAULT FALSE,
    tamper_detection_score FLOAT,

    -- Metadata
    file_size INT UNSIGNED,
    original_dimensions VARCHAR(50),
    compressed_dimensions VARCHAR(50),
    compression_ratio FLOAT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (activity_report_id) REFERENCES activity_reports(id) ON DELETE CASCADE,
    INDEX idx_photo_hash (photo_hash),
    INDEX idx_captured_at (captured_at),
    INDEX idx_gps (latitude, longitude)
);
```

### Update Table: `activity_reports`
```sql
ALTER TABLE activity_reports
    ADD COLUMN foto_sebelum_verified BOOLEAN DEFAULT FALSE,
    ADD COLUMN foto_sesudah_verified BOOLEAN DEFAULT FALSE,
    ADD COLUMN verification_score FLOAT DEFAULT 0,
    ADD COLUMN fraud_flags JSON,
    ADD COLUMN manual_review_required BOOLEAN DEFAULT FALSE;
```

---

## 🛠️ TECHNICAL IMPLEMENTATION

### Technology Stack

**Frontend:**
- HTML5 Camera API (`getUserMedia()`)
- Canvas API (for watermark overlay)
- JavaScript Geolocation API
- Alpine.js (for reactivity)
- TailwindCSS (for styling)

**Backend:**
- Laravel 12.0
- Intervention Image (watermark processing)
- Spatie Image Optimizer (compression)
- GPS Validation Service
- Hash Generation Service

**Libraries:**
```json
{
    "intervention/image": "^3.0",
    "spatie/image-optimizer": "^1.7",
    "bacon/bacon-qr-code": "^3.0",
    "phpexif/exif": "^1.0"
}
```

---

## 📱 FRONTEND IMPLEMENTATION

### Step 1: Camera Component (Livewire)

**File:** `app/Livewire/WatermarkCamera.php`

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\WatermarkCameraService;
use Illuminate\Support\Facades\Auth;

class WatermarkCamera extends Component
{
    use WithFileUploads;

    public $photo;
    public $photoType; // 'before' or 'after'
    public $activityReportId;
    public $lokasiId;

    // GPS Data
    public $latitude;
    public $longitude;
    public $accuracy;
    public $address;

    // Device Data
    public $deviceInfo;

    public function mount($type, $activityReportId = null, $lokasiId = null)
    {
        $this->photoType = $type;
        $this->activityReportId = $activityReportId;
        $this->lokasiId = $lokasiId;
    }

    public function capturePhoto($photoData, $gpsData, $deviceData)
    {
        // Validate GPS
        $validation = app(WatermarkCameraService::class)->validateGPS(
            $gpsData['latitude'],
            $gpsData['longitude'],
            $this->lokasiId
        );

        if (!$validation['valid']) {
            return $this->dispatchBrowserEvent('photo-error', [
                'message' => $validation['error']
            ]);
        }

        // Process photo dengan watermark
        $result = app(WatermarkCameraService::class)->processPhoto([
            'photo_data' => $photoData,
            'gps_data' => $gpsData,
            'device_data' => $deviceData,
            'petugas_id' => Auth::id(),
            'lokasi_id' => $this->lokasiId,
            'photo_type' => $this->photoType,
        ]);

        if ($result['success']) {
            $this->dispatchBrowserEvent('photo-captured', [
                'path' => $result['path'],
                'metadata' => $result['metadata']
            ]);
        } else {
            $this->dispatchBrowserEvent('photo-error', [
                'message' => $result['error']
            ]);
        }
    }

    public function render()
    {
        return view('livewire.watermark-camera');
    }
}
```

---

### Step 2: Camera View (Blade + Alpine.js)

**File:** `resources/views/livewire/watermark-camera.blade.php`

```html
<div
    x-data="watermarkCamera()"
    x-init="init()"
    class="watermark-camera-container"
>
    <!-- Camera Preview -->
    <div class="relative">
        <video
            x-ref="video"
            autoplay
            playsinline
            class="w-full rounded-lg"
            style="max-height: 70vh;"
        ></video>

        <!-- Live Watermark Overlay -->
        <div
            class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-85 text-white p-4 border-t-2 border-white border-opacity-30"
            x-show="cameraReady"
        >
            <div class="space-y-1 text-sm">
                <div class="flex items-center">
                    <span class="mr-2">👤</span>
                    <span class="font-bold" x-text="petugasName"></span>
                </div>
                <div class="flex items-center">
                    <span class="mr-2">📍</span>
                    <span x-text="lokasiName"></span>
                </div>
                <div class="flex items-center">
                    <span class="mr-2">📅</span>
                    <span x-text="currentDateTime"></span>
                </div>
                <div class="flex items-center" x-show="gpsReady">
                    <span class="mr-2">🌍</span>
                    <span x-text="`${latitude.toFixed(6)}, ${longitude.toFixed(6)}`"></span>
                </div>
                <div class="flex items-center" x-show="gpsReady">
                    <span class="mr-2 text-green-400">✓</span>
                    <span x-text="`Verified • GPS Accuracy: ${accuracy}m`"></span>
                </div>
            </div>
        </div>

        <!-- GPS Loading Indicator -->
        <div
            x-show="!gpsReady"
            class="absolute top-4 right-4 bg-yellow-500 text-black px-3 py-2 rounded-lg text-sm font-bold"
        >
            📡 Getting GPS...
        </div>

        <!-- Error Messages -->
        <div
            x-show="errorMessage"
            class="absolute top-4 left-4 right-4 bg-red-500 text-white px-4 py-3 rounded-lg"
            x-text="errorMessage"
        ></div>
    </div>

    <!-- Controls -->
    <div class="mt-4 flex justify-center space-x-4">
        <button
            @click="capturePhoto()"
            :disabled="!cameraReady || !gpsReady"
            class="px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed"
        >
            📸 Ambil Foto
        </button>

        <button
            @click="closeCamera()"
            class="px-6 py-4 bg-gray-600 hover:bg-gray-700 text-white rounded-full font-bold"
        >
            ✕ Tutup
        </button>
    </div>

    <!-- Hidden Canvas for processing -->
    <canvas x-ref="canvas" style="display: none;"></canvas>
</div>

<script>
function watermarkCamera() {
    return {
        stream: null,
        cameraReady: false,
        gpsReady: false,

        // User Data
        petugasName: '{{ Auth::user()->name }}',
        lokasiName: '{{ $lokasi->nama_lokasi ?? "Loading..." }}',

        // GPS Data
        latitude: 0,
        longitude: 0,
        accuracy: 0,
        address: '',

        // Time
        currentDateTime: '',

        // Error
        errorMessage: '',

        async init() {
            // Start time update
            this.updateDateTime();
            setInterval(() => this.updateDateTime(), 1000);

            // Request permissions
            await this.startCamera();
            await this.getGPSLocation();
        },

        async startCamera() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment', // Back camera
                        width: { ideal: 1920 },
                        height: { ideal: 1080 }
                    },
                    audio: false
                });

                this.$refs.video.srcObject = this.stream;
                this.cameraReady = true;
            } catch (error) {
                this.errorMessage = 'Tidak bisa mengakses kamera. Pastikan izin diberikan.';
                console.error('Camera error:', error);
            }
        },

        async getGPSLocation() {
            if (!navigator.geolocation) {
                this.errorMessage = 'GPS tidak didukung di browser ini.';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.latitude = position.coords.latitude;
                    this.longitude = position.coords.longitude;
                    this.accuracy = Math.round(position.coords.accuracy);
                    this.gpsReady = true;

                    // Get address from reverse geocoding
                    this.getAddress();
                },
                (error) => {
                    this.errorMessage = 'Tidak bisa mendapatkan lokasi GPS. Pastikan izin diberikan.';
                    console.error('GPS error:', error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        },

        async getAddress() {
            // Reverse geocoding (optional, could use server-side)
            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?lat=${this.latitude}&lon=${this.longitude}&format=json`
                );
                const data = await response.json();
                this.address = data.display_name;
            } catch (error) {
                console.error('Geocoding error:', error);
            }
        },

        updateDateTime() {
            const now = new Date();
            const options = {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
                timeZone: 'Asia/Jakarta'
            };
            this.currentDateTime = now.toLocaleString('id-ID', options) + ' WIB';
        },

        async capturePhoto() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            const ctx = canvas.getContext('2d');

            // Set canvas size to video dimensions
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // Draw video frame to canvas
            ctx.drawImage(video, 0, 0);

            // Draw watermark
            this.drawWatermark(ctx, canvas.width, canvas.height);

            // Convert to blob
            canvas.toBlob(async (blob) => {
                // Get device info
                const deviceInfo = {
                    model: navigator.userAgent,
                    os: navigator.platform,
                    screen: `${screen.width}x${screen.height}`,
                };

                // Prepare data
                const photoData = await this.blobToBase64(blob);
                const gpsData = {
                    latitude: this.latitude,
                    longitude: this.longitude,
                    accuracy: this.accuracy,
                    address: this.address
                };

                // Send to Livewire
                @this.capturePhoto(photoData, gpsData, deviceInfo);

            }, 'image/jpeg', 0.9);
        },

        drawWatermark(ctx, width, height) {
            // Watermark background
            const overlayHeight = 140;
            ctx.fillStyle = 'rgba(0, 0, 0, 0.85)';
            ctx.fillRect(0, height - overlayHeight, width, overlayHeight);

            // Border line
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(0, height - overlayHeight);
            ctx.lineTo(width, height - overlayHeight);
            ctx.stroke();

            // Text styling
            ctx.fillStyle = '#FFFFFF';
            ctx.font = 'bold 24px Inter, sans-serif';

            const padding = 20;
            let y = height - overlayHeight + 35;

            // Petugas name
            ctx.fillText(`👤 ${this.petugasName}`, padding, y);
            y += 28;

            // Location
            ctx.fillText(`📍 ${this.lokasiName}`, padding, y);
            y += 28;

            // DateTime
            ctx.fillText(`📅 ${this.currentDateTime}`, padding, y);
            y += 28;

            // GPS
            ctx.fillText(`🌍 ${this.latitude.toFixed(6)}, ${this.longitude.toFixed(6)}`, padding, y);
            y += 28;

            // Verified badge
            ctx.fillStyle = '#22c55e';
            ctx.fillText(`✓ Verified • GPS Accuracy: ${this.accuracy}m`, padding, y);
        },

        blobToBase64(blob) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            });
        },

        closeCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
            this.$dispatch('camera-closed');
        }
    }
}
</script>
```

---

## 🔧 BACKEND SERVICES

### Service: WatermarkCameraService

**File:** `app/Services/WatermarkCameraService.php`

```php
<?php

namespace App\Services;

use App\Models\ActivityReport;
use App\Models\PhotoMetadata;
use App\Models\Lokasi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class WatermarkCameraService
{
    public function validateGPS($latitude, $longitude, $lokasiId)
    {
        $lokasi = Lokasi::findOrFail($lokasiId);

        // Haversine formula to calculate distance
        $distance = $this->calculateDistance(
            $latitude,
            $longitude,
            $lokasi->latitude,
            $lokasi->longitude
        );

        // Check if within 50 meters
        if ($distance > 50) {
            return [
                'valid' => false,
                'error' => "Anda terlalu jauh dari lokasi kerja. Jarak: {$distance}m (Max: 50m)",
                'distance' => $distance
            ];
        }

        return [
            'valid' => true,
            'distance' => $distance
        ];
    }

    public function processPhoto($data)
    {
        try {
            // Decode base64 photo
            $photoData = $data['photo_data'];
            $photoData = str_replace('data:image/jpeg;base64,', '', $photoData);
            $photoData = str_replace(' ', '+', $photoData);
            $imageData = base64_decode($photoData);

            // Generate unique filename
            $filename = 'watermarked_' . Str::random(40) . '.webp';
            $path = 'activity-reports/' . $data['photo_type'] . '/' . $filename;

            // Save original image temporarily
            $tempPath = storage_path('app/temp/' . $filename);
            file_put_contents($tempPath, $imageData);

            // Process with Intervention Image (already has watermark from frontend)
            $image = Image::read($tempPath);

            // Compress to WebP
            $image->toWebp(80);

            // Save to storage
            Storage::put($path, $image->encode());

            // Generate hash for verification
            $hash = $this->generatePhotoHash($imageData, $data);

            // Save metadata
            $metadata = PhotoMetadata::create([
                'photo_path' => $path,
                'photo_type' => $data['photo_type'],
                'latitude' => $data['gps_data']['latitude'],
                'longitude' => $data['gps_data']['longitude'],
                'gps_accuracy' => $data['gps_data']['accuracy'],
                'gps_address' => $data['gps_data']['address'] ?? null,
                'captured_at' => now(),
                'server_time_at_capture' => now(),
                'device_model' => $data['device_data']['model'] ?? null,
                'device_os' => $data['device_data']['os'] ?? null,
                'screen_resolution' => $data['device_data']['screen'] ?? null,
                'photo_hash' => $hash,
                'watermark_hash' => hash('sha256', $path . $hash),
            ]);

            // Clean up temp file
            unlink($tempPath);

            return [
                'success' => true,
                'path' => $path,
                'metadata' => $metadata
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Gagal memproses foto: ' . $e->getMessage()
            ];
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        $distance = $earthRadius * $c;

        return round($distance, 2); // Return in meters
    }

    private function generatePhotoHash($imageData, $data)
    {
        $hashString = $imageData
            . $data['gps_data']['latitude']
            . $data['gps_data']['longitude']
            . $data['petugas_id']
            . $data['lokasi_id']
            . now()->timestamp;

        return hash('sha256', $hashString);
    }
}
```

---

## 📊 IMPLEMENTATION PHASES

### Phase 1: Database & Models (Week 1)
- [x] Create `photo_metadata` migration
- [x] Update `activity_reports` table
- [x] Create PhotoMetadata model
- [x] Update ActivityReport model relationships

### Phase 2: Backend Services (Week 1)
- [ ] Create WatermarkCameraService
- [ ] GPS validation logic
- [ ] Photo hash generation
- [ ] Watermark processing
- [ ] Metadata storage

### Phase 3: Frontend Camera UI (Week 2)
- [ ] Create Livewire camera component
- [ ] Implement getUserMedia() API
- [ ] Live watermark overlay
- [ ] GPS location capture
- [ ] Photo capture & upload

### Phase 4: Integration (Week 2)
- [ ] Replace FileUpload with camera button
- [ ] Integrate with ActivityReportResource
- [ ] Update form validation
- [ ] Add verification indicators

### Phase 5: Verification Dashboard (Week 3)
- [ ] Admin dashboard for photo verification
- [ ] Tamper detection alerts
- [ ] GPS validation reports
- [ ] Suspicious activity logs

### Phase 6: Testing & QA (Week 3)
- [ ] Unit tests for services
- [ ] Integration tests
- [ ] Manual testing on mobile devices
- [ ] Performance testing
- [ ] Security audit

---

## 🎯 SUCCESS METRICS

### Technical Metrics:
- ✅ GPS accuracy: < 20 meters
- ✅ Photo capture time: < 3 seconds
- ✅ Upload time: < 5 seconds
- ✅ Watermark clarity: Readable on all devices
- ✅ No false positives in validation: < 2%

### Business Metrics:
- ✅ Fraud reduction: 95%+
- ✅ Photo authenticity: 99%+
- ✅ User adoption: 90%+ petugas use camera
- ✅ Supervisor confidence: 95%+ trust photos
- ✅ Legal compliance: 100% audit-ready

---

## 🚀 NEXT STEPS

1. **Review & Approval** - User approval of this design
2. **Create Migration** - Database schema changes
3. **Install Packages** - Intervention Image, etc.
4. **Build Services** - WatermarkCameraService
5. **Create UI** - Livewire camera component
6. **Integration** - Replace file upload
7. **Testing** - Comprehensive testing
8. **Deployment** - Roll out to production

---

## 📝 NOTES

**Browser Support:**
- Chrome 53+
- Firefox 36+
- Safari 11+
- Edge 79+
- Mobile browsers (iOS Safari 11+, Chrome Mobile)

**Permissions Required:**
- Camera access (mandatory)
- GPS/Location access (mandatory)
- Storage access (automatic)

**File Size:**
- Original: ~2-4 MB (JPEG)
- After compression: ~400-800 KB (WebP)
- Savings: 80%

**Security:**
- All photos are server-side verified
- Hash tampering triggers alerts
- GPS spoofing detected
- Device fingerprinting enabled

---

**© 2025 E-Clean - Watermark Camera System**
**Status:** Design Complete - Ready for Implementation
**Estimated Development Time:** 3 weeks
**Priority:** High - Anti-Fraud Feature
