@php
    $statePath = $getStatePath();
    $photoType = $getPhotoType();
    $lokasiId = $getLokasiId();
    $activityReportId = $getActivityReportId();
    $componentId = 'cameraField_' . $photoType . '_' . uniqid();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            ___statePath: '{{ $statePath }}',
            ___photoType: '{{ $photoType }}',
            ___lokasiId: {{ $lokasiId ?? 'null' }},
            ___activityReportId: {{ $activityReportId ?? 'null' }},
            showCamera: false,
            capturedPhotos: [],
            stream: null,
            cameraReady: false,
            gpsReady: false,
            capturing: false,
            lokasiId: {{ $lokasiId ?? 'null' }},
            activityReportId: {{ $activityReportId ?? 'null' }},
            photoType: '{{ $photoType }}',
            petugasName: '',
            lokasiName: '',
            latitude: 0,
            longitude: 0,
            accuracy: 0,
            currentDateTime: '',
            gpsWatcher: null,
            timeInterval: null,
            errorMessage: '',
            successMessage: '',

            init() {
                console.log('Camera field initialized for {{ $photoType }}');
                this.updateDateTime();
                this.timeInterval = setInterval(() => this.updateDateTime(), 1000);

                // Load existing photo from Livewire state
                const existingPath = $wire.get(this.___statePath);
                if (existingPath && typeof existingPath === 'string') {
                    // Convert string path to array format for display
                    this.capturedPhotos = [{
                        path: existingPath,
                        url: '/storage/' + existingPath,
                        metadata_id: null,
                        confidence_score: null,
                        file_size: null
                    }];
                    console.log('Loaded existing photo:', existingPath);
                }
            },

            // Get lokasi_id from Livewire state (reactive)
            getCurrentLokasiId() {
                // First try to get from Livewire state
                const lokasiFromState = $wire.get('data.lokasi_id');
                if (lokasiFromState) {
                    return lokasiFromState;
                }
                // Fallback to initial value
                return this.___lokasiId;
            },

            updateDateTime() {
                const now = new Date();
                this.currentDateTime = now.toLocaleString('id-ID', {
                    dateStyle: 'medium',
                    timeStyle: 'medium'
                });
            },

            async openCamera() {
                const currentLokasiId = this.getCurrentLokasiId();
                if (!currentLokasiId) {
                    alert('Pilih lokasi terlebih dahulu');
                    return;
                }

                // Update the internal lokasi ID from Livewire state
                this.___lokasiId = currentLokasiId;

                this.showCamera = true;
                this.errorMessage = '';
                this.successMessage = '';

                await this.loadLokasiInfo();
                await this.startCamera();
                await this.startGPS();
            },

            async loadLokasiInfo() {
                const currentLokasiId = this.getCurrentLokasiId();
                try {
                    const response = await fetch('/api/camera/lokasi/' + currentLokasiId, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.petugasName = data.petugas_name;
                        this.lokasiName = data.lokasi_name;
                    }
                } catch (error) {
                    console.error('Failed to load lokasi info:', error);
                }
            },

            async startCamera() {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: { ideal: 'environment' },
                            width: { ideal: 1920 },
                            height: { ideal: 1080 }
                        },
                        audio: false
                    });
                    this.$refs.video.srcObject = this.stream;
                    this.cameraReady = true;
                } catch (error) {
                    this.errorMessage = 'Tidak bisa mengakses kamera: ' + error.message;
                    console.error('Camera error:', error);
                }
            },

            async startGPS() {
                if (!navigator.geolocation) {
                    this.errorMessage = 'GPS tidak didukung oleh browser Anda';
                    return;
                }

                this.gpsWatcher = navigator.geolocation.watchPosition(
                    (position) => {
                        this.latitude = position.coords.latitude;
                        this.longitude = position.coords.longitude;
                        this.accuracy = position.coords.accuracy;
                        this.gpsReady = true;
                        this.errorMessage = '';
                    },
                    (error) => {
                        console.error('GPS error:', error);
                        // Show warning but allow camera to work
                        if (error.code === 3) {
                            this.errorMessage = '⚠️ GPS timeout - gunakan mode development (akurasi rendah)';
                            // Use mock GPS for development
                            this.latitude = -6.200000;
                            this.longitude = 106.816666;
                            this.accuracy = 999;
                            this.gpsReady = true;
                        } else if (error.code === 1) {
                            this.errorMessage = 'Izin GPS ditolak. Aktifkan izin lokasi di browser.';
                        } else {
                            this.errorMessage = 'Tidak bisa mendapatkan lokasi GPS: ' + error.message;
                        }
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 30000,
                        maximumAge: 5000
                    }
                );
            },

            async capturePhoto() {
                if (!this.cameraReady || !this.gpsReady || this.capturing) return;

                this.capturing = true;
                this.errorMessage = '';

                try {
                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;
                    const ctx = canvas.getContext('2d');

                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    ctx.drawImage(video, 0, 0);

                    const overlayHeight = 100;
                    ctx.fillStyle = 'rgba(0, 0, 0, 0.85)';
                    ctx.fillRect(0, canvas.height - overlayHeight, canvas.width, overlayHeight);

                    ctx.fillStyle = '#FFFFFF';
                    ctx.font = 'bold 24px Arial';
                    let y = canvas.height - overlayHeight + 35;
                    ctx.fillText('👤 ' + this.petugasName, 20, y); y += 28;
                    ctx.fillText('📍 ' + this.lokasiName, 20, y); y += 28;
                    ctx.fillText('📅 ' + this.currentDateTime, 20, y);

                    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.95));
                    const reader = new FileReader();
                    const photoData = await new Promise((resolve) => {
                        reader.onloadend = () => resolve(reader.result);
                        reader.readAsDataURL(blob);
                    });

                    const response = await fetch('/api/camera/capture', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content
                        },
                        body: JSON.stringify({
                            photo_data: photoData,
                            gps_data: {
                                latitude: this.latitude,
                                longitude: this.longitude,
                                accuracy: this.accuracy
                            },
                            device_data: {
                                model: this.getDeviceModel(),
                                os: navigator.platform,
                                agent: navigator.userAgent,
                                screen: screen.width + 'x' + screen.height,
                                network: navigator.connection?.effectiveType || 'unknown'
                            },
                            lokasi_id: this.___lokasiId,
                            photo_type: this.___photoType,
                            activity_report_id: this.___activityReportId
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.capturedPhotos.push({
                            path: result.path,
                            url: result.url,
                            metadata_id: result.metadata.id,
                            confidence_score: result.metadata.confidence_score,
                            file_size: result.metadata.file_size
                        });

                        // Update Livewire state dengan path foto
                        $wire.set(this.___statePath, result.path);

                        this.successMessage = 'Foto berhasil diambil!';
                        setTimeout(() => {
                            this.closeCamera();
                        }, 2000);
                    } else {
                        this.errorMessage = result.error || 'Gagal mengambil foto';
                    }
                } catch (error) {
                    this.errorMessage = 'Terjadi kesalahan: ' + error.message;
                    console.error('Capture error:', error);
                } finally {
                    this.capturing = false;
                }
            },

            removePhoto(index) {
                this.capturedPhotos.splice(index, 1);

                // Update Livewire state - clear if no photos left
                if (this.capturedPhotos.length === 0) {
                    $wire.set(this.___statePath, null);
                }
            },

            closeCamera() {
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                    this.stream = null;
                }

                if (this.gpsWatcher) {
                    navigator.geolocation.clearWatch(this.gpsWatcher);
                    this.gpsWatcher = null;
                }

                this.showCamera = false;
                this.cameraReady = false;
                this.gpsReady = false;
                this.errorMessage = '';
                this.successMessage = '';
            },

            getConfidenceBadgeColor(score) {
                if (score >= 80) return 'bg-green-500';
                if (score >= 60) return 'bg-yellow-500';
                return 'bg-red-500';
            },

            getDeviceModel() {
                const ua = navigator.userAgent;
                let model = 'Unknown Device';

                if (/iPhone/.test(ua)) {
                    model = 'iPhone';
                } else if (/iPad/.test(ua)) {
                    model = 'iPad';
                } else if (/Android/.test(ua)) {
                    const match = ua.match(/Android[^;]*;\s*([^)]*)\)/);
                    if (match && match[1]) {
                        model = match[1].split(' Build')[0].trim().substring(0, 50);
                    } else {
                        model = 'Android Device';
                    }
                } else if (/Macintosh/.test(ua)) {
                    model = 'Mac';
                } else if (/Windows/.test(ua)) {
                    model = 'Windows PC';
                } else if (/Linux/.test(ua)) {
                    model = 'Linux PC';
                }

                return model.substring(0, 80);
            }
        }"
        class="space-y-4"
    >
        <!-- Camera Button -->
        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="openCamera()"
                class="inline-flex items-center justify-center gap-2.5 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold shadow-sm transition-all"
                style="background-color: #2563eb !important; color: white !important;"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-white font-medium">Ambil Foto dengan Kamera</span>
            </button>

            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span x-show="capturedPhotos && capturedPhotos.length > 0">
                    <span x-text="capturedPhotos.length"></span> foto terekam
                </span>
                <span x-show="!capturedPhotos || capturedPhotos.length === 0">
                    Belum ada foto
                </span>
            </div>
        </div>

        <!-- Captured Photos Grid -->
        <div
            x-show="capturedPhotos && capturedPhotos.length > 0"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
        >
            <template x-for="(photo, index) in capturedPhotos" :key="index">
                <div class="relative group border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow">
                    <div class="aspect-video relative">
                        <img :src="photo.url" :alt="`Photo ${index + 1}`" class="w-full h-full object-cover">

                        <!-- Confidence Badge -->
                        <div
                            class="absolute top-2 right-2 px-2 py-1 rounded-md text-xs font-bold shadow-lg"
                            :class="{
                                'bg-green-500 text-white': photo.confidence_score >= 90,
                                'bg-yellow-500 text-black': photo.confidence_score >= 70 && photo.confidence_score < 90,
                                'bg-red-500 text-white': photo.confidence_score < 70
                            }"
                        >
                            <span x-text="photo.confidence_score >= 90 ? 'Verified' : (photo.confidence_score >= 70 ? 'Good' : 'Low')"></span>
                            <span x-text="Math.round(photo.confidence_score)"></span>%
                        </div>

                        <!-- Remove Button -->
                        <button
                            @click="removePhoto(index)"
                            type="button"
                            class="absolute top-2 left-2 p-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-3 space-y-1 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Foto #<span x-text="index + 1"></span></span>
                            <span class="text-gray-500"><span x-text="(photo.file_size / 1024).toFixed(0)"></span> KB</span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Info Notice -->
        <div class="flex items-start gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-sm">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-blue-800 dark:text-blue-300">
                <p class="font-semibold mb-1">Panduan Foto:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <li>Gunakan kamera langsung (bukan upload dari galeri)</li>
                    <li>Foto otomatis diberi watermark dengan nama petugas, lokasi, dan waktu</li>
                </ul>
            </div>
        </div>

        <!-- Camera Modal -->
        <template x-teleport="body">
            <div
                x-show="showCamera"
                x-transition.opacity
                class="fixed inset-0 z-[99999] overflow-y-auto"
                @keydown.escape.window="closeCamera()"
                style="display: none;"
            >
                <div class="fixed inset-0 bg-black/75" @click="closeCamera()"></div>

                <div class="relative min-h-screen flex items-center justify-center p-4">
                    <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-4xl w-full" @click.stop>
                        <!-- Header -->
                        <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                📷 Ambil Foto - {{ ucfirst($photoType) }}
                            </h3>
                            <button @click="closeCamera()" type="button" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Camera UI -->
                        <div class="p-6">
                            <template x-if="!___lokasiId">
                                <div class="text-center py-8">
                                    <p class="text-gray-600 dark:text-gray-400">Pilih lokasi terlebih dahulu</p>
                                    <button @click="closeCamera()" class="mt-4 px-4 py-2 bg-gray-600 text-white rounded">Tutup</button>
                                </div>
                            </template>

                            <template x-if="___lokasiId">
                                <div>
                                    <!-- Video Preview -->
                                    <div class="relative bg-black rounded-lg overflow-hidden" style="max-height: 60vh;">
                                        <video x-ref="video" autoplay playsinline class="w-full h-auto" x-show="cameraReady"></video>

                                        <div x-show="!cameraReady" class="flex items-center justify-center h-96">
                                            <p class="text-white">Memulai kamera...</p>
                                        </div>

                                        <!-- Live Watermark -->
                                        <div x-show="cameraReady" class="absolute bottom-0 left-0 right-0 bg-black/85 text-white p-4">
                                            <div class="space-y-1 text-sm">
                                                <div>👤 <span x-text="petugasName"></span></div>
                                                <div>📍 <span x-text="lokasiName"></span></div>
                                                <div>📅 <span x-text="currentDateTime"></span></div>
                                            </div>
                                        </div>

                                        <!-- Error/Success Messages -->
                                        <div x-show="errorMessage" class="absolute top-4 left-4 right-4 bg-red-500 text-white px-4 py-3 rounded shadow-lg">
                                            <span x-text="errorMessage"></span>
                                        </div>
                                        <div x-show="successMessage" class="absolute top-4 left-4 right-4 bg-green-500 text-white px-4 py-3 rounded shadow-lg">
                                            <span x-text="successMessage"></span>
                                        </div>
                                    </div>

                                    <br>

                                    <!-- Controls -->
                                    <div class="flex justify-center gap-4">
                                        <button
                                            @click="capturePhoto()"
                                            :disabled="!cameraReady || !gpsReady || capturing"
                                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold disabled:opacity-50 disabled:cursor-not-allowed"
                                            style="background-color: #2563eb !important; color: white !important;"
                                        >
                                            <span x-show="!capturing">📸 Ambil Foto</span>
                                            <span x-show="capturing">⏳ Memproses...</span>
                                        </button>
                                        <button @click="closeCamera()" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold" style="background-color: #4b5563 !important; color: white !important;">Tutup</button>
                                    </div>

                                    <canvas x-ref="canvas" style="display: none;"></canvas>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-dynamic-component>
