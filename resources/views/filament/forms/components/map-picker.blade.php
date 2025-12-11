<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    {{-- Load Leaflet CSS inline --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

    <div
        x-data="mapPickerComponent(@js([
            'latitude' => $getRecord()?->latitude ?? $getDefaultLatitude(),
            'longitude' => $getRecord()?->longitude ?? $getDefaultLongitude(),
            'zoom' => $getDefaultZoom(),
            'height' => $getHeight(),
        ]))"
        x-init="init()"
        wire:ignore.self
        class="space-y-3"
    >
        {{-- Search Bar --}}
        <div class="flex gap-2">
            <div class="flex-1 relative">
                <input
                    type="text"
                    x-model="searchQuery"
                    x-on:keydown.enter.prevent="searchLocation()"
                    placeholder="Cari lokasi... (contoh: Monas Jakarta)"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                >
            </div>
            <button
                type="button"
                x-on:click="searchLocation()"
                x-bind:disabled="isLoading"
                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors text-sm font-medium flex items-center gap-2"
            >
                <template x-if="!isLoading">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </template>
                <template x-if="isLoading">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <span>Cari</span>
            </button>
            <button
                type="button"
                x-on:click="getCurrentLocation()"
                x-bind:disabled="isLoading"
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

        {{-- Map Container --}}
        <div
            x-ref="mapContainer"
            class="rounded-lg border border-gray-300 dark:border-gray-700 overflow-hidden"
            style="height: {{ $getHeight() }}px; z-index: 1;"
        ></div>

        {{-- Coordinate Display --}}
        <div class="flex flex-wrap gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm">
            <div class="flex items-center gap-2">
                <span class="text-gray-500 dark:text-gray-400">Latitude:</span>
                <span class="font-mono font-medium text-gray-900 dark:text-white" x-text="latitude.toFixed(7)"></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-gray-500 dark:text-gray-400">Longitude:</span>
                <span class="font-mono font-medium text-gray-900 dark:text-white" x-text="longitude.toFixed(7)"></span>
            </div>
            <a
                x-bind:href="`https://www.google.com/maps?q=${latitude},${longitude}`"
                target="_blank"
                class="ml-auto text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center gap-1"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Buka di Google Maps
            </a>
        </div>

        {{-- Instructions --}}
        <p class="text-xs text-gray-500 dark:text-gray-400">
            <strong>Petunjuk:</strong> Klik pada peta atau drag marker untuk memilih lokasi. Gunakan tombol "Lokasi Saya" untuk mengambil koordinat GPS perangkat Anda.
        </p>
    </div>

    {{-- Leaflet JS & Component Script --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('mapPickerComponent', (config) => ({
                map: null,
                marker: null,
                latitude: config.latitude || -6.2088,
                longitude: config.longitude || 106.8456,
                zoom: config.zoom || 15,
                searchQuery: '',
                isLoading: false,
                initialized: false,

                init() {
                    if (this.initialized) return;

                    this.$nextTick(() => {
                        setTimeout(() => {
                            this.initMap();
                            this.initialized = true;
                        }, 100);
                    });
                },

                initMap() {
                    const container = this.$refs.mapContainer;
                    if (!container || this.map) return;

                    // Initialize Leaflet map
                    this.map = L.map(container).setView([this.latitude, this.longitude], this.zoom);

                    // Add OpenStreetMap tile layer
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(this.map);

                    // Add draggable marker
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

                    // Fix map rendering issue in modals
                    setTimeout(() => {
                        this.map.invalidateSize();
                    }, 200);
                },

                updateCoordinates(lat, lng) {
                    this.latitude = parseFloat(lat);
                    this.longitude = parseFloat(lng);

                    // Update Livewire/Filament form state
                    this.$wire.set('data.latitude', this.latitude.toFixed(7));
                    this.$wire.set('data.longitude', this.longitude.toFixed(7));
                },

                async searchLocation() {
                    if (!this.searchQuery.trim()) return;

                    this.isLoading = true;

                    try {
                        const response = await fetch(
                            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}&limit=1`,
                            { headers: { 'Accept-Language': 'id' } }
                        );
                        const data = await response.json();

                        if (data && data.length > 0) {
                            const result = data[0];
                            const lat = parseFloat(result.lat);
                            const lng = parseFloat(result.lon);

                            this.map.setView([lat, lng], 17);
                            this.marker.setLatLng([lat, lng]);
                            this.updateCoordinates(lat, lng);

                            if (result.display_name) {
                                this.$wire.set('data.address', result.display_name);
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
            }));
        });
    </script>

    <style>
        .leaflet-container {
            font-family: inherit;
        }
    </style>
</x-dynamic-component>
