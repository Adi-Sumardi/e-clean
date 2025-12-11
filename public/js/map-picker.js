// Map Picker Component for Filament
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

            // Load Leaflet CSS
            if (!document.querySelector('link[href*="leaflet"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(link);
            }

            // Load Leaflet JS
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
                    attribution: '&copy; OpenStreetMap'
                }).addTo(this.map);

                this.marker = L.marker([this.latitude, this.longitude], {
                    draggable: true
                }).addTo(this.map);

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
                    alert('Lokasi tidak ditemukan. Coba kata kunci lain.');
                }
            } catch (err) {
                console.error(err);
                alert('Gagal mencari lokasi. Periksa koneksi internet.');
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
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    this.map.setView([lat, lng], 17);
                    this.marker.setLatLng([lat, lng]);
                    this.updateCoords(lat, lng);
                    this.isLoading = false;
                    this.gpsStatus = 'granted';
                },
                (err) => {
                    this.isLoading = false;
                    console.error('GPS Error:', err);

                    if (err.code === 1) {
                        this.gpsStatus = 'denied';
                        alert('Izin lokasi ditolak. Klik ikon gembok di address bar untuk mengaktifkan.');
                    } else if (err.code === 2) {
                        alert('Posisi tidak tersedia. Pastikan GPS aktif.');
                    } else if (err.code === 3) {
                        alert('Waktu habis. Coba lagi.');
                    } else {
                        alert('Gagal mendapatkan lokasi.');
                    }
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        }
    };
};
