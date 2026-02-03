@php
    $statePath = $getStatePath();
    $photoType = $getPhotoType();
    $lokasiId = $getLokasiId();
    $activityReportId = $getActivityReportId();
    $maxPhotos = $getMaxPhotos();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="watermarkCameraField('{{ $statePath }}', '{{ $photoType }}', {{ $lokasiId ?? 'null' }}, {{ $activityReportId ?? 'null' }}, {{ $maxPhotos }})"
        x-init="init()"
        class="space-y-4"
    >
        <!-- Camera Button -->
        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="openCamera()"
                :disabled="!canAddMorePhotos()"
                class="inline-flex items-center justify-center gap-2.5 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                style="background-color: #2563eb !important; color: white !important;"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-white font-medium">Ambil Foto</span>
            </button>

            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span x-text="capturedPhotos.length"></span>/<span x-text="maxPhotos"></span> foto
            </div>
        </div>

        <!-- Captured Photos Grid -->
        <div
            x-show="capturedPhotos && capturedPhotos.length > 0"
            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3"
        >
            <template x-for="(photo, index) in capturedPhotos" :key="index">
                <div class="relative group border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow">
                    <div class="aspect-square relative">
                        <img :src="photo.url" :alt="'Foto ' + (index + 1)" class="w-full h-full object-cover">

                        <!-- Photo Number Badge -->
                        <div class="absolute bottom-2 left-2 px-2 py-0.5 bg-black/70 text-white text-xs rounded">
                            #<span x-text="index + 1"></span>
                        </div>

                        <!-- Remove Button - Always visible on mobile, hover on desktop -->
                        <button
                            @click.stop.prevent="removePhoto(index)"
                            type="button"
                            class="absolute top-2 right-2 p-1.5 bg-red-600 hover:bg-red-700 text-white rounded-full shadow-lg sm:opacity-0 sm:group-hover:opacity-100 transition-opacity"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
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
                <p>Maksimal <span x-text="maxPhotos"></span> foto. Foto akan diberi watermark otomatis.</p>
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
                                Ambil Foto {{ $photoType === 'before' ? 'Sebelum' : 'Sesudah' }}
                                (<span x-text="capturedPhotos.length"></span>/<span x-text="maxPhotos"></span>)
                            </h3>
                            <button @click="closeCamera()" type="button" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Camera UI -->
                        <div class="p-6">
                            <template x-if="!lokasiId">
                                <div class="text-center py-8">
                                    <p class="text-gray-600 dark:text-gray-400">Pilih lokasi terlebih dahulu</p>
                                    <button @click="closeCamera()" class="mt-4 px-4 py-2 bg-gray-600 text-white rounded">Tutup</button>
                                </div>
                            </template>

                            <template x-if="lokasiId">
                                <div>
                                    <!-- Camera Settings -->
                                    <div class="flex flex-wrap items-center justify-center gap-4 mb-4">
                                        <!-- Camera Selection -->
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Kamera:</span>
                                            <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                                                <button
                                                    type="button"
                                                    @click="switchCamera('environment')"
                                                    :class="facingMode === 'environment' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                                    class="px-3 py-1.5 text-sm font-medium transition-colors"
                                                >
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                    Belakang
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="switchCamera('user')"
                                                    :class="facingMode === 'user' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                                    class="px-3 py-1.5 text-sm font-medium transition-colors border-l border-gray-300 dark:border-gray-600"
                                                >
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                    Depan
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Timer Selection -->
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Timer:</span>
                                            <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                                                <button
                                                    type="button"
                                                    @click="timerSeconds = 0"
                                                    :class="timerSeconds === 0 ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                                    class="px-3 py-1.5 text-sm font-medium transition-colors"
                                                >
                                                    Off
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="timerSeconds = 5"
                                                    :class="timerSeconds === 5 ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                                    class="px-3 py-1.5 text-sm font-medium transition-colors border-l border-gray-300 dark:border-gray-600"
                                                >
                                                    5s
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="timerSeconds = 10"
                                                    :class="timerSeconds === 10 ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                                    class="px-3 py-1.5 text-sm font-medium transition-colors border-l border-gray-300 dark:border-gray-600"
                                                >
                                                    10s
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Video Preview -->
                                    <div class="relative bg-black rounded-lg overflow-hidden" style="max-height: 60vh;">
                                        <video x-ref="video" autoplay playsinline class="w-full h-auto" :class="facingMode === 'user' ? 'scale-x-[-1]' : ''" x-show="cameraReady"></video>

                                        <div x-show="!cameraReady" class="flex items-center justify-center h-96">
                                            <p class="text-white">Memulai kamera...</p>
                                        </div>

                                        <!-- Timer Countdown Overlay -->
                                        <div x-show="timerCountdown > 0" class="absolute inset-0 flex items-center justify-center bg-black/50">
                                            <span class="text-9xl font-bold text-white animate-pulse" x-text="timerCountdown"></span>
                                        </div>

                                        <!-- Live Watermark -->
                                        <div x-show="cameraReady && timerCountdown === 0" class="absolute bottom-0 left-0 right-0 bg-black/85 text-white p-4">
                                            <div class="space-y-1 text-sm">
                                                <div><span x-text="petugasName"></span></div>
                                                <div><span x-text="lokasiName"></span></div>
                                                <div><span x-text="currentDateTime"></span></div>
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
                                            @click="startCaptureWithTimer()"
                                            :disabled="!cameraReady || capturing || timerCountdown > 0 || !canAddMorePhotos()"
                                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                            style="background-color: #2563eb !important; color: white !important;"
                                        >
                                            <svg x-show="timerSeconds > 0 && timerCountdown === 0" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span x-show="!capturing && timerCountdown === 0">
                                                <span x-show="timerSeconds === 0">Ambil Foto</span>
                                                <span x-show="timerSeconds > 0">Mulai Timer (<span x-text="timerSeconds"></span>s)</span>
                                            </span>
                                            <span x-show="capturing">Memproses...</span>
                                        </button>
                                        <button @click="closeCamera()" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold" style="background-color: #4b5563 !important; color: white !important;">Selesai</button>
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

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('watermarkCameraField', (statePath, photoType, lokasiId, activityReportId, maxPhotos) => ({
        statePath: statePath,
        photoType: photoType,
        lokasiId: lokasiId,
        activityReportId: activityReportId,
        maxPhotos: maxPhotos,
        showCamera: false,
        capturedPhotos: [],
        stream: null,
        cameraReady: false,
        // gpsReady removed - GPS no longer required
        capturing: false,
        petugasName: '',
        lokasiName: '',
        currentDateTime: '',
        timeInterval: null,
        errorMessage: '',
        successMessage: '',
        // New camera features
        facingMode: 'environment', // 'environment' = back camera, 'user' = front camera
        timerSeconds: 0, // 0 = no timer, 5 = 5 seconds, 10 = 10 seconds
        timerCountdown: 0,
        timerInterval: null,

        init() {
            console.log('Camera field initialized for', this.photoType);
            this.updateDateTime();
            this.timeInterval = setInterval(() => this.updateDateTime(), 1000);
            this.loadExistingPhotos();

            // Watch for Livewire state changes
            this.$wire.$watch(this.statePath, (value) => {
                console.log('Livewire state changed for', this.photoType, ':', value);
                this.syncPhotosFromState(value);
            });
        },

        loadExistingPhotos() {
            const existingData = this.$wire.get(this.statePath);
            console.log('Loading photos for', this.photoType, ':', existingData);
            this.syncPhotosFromState(existingData);
        },

        syncPhotosFromState(data) {
            if (!data) {
                this.capturedPhotos = [];
                return;
            }

            if (Array.isArray(data)) {
                // Only update if different to prevent infinite loops
                const currentPaths = this.capturedPhotos.map(p => p.path).join(',');
                const newPaths = data.join(',');
                if (currentPaths !== newPaths) {
                    this.capturedPhotos = data.map((path) => ({
                        path: path,
                        url: '/storage/' + path,
                        metadata_id: null,
                        confidence_score: 100,
                        file_size: null
                    }));
                }
            } else if (typeof data === 'string' && data.length > 0) {
                this.capturedPhotos = [{
                    path: data,
                    url: '/storage/' + data,
                    metadata_id: null,
                    confidence_score: 100,
                    file_size: null
                }];
                this.$wire.set(this.statePath, [data]);
            }
        },

        getCurrentLokasiId() {
            const lokasiFromState = this.$wire.get('data.lokasi_id');
            return lokasiFromState || this.lokasiId;
        },

        updateDateTime() {
            const now = new Date();
            this.currentDateTime = now.toLocaleString('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'medium'
            });
        },

        canAddMorePhotos() {
            return this.capturedPhotos.length < this.maxPhotos;
        },

        async openCamera() {
            if (!this.canAddMorePhotos()) {
                alert('Maksimal ' + this.maxPhotos + ' foto');
                return;
            }

            const currentLokasiId = this.getCurrentLokasiId();
            if (!currentLokasiId) {
                alert('Pilih lokasi terlebih dahulu');
                return;
            }

            this.lokasiId = currentLokasiId;
            this.showCamera = true;
            this.errorMessage = '';
            this.successMessage = '';

            await this.loadLokasiInfo();
            await this.startCamera();
            // GPS no longer required - location is selected from dropdown
        },

        async loadLokasiInfo() {
            const currentLokasiId = this.getCurrentLokasiId();
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                const response = await fetch('/api/camera/lokasi/' + currentLokasiId, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.content : ''
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
                // Stop existing stream if any
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                }

                this.cameraReady = false;

                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: this.facingMode },
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

        async switchCamera(mode) {
            if (this.facingMode === mode) return;

            this.facingMode = mode;
            await this.startCamera();
        },

        startCaptureWithTimer() {
            // Clear any existing timer
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }

            if (this.timerSeconds === 0) {
                // No timer, capture immediately
                this.capturePhoto();
            } else {
                // Start countdown
                this.timerCountdown = this.timerSeconds;

                // Use arrow function to preserve 'this' context properly with Alpine.js
                const tick = () => {
                    this.timerCountdown--;
                    console.log('Timer countdown:', this.timerCountdown, 'GPS:', this.gpsReady, 'Camera:', this.cameraReady);

                    if (this.timerCountdown <= 0) {
                        clearInterval(this.timerInterval);
                        this.timerInterval = null;

                        // Check conditions before capture
                        console.log('Timer done. Capturing... GPS:', this.gpsReady, 'Camera:', this.cameraReady, 'Capturing:', this.capturing);

                        if (this.cameraReady && !this.capturing) {
                            this.capturePhoto();
                        } else {
                            this.errorMessage = 'Gagal mengambil foto: Kamera tidak siap';
                            console.error('Capture conditions not met:', {
                                cameraReady: this.cameraReady,
                                capturing: this.capturing
                            });
                        }
                    }
                };

                this.timerInterval = setInterval(tick, 1000);
            }
        },

        // startGPS removed - GPS no longer required, location is selected from dropdown

        async capturePhoto() {
            if (!this.cameraReady || this.capturing) return;
            if (!this.canAddMorePhotos()) {
                this.errorMessage = 'Maksimal ' + this.maxPhotos + ' foto';
                return;
            }

            this.capturing = true;
            this.errorMessage = '';

            try {
                const video = this.$refs.video;
                const canvas = this.$refs.canvas;
                const ctx = canvas.getContext('2d');

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                // If using front camera, flip the image horizontally to match what user sees
                if (this.facingMode === 'user') {
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                }

                ctx.drawImage(video, 0, 0);

                // Reset transformation for watermark
                ctx.setTransform(1, 0, 0, 1, 0, 0);

                const overlayHeight = 100;
                ctx.fillStyle = 'rgba(0, 0, 0, 0.85)';
                ctx.fillRect(0, canvas.height - overlayHeight, canvas.width, overlayHeight);

                ctx.fillStyle = '#FFFFFF';
                ctx.font = 'bold 24px Arial';
                let y = canvas.height - overlayHeight + 35;
                ctx.fillText(this.petugasName, 20, y); y += 28;
                ctx.fillText(this.lokasiName, 20, y); y += 28;
                ctx.fillText(this.currentDateTime, 20, y);

                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.95));
                const reader = new FileReader();
                const photoData = await new Promise((resolve) => {
                    reader.onloadend = () => resolve(reader.result);
                    reader.readAsDataURL(blob);
                });

                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                const response = await fetch('/api/camera/capture', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.content : ''
                    },
                    body: JSON.stringify({
                        photo_data: photoData,
                        gps_data: null, // GPS no longer required - location selected from dropdown
                        device_data: {
                            model: this.getDeviceModel(),
                            os: navigator.platform,
                            agent: navigator.userAgent,
                            screen: screen.width + 'x' + screen.height,
                            network: navigator.connection ? navigator.connection.effectiveType : 'unknown'
                        },
                        lokasi_id: this.lokasiId,
                        photo_type: this.photoType,
                        activity_report_id: this.activityReportId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Create new array to trigger Alpine reactivity
                    const newPhoto = {
                        path: result.path,
                        url: result.url,
                        metadata_id: result.metadata.id,
                        confidence_score: result.metadata.confidence_score,
                        file_size: result.metadata.file_size
                    };
                    this.capturedPhotos = [...this.capturedPhotos, newPhoto];

                    const paths = this.capturedPhotos.map(p => p.path);
                    this.$wire.set(this.statePath, paths);
                    console.log('Photos saved:', this.statePath, paths, 'Total:', this.capturedPhotos.length);

                    this.successMessage = 'Foto ' + this.capturedPhotos.length + '/' + this.maxPhotos + ' berhasil!';

                    if (!this.canAddMorePhotos()) {
                        setTimeout(() => {
                            this.closeCamera();
                        }, 1500);
                    } else {
                        setTimeout(() => {
                            this.successMessage = '';
                        }, 2000);
                    }
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
            // Create new array to trigger Alpine reactivity
            this.capturedPhotos = this.capturedPhotos.filter((_, i) => i !== index);

            if (this.capturedPhotos.length === 0) {
                this.$wire.set(this.statePath, null);
            } else {
                const paths = this.capturedPhotos.map(p => p.path);
                this.$wire.set(this.statePath, paths);
            }
        },

        closeCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }

            // Clear timer if running
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
            this.timerCountdown = 0;

            this.showCamera = false;
            this.cameraReady = false;
            this.errorMessage = '';
            this.successMessage = '';
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
    }));
});
</script>
@endpush
@endonce
