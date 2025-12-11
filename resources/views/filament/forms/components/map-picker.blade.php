<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            map: null,
            marker: null,
            latitude: {{ $getRecord()?->latitude ?? $getDefaultLatitude() }},
            longitude: {{ $getRecord()?->longitude ?? $getDefaultLongitude() }},
            zoom: {{ $getDefaultZoom() }},
            searchQuery: '',
            isLoading: false,

            init() {
                this.$nextTick(() => {
                    this.initMap();
                });
            },

            initMap() {
                // Initialize Leaflet map (OpenStreetMap - free, no API key needed)
                this.map = L.map(this.$refs.mapContainer).setView([this.latitude, this.longitude], this.zoom);

                // Add OpenStreetMap tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'
                }).addTo(this.map);

                // Add marker
                this.marker = L.marker([this.latitude, this.longitude], {
                    draggable: true
                }).addTo(this.map);

                // Update coordinates when marker is dragged
                this.marker.on('dragend', (e) => {
                    const position = e.target.getLatLng();
                    this.updateCoordinates(position.lat, position.lng);
                });

                // Update coordinates when map is clicked
                this.map.on('click', (e) => {
                    this.updateCoordinates(e.latlng.lat, e.latlng.lng);
                    this.marker.setLatLng(e.latlng);
                });

                // Fix map rendering issue
                setTimeout(() => {
                    this.map.invalidateSize();
                }, 100);
            },

            updateCoordinates(lat, lng) {
                this.latitude = parseFloat(lat.toFixed(7));
                this.longitude = parseFloat(lng.toFixed(7));

                // Update Livewire state
                $wire.set('data.latitude', this.latitude);
                $wire.set('data.longitude', this.longitude);
            },

            async searchLocation() {
                if (!this.searchQuery.trim()) return;

                this.isLoading = true;

                try {
                    // Use Nominatim (OpenStreetMap) geocoding API - free
                    const response = await fetch(
                        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}&limit=1`,
                        {
                            headers: {
                                'Accept-Language': 'id'
                            }
                        }
                    );
                    const data = await response.json();

                    if (data && data.length > 0) {
                        const result = data[0];
                        const lat = parseFloat(result.lat);
                        const lng = parseFloat(result.lon);

                        this.map.setView([lat, lng], 17);
                        this.marker.setLatLng([lat, lng]);
                        this.updateCoordinates(lat, lng);

                        // Update address field if exists
                        if (result.display_name) {
                            $wire.set('data.address', result.display_name);
                        }
                    } else {
                        alert('Lokasi tidak ditemukan. Coba kata kunci lain.');
                    }
                } catch (error) {
                    console.error('Search error:', error);
                    alert('Gagal mencari lokasi. Silakan coba lagi.');
                }

                this.isLoading = false;
            },

            getCurrentLocation() {
                if (!navigator.geolocation) {
                    alert('Browser tidak mendukung geolocation');
                    return;
                }

                this.isLoading = true;

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        this.map.setView([lat, lng], 17);
                        this.marker.setLatLng([lat, lng]);
                        this.updateCoordinates(lat, lng);

                        this.isLoading = false;
                    },
                    (error) => {
                        console.error('Geolocation error:', error);
                        alert('Gagal mendapatkan lokasi. Pastikan GPS aktif dan izin lokasi diberikan.');
                        this.isLoading = false;
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            }
        }"
        wire:ignore
        class="space-y-3"
    >
        <!-- Search Bar -->
        <div class="flex gap-2">
            <div class="flex-1 relative">
                <input
                    type="text"
                    x-model="searchQuery"
                    @keydown.enter.prevent="searchLocation()"
                    placeholder="Cari lokasi... (contoh: Monas Jakarta)"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                >
            </div>
            <button
                type="button"
                @click="searchLocation()"
                :disabled="isLoading"
                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors text-sm font-medium flex items-center gap-2"
            >
                <svg x-show="!isLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <svg x-show="isLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Cari</span>
            </button>
            <button
                type="button"
                @click="getCurrentLocation()"
                :disabled="isLoading"
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 transition-colors text-sm font-medium flex items-center gap-2"
                title="Gunakan lokasi saya saat ini"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="hidden sm:inline">Lokasi Saya</span>
            </button>
        </div>

        <!-- Map Container -->
        <div
            x-ref="mapContainer"
            class="rounded-lg border border-gray-300 dark:border-gray-700 overflow-hidden"
            style="height: {{ $getHeight() }}px; z-index: 1;"
        ></div>

        <!-- Coordinate Display -->
        <div class="flex flex-wrap gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm">
            <div class="flex items-center gap-2">
                <span class="text-gray-500 dark:text-gray-400">Latitude:</span>
                <span class="font-mono font-medium text-gray-900 dark:text-white" x-text="latitude"></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-gray-500 dark:text-gray-400">Longitude:</span>
                <span class="font-mono font-medium text-gray-900 dark:text-white" x-text="longitude"></span>
            </div>
            <a
                :href="`https://www.google.com/maps?q=${latitude},${longitude}`"
                target="_blank"
                class="ml-auto text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center gap-1"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Buka di Google Maps
            </a>
        </div>

        <!-- Instructions -->
        <p class="text-xs text-gray-500 dark:text-gray-400">
            <strong>Petunjuk:</strong> Klik pada peta atau drag marker untuk memilih lokasi. Gunakan tombol "Lokasi Saya" untuk mengambil koordinat GPS perangkat Anda.
        </p>
    </div>

    <!-- Leaflet CSS & JS -->
    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .leaflet-container {
            font-family: inherit;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @endpush
</x-dynamic-component>
