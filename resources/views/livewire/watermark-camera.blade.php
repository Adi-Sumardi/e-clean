<div
    x-data="watermarkCamera(@js($petugasName), @js($lokasiName), @js($photoType))"
    x-init="init()"
    class="watermark-camera-container"
    @photo-error.window="handleError($event.detail)"
    @photo-captured.window="handleSuccess($event.detail)"
>
    <!-- Camera Preview Container -->
    <div class="relative bg-black rounded-lg overflow-hidden" style="max-height: 70vh;">
        <!-- Video Stream -->
        <video
            x-ref="video"
            autoplay
            playsinline
            class="w-full h-auto"
            style="display: block;"
            x-show="cameraReady"
        ></video>

        <!-- Loading State -->
        <div x-show="!cameraReady" class="flex items-center justify-center h-96 bg-gray-800">
            <div class="text-center text-white">
                <svg class="animate-spin h-12 w-12 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-lg font-semibold">Memulai kamera...</p>
                <p class="text-sm text-gray-400 mt-2">Mohon izinkan akses kamera</p>
            </div>
        </div>

        <!-- Live Watermark Overlay (Bottom) -->
        <div
            x-show="cameraReady"
            class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-85 text-white p-4 border-t-2 border-white border-opacity-30"
            style="backdrop-filter: blur(5px);"
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
                    <span x-text="`Verified • GPS Accuracy: ${Math.round(accuracy)}m`"></span>
                </div>
            </div>
        </div>

        <!-- GPS Loading Indicator -->
        <div
            x-show="!gpsReady && cameraReady"
            class="absolute top-4 right-4 bg-yellow-500 text-black px-3 py-2 rounded-lg text-sm font-bold animate-pulse"
        >
            📡 Menunggu GPS...
        </div>

        <!-- GPS Accuracy Warning -->
        <div
            x-show="gpsReady && accuracy > 20"
            class="absolute top-4 left-4 bg-orange-500 text-white px-3 py-2 rounded-lg text-sm font-bold"
        >
            ⚠️ Akurasi GPS: <span x-text="Math.round(accuracy)"></span>m
        </div>

        <!-- Error Messages -->
        <div
            x-show="errorMessage"
            x-transition
            class="absolute top-4 left-4 right-4 bg-red-500 text-white px-4 py-3 rounded-lg shadow-lg"
        >
            <div class="flex items-start">
                <svg class="w-6 h-6 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span x-text="errorMessage"></span>
            </div>
        </div>

        <!-- Success Message -->
        <div
            x-show="successMessage"
            x-transition
            class="absolute top-4 left-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg"
        >
            <div class="flex items-start">
                <svg class="w-6 h-6 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span x-text="successMessage"></span>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="mt-6 flex flex-col sm:flex-row justify-center items-center gap-4">
        <!-- Capture Button -->
        <button
            @click="capturePhoto()"
            :disabled="!cameraReady || !gpsReady || capturing"
            class="w-full sm:w-auto px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all transform hover:scale-105 active:scale-95 shadow-lg"
            :class="{ 'animate-pulse': capturing }"
        >
            <span x-show="!capturing">📸 Ambil Foto</span>
            <span x-show="capturing">⏳ Memproses...</span>
        </button>

        <!-- Close Button -->
        <button
            @click="closeCamera()"
            class="w-full sm:w-auto px-6 py-4 bg-gray-600 hover:bg-gray-700 text-white rounded-full font-bold transition-all"
        >
            ✕ Tutup
        </button>
    </div>

    <!-- Info Box -->
    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-semibold mb-1">Tips:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Pastikan GPS accuracy < 20m untuk hasil terbaik</li>
                    <li>Foto akan otomatis diberi watermark dengan nama, lokasi, dan waktu</li>
                    <li>Anda harus berada dalam radius 50m dari lokasi kerja</li>
                    <li>Pastikan pencahayaan cukup untuk foto yang jelas</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Hidden Canvas for processing -->
    <canvas x-ref="canvas" style="display: none;"></canvas>
</div>

@push('scripts')
<script>
function watermarkCamera(petugasName, lokasiName, photoType) {
    return {
        // Camera state
        stream: null,
        cameraReady: false,
        gpsReady: false,
        capturing: false,

        // User Data
        petugasName: petugasName,
        lokasiName: lokasiName,
        photoType: photoType,

        // GPS Data
        latitude: 0,
        longitude: 0,
        accuracy: 0,
        address: '',

        // Time
        currentDateTime: '',
        timeInterval: null,

        // Messages
        errorMessage: '',
        successMessage: '',

        async init() {
            console.log('Initializing watermark camera...');

            // Start time update
            this.updateDateTime();
            this.timeInterval = setInterval(() => this.updateDateTime(), 1000);

            // Request permissions and start camera
            await this.startCamera();
            await this.getGPSLocation();
        },

        async startCamera() {
            try {
                console.log('Requesting camera access...');

                // Request camera with back camera preference
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: 'environment' }, // Prefer back camera
                        width: { ideal: 1920 },
                        height: { ideal: 1080 }
                    },
                    audio: false
                });

                this.$refs.video.srcObject = this.stream;
                this.cameraReady = true;
                console.log('Camera ready!');

            } catch (error) {
                console.error('Camera error:', error);
                this.errorMessage = 'Tidak bisa mengakses kamera. Pastikan izin kamera diberikan.';

                // Auto-hide error after 5 seconds
                setTimeout(() => { this.errorMessage = ''; }, 5000);
            }
        },

        async getGPSLocation() {
            if (!navigator.geolocation) {
                this.errorMessage = 'GPS tidak didukung di browser ini.';
                return;
            }

            console.log('Getting GPS location...');

            // Watch position for continuous updates
            navigator.geolocation.watchPosition(
                (position) => {
                    this.latitude = position.coords.latitude;
                    this.longitude = position.coords.longitude;
                    this.accuracy = position.coords.accuracy;
                    this.gpsReady = true;

                    console.log(`GPS: ${this.latitude}, ${this.longitude}, accuracy: ${this.accuracy}m`);

                    // Get address from reverse geocoding (optional)
                    // this.getAddress();
                },
                (error) => {
                    console.error('GPS error:', error);
                    this.errorMessage = 'Tidak bisa mendapatkan lokasi GPS. Pastikan izin lokasi diberikan dan GPS aktif.';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
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
            if (this.capturing) return;

            this.capturing = true;
            this.errorMessage = '';
            this.successMessage = '';

            try {
                const video = this.$refs.video;
                const canvas = this.$refs.canvas;
                const ctx = canvas.getContext('2d');

                // Set canvas size to video dimensions
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                // Draw video frame to canvas
                ctx.drawImage(video, 0, 0);

                // Draw watermark overlay
                this.drawWatermark(ctx, canvas.width, canvas.height);

                // Convert to blob
                const blob = await new Promise(resolve => {
                    canvas.toBlob(resolve, 'image/jpeg', 0.95);
                });

                // Convert blob to base64
                const photoData = await this.blobToBase64(blob);

                // Get device info
                const deviceData = {
                    model: navigator.userAgent,
                    os: navigator.platform,
                    agent: navigator.userAgent,
                    screen: `${screen.width}x${screen.height}`,
                    network: navigator.connection ? navigator.connection.effectiveType : 'unknown'
                };

                // Prepare GPS data
                const gpsData = {
                    latitude: this.latitude,
                    longitude: this.longitude,
                    accuracy: this.accuracy,
                    address: this.address
                };

                console.log('Sending photo to server...');

                // Send to Livewire component
                @this.call('capturePhoto', photoData, gpsData, deviceData);

            } catch (error) {
                console.error('Capture error:', error);
                this.errorMessage = 'Gagal mengambil foto: ' + error.message;
                this.capturing = false;
            }
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
            ctx.font = 'bold 24px Inter, Arial, sans-serif';

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
            ctx.fillText(`✓ Verified • GPS Accuracy: ${Math.round(this.accuracy)}m`, padding, y);
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
            // Stop camera stream
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }

            // Clear interval
            if (this.timeInterval) {
                clearInterval(this.timeInterval);
            }

            // Dispatch close event
            this.$dispatch('camera-closed');

            // Optionally close modal/drawer
            // You can emit event to parent component
        },

        handleError(detail) {
            this.errorMessage = detail.message;
            this.capturing = false;

            // Auto-hide after 5 seconds
            setTimeout(() => { this.errorMessage = ''; }, 5000);
        },

        handleSuccess(detail) {
            this.successMessage = 'Foto berhasil disimpan! Confidence score: ' + detail.metadata.confidence_score;
            this.capturing = false;

            // Auto-hide and close after 2 seconds
            setTimeout(() => {
                this.successMessage = '';
                this.closeCamera();
            }, 2000);
        }
    }
}
</script>
@endpush
