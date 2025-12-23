<x-filament-panels::page>
    <div class="space-y-4">
        @if(!$scannedLokasi)
            <!-- Scanner Section -->
            <x-filament::section>
                <x-slot name="heading">
                    Scanner QR Code
                </x-slot>

                <x-slot name="description">
                    Arahkan kamera ke QR Code lokasi untuk memindai
                </x-slot>

                <div class="space-y-4">
                    <!-- Video Preview -->
                    <div class="relative bg-black rounded-lg overflow-hidden" style="max-width: 500px; margin: 0 auto;">
                        <video id="qr-video" class="w-full" style="max-height: 500px;"></video>

                        <div id="qr-scanning-region" class="absolute inset-0 pointer-events-none">
                            <div class="absolute inset-0 border-4 border-green-500 m-12 rounded-lg animate-pulse"></div>
                        </div>
                    </div>

                    <!-- Camera Selection -->
                    <div class="flex flex-col items-center gap-3">
                        <select id="camera-select" class="fi-input block w-full max-w-xs rounded-lg border-gray-300">
                            <option value="">Memilih kamera...</option>
                        </select>

                        <div class="flex gap-2">
                            <x-filament::button
                                id="start-button"
                                color="success"
                                icon="heroicon-o-play"
                            >
                                Mulai Scan
                            </x-filament::button>

                            <x-filament::button
                                id="stop-button"
                                color="danger"
                                icon="heroicon-o-stop"
                                style="display: none;"
                            >
                                Berhenti
                            </x-filament::button>
                        </div>
                    </div>

                    <!-- Status -->
                    <div id="scan-status" class="text-center text-sm text-gray-600">
                        Klik "Mulai Scan" untuk memulai
                    </div>
                </div>
            </x-filament::section>
        @else
            <!-- Scanned Result Section -->
            <x-filament::section>
                <x-slot name="heading">
                    Hasil Scan
                </x-slot>

                <div class="space-y-4">
                    @if($hasJadwal)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 shrink-0 mt-1"/>
                                <div>
                                    <h3 class="text-lg font-semibold text-green-900">QR Code Berhasil Dipindai!</h3>
                                    <p class="text-sm text-green-700 mt-1">Lokasi berhasil diidentifikasi dan Anda memiliki jadwal untuk lokasi ini</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-yellow-600 shrink-0 mt-1"/>
                                <div>
                                    <h3 class="text-lg font-semibold text-yellow-900">Akses Ditolak</h3>
                                    <p class="text-sm text-yellow-800 mt-1">Anda tidak memiliki jadwal untuk lokasi ini hari ini</p>
                                    <p class="text-sm text-yellow-700 mt-2">
                                        <strong>Silakan hubungi supervisor Anda</strong> jika Anda merasa ini adalah kesalahan atau jika ada perubahan jadwal yang belum terupdate.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Jadwal Info (if exists) -->
                    @if($hasJadwal && $jadwal)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-calendar class="w-5 h-5 text-blue-600 shrink-0 mt-1"/>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-blue-900 mb-2">Jadwal Anda Hari Ini</h4>
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <span class="text-blue-700">Shift:</span>
                                            <span class="font-medium text-blue-900 ml-1 capitalize">{{ $jadwal->shift }}</span>
                                        </div>
                                        <div>
                                            <span class="text-blue-700">Jam:</span>
                                            <span class="font-medium text-blue-900 ml-1">
                                                {{ $jadwal->jam_mulai?->format('H:i') }} - {{ $jadwal->jam_selesai?->format('H:i') }}
                                            </span>
                                        </div>
                                        @if($jadwal->prioritas)
                                            <div class="col-span-2">
                                                <span class="text-blue-700">Prioritas:</span>
                                                <span class="px-2 py-0.5 rounded text-xs font-medium ml-1
                                                    {{ $jadwal->prioritas === 'tinggi' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $jadwal->prioritas === 'sedang' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $jadwal->prioritas === 'rendah' ? 'bg-green-100 text-green-800' : '' }}
                                                ">
                                                    {{ ucfirst($jadwal->prioritas) }}
                                                </span>
                                            </div>
                                        @endif
                                        @if($jadwal->catatan)
                                            <div class="col-span-2">
                                                <span class="text-blue-700">Catatan:</span>
                                                <p class="text-blue-800 mt-1">{{ $jadwal->catatan }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Lokasi Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white border rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500">Kode Lokasi</div>
                            <div class="text-lg font-bold text-gray-900 mt-1 font-mono">{{ $scannedLokasi->kode_lokasi }}</div>
                        </div>

                        <div class="bg-white border rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500">Nama Lokasi</div>
                            <div class="text-lg font-semibold text-gray-900 mt-1">{{ $scannedLokasi->nama_lokasi }}</div>
                        </div>

                        <div class="bg-white border rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500">Kategori</div>
                            <div class="text-lg text-gray-900 mt-1 capitalize">{{ str_replace('_', ' ', $scannedLokasi->kategori) }}</div>
                        </div>

                        <div class="bg-white border rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500">Status Kebersihan</div>
                            <div class="mt-1">
                                <span class="px-3 py-1 rounded-full text-sm font-medium
                                    {{ $scannedLokasi->status_kebersihan === 'bersih' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $scannedLokasi->status_kebersihan === 'kotor' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $scannedLokasi->status_kebersihan === 'belum_dicek' ? 'bg-gray-100 text-gray-800' : '' }}
                                ">
                                    {{ str_replace('_', ' ', ucwords($scannedLokasi->status_kebersihan, '_')) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($scannedLokasi->deskripsi)
                        <div class="bg-white border rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500 mb-2">Deskripsi</div>
                            <div class="text-gray-700">{{ $scannedLokasi->deskripsi }}</div>
                        </div>
                    @endif

                    <div class="flex gap-3 justify-center pt-4">
                        @if($hasJadwal)
                            <x-filament::button
                                wire:click="createReport"
                                color="success"
                                icon="heroicon-o-document-text"
                                size="lg"
                            >
                                Buat Laporan Kegiatan
                            </x-filament::button>
                        @endif

                        <x-filament::button
                            wire:click="resetScanner"
                            color="gray"
                            icon="heroicon-o-arrow-path"
                        >
                            Scan Lagi
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif

        <!-- Instructions -->
        <x-filament::section>
            <x-slot name="heading">
                Cara Penggunaan
            </x-slot>

            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600">
                <li>Izinkan akses kamera saat diminta oleh browser</li>
                <li>Pilih kamera yang akan digunakan (kamera belakang direkomendasikan)</li>
                <li>Klik tombol "Mulai Scan"</li>
                <li>Arahkan kamera ke QR Code lokasi</li>
                <li>Tunggu hingga QR Code terdeteksi (akan otomatis scan)</li>
                <li>Setelah berhasil, informasi lokasi akan ditampilkan</li>
                <li>Klik "Buat Laporan Kegiatan" untuk melanjutkan atau "Scan Lagi" untuk scan lokasi lain</li>
            </ol>
        </x-filament::section>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let html5QrCode = null;
                const qrRegionId = "qr-video";
                const startButton = document.getElementById('start-button');
                const stopButton = document.getElementById('stop-button');
                const cameraSelect = document.getElementById('camera-select');
                const statusDiv = document.getElementById('scan-status');

                // Get available cameras
                Html5Qrcode.getCameras().then(devices => {
                    if (devices && devices.length) {
                        cameraSelect.innerHTML = '<option value="">Pilih Kamera</option>';
                        devices.forEach((device, index) => {
                            const option = document.createElement('option');
                            option.value = device.id;
                            option.text = device.label || `Kamera ${index + 1}`;
                            cameraSelect.appendChild(option);

                            // Auto-select back camera (usually has 'back' or 'rear' in label)
                            if (device.label.toLowerCase().includes('back') ||
                                device.label.toLowerCase().includes('rear') ||
                                index === devices.length - 1) {
                                option.selected = true;
                            }
                        });
                    }
                }).catch(err => {
                    console.error('Error getting cameras:', err);
                    statusDiv.textContent = 'Error: Tidak dapat mengakses kamera';
                    statusDiv.className = 'text-center text-sm text-red-600';
                });

                // Start scanning
                startButton.addEventListener('click', function() {
                    const cameraId = cameraSelect.value;
                    if (!cameraId) {
                        alert('Pilih kamera terlebih dahulu');
                        return;
                    }

                    html5QrCode = new Html5Qrcode(qrRegionId);

                    const config = {
                        fps: 10,
                        qrbox: { width: 250, height: 250 },
                        aspectRatio: 1.0
                    };

                    html5QrCode.start(
                        cameraId,
                        config,
                        (decodedText, decodedResult) => {
                            // QR Code detected
                            console.log(`QR Code detected: ${decodedText}`);

                            // Stop scanning
                            html5QrCode.stop().then(() => {
                                startButton.style.display = 'inline-flex';
                                stopButton.style.display = 'none';
                                statusDiv.textContent = 'Scan selesai!';
                                statusDiv.className = 'text-center text-sm text-green-600';

                                // Send to Livewire
                                @this.call('handleQRScanned', decodedText);
                            });
                        },
                        (errorMessage) => {
                            // Scanning error (not necessarily an issue)
                            // console.log(`Scanning: ${errorMessage}`);
                        }
                    ).then(() => {
                        startButton.style.display = 'none';
                        stopButton.style.display = 'inline-flex';
                        statusDiv.textContent = 'Scanning... Arahkan kamera ke QR Code';
                        statusDiv.className = 'text-center text-sm text-blue-600';
                    }).catch(err => {
                        console.error('Start failed:', err);
                        statusDiv.textContent = 'Error: ' + err;
                        statusDiv.className = 'text-center text-sm text-red-600';
                    });
                });

                // Stop scanning
                stopButton.addEventListener('click', function() {
                    if (html5QrCode) {
                        html5QrCode.stop().then(() => {
                            startButton.style.display = 'inline-flex';
                            stopButton.style.display = 'none';
                            statusDiv.textContent = 'Scanning dihentikan';
                            statusDiv.className = 'text-center text-sm text-gray-600';
                        });
                    }
                });
            });
        </script>
    @endpush
</x-filament-panels::page>
