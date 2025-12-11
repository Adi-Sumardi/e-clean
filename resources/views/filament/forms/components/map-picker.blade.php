<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php
        $latitude = $getRecord()?->latitude ?? $getDefaultLatitude();
        $longitude = $getRecord()?->longitude ?? $getDefaultLongitude();
        $zoom = $getDefaultZoom();
        $height = $getHeight();
        $uniqueId = 'map-' . uniqid();
    @endphp

    {{-- Load Map Picker JS inline to ensure it works in modals --}}
    <script>
        if (typeof window.initMapPicker === 'undefined') {
            window.initMapPicker = function(lat, lng, zoom, mapId) {
                return {
                    map: null,
                    marker: null,
                    latitude: lat,
                    longitude: lng,
                    zoom: zoom,
                    searchQuery: '',
                    isLoading: false,
                    gpsStatus: 'unknown',
                    mapId: mapId,
                    leafletLoaded: false,

                    init() {
                        this.checkGpsPermission();
                        this.loadLeafletAndInit();
                    },

                    loadLeafletAndInit() {
                        if (typeof L !== 'undefined') {
                            this.leafletLoaded = true;
                            this.$nextTick(() => this.initMap());
                            return;
                        }

                        if (!document.querySelector('link[href*="leaflet"]')) {
                            const link = document.createElement('link');
                            link.rel = 'stylesheet';
                            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                            document.head.appendChild(link);
                        }

                        if (!document.querySelector('script[src*="leaflet"]')) {
                            const script = document.createElement('script');
                            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                            script.onload = () => {
                                this.leafletLoaded = true;
                                this.$nextTick(() => this.initMap());
                            };
                            document.head.appendChild(script);
                        } else {
                            const check = setInterval(() => {
                                if (typeof L !== 'undefined') {
                                    clearInterval(check);
                                    this.leafletLoaded = true;
                                    this.$nextTick(() => this.initMap());
                                }
                            }, 100);
                        }
                    },

                    async checkGpsPermission() {
                        if (!navigator.permissions) {
                            this.gpsStatus = 'unsupported';
                            return;
                        }
                        try {
                            const result = await navigator.permissions.query({ name: 'geolocation' });
                            this.gpsStatus = result.state;
                            result.onchange = () => { this.gpsStatus = result.state; };
                        } catch (e) {
                            this.gpsStatus = 'unknown';
                        }
                    },

                    initMap() {
                        const container = document.getElementById(this.mapId);
                        if (!container || this.map) return;

                        try {
                            this.map = L.map(container).setView([this.latitude, this.longitude], this.zoom);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '© OpenStreetMap'
                            }).addTo(this.map);

                            this.marker = L.marker([this.latitude, this.longitude], { draggable: true }).addTo(this.map);

                            this.marker.on('dragend', (e) => {
                                const pos = e.target.getLatLng();
                                this.updateCoords(pos.lat, pos.lng);
                            });

                            this.map.on('click', (e) => {
                                this.updateCoords(e.latlng.lat, e.latlng.lng);
                                this.marker.setLatLng(e.latlng);
                            });

                            setTimeout(() => this.map.invalidateSize(), 300);
                        } catch (err) {
                            console.error('Map init error:', err);
                        }
                    },

                    updateCoords(lat, lng) {
                        this.latitude = parseFloat(lat);
                        this.longitude = parseFloat(lng);
                        if (this.$wire) {
                            this.$wire.set('data.latitude', this.latitude.toFixed(7));
                            this.$wire.set('data.longitude', this.longitude.toFixed(7));
                        }
                    },

                    async searchLoc() {
                        if (!this.searchQuery.trim()) return;
                        this.isLoading = true;

                        try {
                            const res = await fetch(
                                'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(this.searchQuery) + '&limit=1',
                                { headers: { 'Accept-Language': 'id' } }
                            );
                            const data = await res.json();

                            if (data && data.length > 0) {
                                const r = data[0];
                                const lat = parseFloat(r.lat);
                                const lng = parseFloat(r.lon);

                                this.map.setView([lat, lng], 17);
                                this.marker.setLatLng([lat, lng]);
                                this.updateCoords(lat, lng);

                                if (r.display_name && this.$wire) {
                                    this.$wire.set('data.address', r.display_name);
                                }
                            } else {
                                alert('Lokasi tidak ditemukan.');
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Gagal mencari lokasi.');
                        }
                        this.isLoading = false;
                    },

                    getMyLoc() {
                        if (!navigator.geolocation) {
                            alert('Browser tidak mendukung GPS.');
                            return;
                        }

                        this.isLoading = true;
                        this.gpsStatus = 'requesting';

                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                this.map.setView([pos.coords.latitude, pos.coords.longitude], 17);
                                this.marker.setLatLng([pos.coords.latitude, pos.coords.longitude]);
                                this.updateCoords(pos.coords.latitude, pos.coords.longitude);
                                this.isLoading = false;
                                this.gpsStatus = 'granted';
                            },
                            (err) => {
                                this.isLoading = false;
                                if (err.code === 1) {
                                    this.gpsStatus = 'denied';
                                    alert('Izin lokasi ditolak.');
                                } else {
                                    alert('Gagal mendapatkan lokasi.');
                                }
                            },
                            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                        );
                    }
                };
            };
        }
    </script>

    <div
        x-data="initMapPicker({{ $latitude }}, {{ $longitude }}, {{ $zoom }}, '{{ $uniqueId }}')"
        x-init="init()"
        wire:ignore
        class="space-y-3"
    >
        {{-- GPS Permission Alert --}}
        <div x-show="gpsStatus === 'denied'" x-cloak class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-sm">
                    <p class="font-medium text-red-800 dark:text-red-200">Izin Lokasi Ditolak</p>
                    <p class="text-red-600 dark:text-red-300 mt-1">Klik ikon gembok di address bar browser untuk mengaktifkan izin lokasi.</p>
                </div>
            </div>
        </div>

        {{-- Loading indicator while Leaflet loads --}}
        <div x-show="!leafletLoaded" class="flex items-center justify-center p-8 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700" style="height: {{ $height }}px;">
            <div class="text-center">
                <svg class="animate-spin h-8 w-8 text-primary-500 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 dark:text-gray-400">Memuat peta...</span>
            </div>
        </div>

        {{-- Search Bar --}}
        <div x-show="leafletLoaded" x-cloak class="flex gap-2">
            <input
                type="text"
                x-model="searchQuery"
                x-on:keydown.enter.prevent="searchLoc()"
                placeholder="Cari lokasi... (contoh: Monas Jakarta)"
                class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
            >
            <button
                type="button"
                x-on:click="searchLoc()"
                x-bind:disabled="isLoading"
                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 text-sm font-medium"
            >
                <span x-show="!isLoading">Cari</span>
                <span x-show="isLoading">...</span>
            </button>
            <button
                type="button"
                x-on:click="getMyLoc()"
                x-bind:disabled="isLoading"
                x-bind:class="gpsStatus === 'denied' ? 'bg-gray-400 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700'"
                class="px-4 py-2 text-white rounded-lg disabled:opacity-50 text-sm font-medium flex items-center gap-2"
                x-bind:title="gpsStatus === 'denied' ? 'Izin lokasi ditolak' : 'Ambil lokasi GPS saya'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="hidden sm:inline">GPS</span>
            </button>
        </div>

        {{-- Map Container --}}
        <div
            x-show="leafletLoaded"
            x-cloak
            id="{{ $uniqueId }}"
            class="rounded-lg border border-gray-300 dark:border-gray-700 overflow-hidden"
            style="height: {{ $height }}px; z-index: 1;"
        ></div>

        {{-- Coordinates Display --}}
        <div x-show="leafletLoaded" x-cloak class="flex flex-wrap gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm">
            <div>
                <span class="text-gray-500">Lat:</span>
                <span class="font-mono font-medium" x-text="latitude.toFixed(7)"></span>
            </div>
            <div>
                <span class="text-gray-500">Lng:</span>
                <span class="font-mono font-medium" x-text="longitude.toFixed(7)"></span>
            </div>
            <div class="flex items-center gap-1">
                <span class="text-gray-500">GPS:</span>
                <span x-show="gpsStatus === 'granted'" class="text-emerald-600 font-medium">Diizinkan</span>
                <span x-show="gpsStatus === 'denied'" class="text-red-600 font-medium">Ditolak</span>
                <span x-show="gpsStatus === 'prompt'" class="text-amber-600 font-medium">Belum diizinkan</span>
                <span x-show="gpsStatus === 'requesting'" class="text-blue-600 font-medium">Meminta izin...</span>
                <span x-show="gpsStatus === 'unknown' || gpsStatus === 'unsupported'" class="text-gray-500">-</span>
            </div>
            <a
                x-bind:href="'https://www.google.com/maps?q=' + latitude + ',' + longitude"
                target="_blank"
                class="ml-auto text-primary-600 hover:underline"
            >
                Buka di Google Maps &rarr;
            </a>
        </div>

        <p x-show="leafletLoaded" x-cloak class="text-xs text-gray-500">
            <strong>Cara penggunaan:</strong> Klik pada peta atau drag marker merah untuk memilih lokasi. Gunakan tombol GPS hijau untuk mengambil koordinat dari perangkat Anda.
        </p>
    </div>

    <style>
        .leaflet-container { font-family: inherit; }
        [x-cloak] { display: none !important; }
    </style>
</x-dynamic-component>
