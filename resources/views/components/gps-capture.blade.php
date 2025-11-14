<div x-data="gpsCapture()" x-init="init()" class="space-y-3">
    <!-- GPS Status -->
    <div class="rounded-lg border p-3" :class="{
        'bg-green-50 border-green-200': gpsStatus === 'success',
        'bg-yellow-50 border-yellow-200': gpsStatus === 'loading',
        'bg-red-50 border-red-200': gpsStatus === 'error',
        'bg-gray-50 border-gray-200': gpsStatus === 'idle'
    }">
        <div class="flex items-center gap-2">
            <!-- Icon -->
            <div x-show="gpsStatus === 'loading'">
                <svg class="animate-spin h-5 w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <div x-show="gpsStatus === 'success'">
                <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div x-show="gpsStatus === 'error'">
                <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div x-show="gpsStatus === 'idle'">
                <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>

            <!-- Status Text -->
            <div class="flex-1">
                <p class="text-sm font-medium" x-text="statusMessage"></p>
            </div>

            <!-- Capture Button -->
            <button
                type="button"
                @click="captureLocation()"
                :disabled="gpsStatus === 'loading'"
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
            >
                <span x-show="gpsStatus !== 'loading'">Capture GPS</span>
                <span x-show="gpsStatus === 'loading'">Capturing...</span>
            </button>
        </div>

        <!-- GPS Details -->
        <div x-show="gpsData.latitude && gpsData.longitude" class="mt-3 space-y-1 text-xs text-gray-600">
            <div class="flex justify-between">
                <span class="font-medium">Latitude:</span>
                <span x-text="gpsData.latitude"></span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Longitude:</span>
                <span x-text="gpsData.longitude"></span>
            </div>
            <div class="flex justify-between" x-show="gpsData.accuracy">
                <span class="font-medium">Accuracy:</span>
                <span x-text="gpsData.accuracy + 'm'"></span>
            </div>
            <div class="mt-2">
                <a :href="'https://www.google.com/maps?q=' + gpsData.latitude + ',' + gpsData.longitude"
                   target="_blank"
                   class="text-primary-600 hover:text-primary-700 underline">
                    View on Google Maps →
                </a>
            </div>
        </div>
    </div>

    <!-- Hidden Inputs -->
    <input type="hidden"
           x-ref="latitudeInput"
           name="{{ $latitudeField ?? 'latitude' }}"
           :value="gpsData.latitude">
    <input type="hidden"
           x-ref="longitudeInput"
           name="{{ $longitudeField ?? 'longitude' }}"
           :value="gpsData.longitude">
    <input type="hidden"
           x-ref="accuracyInput"
           name="{{ $accuracyField ?? 'gps_accuracy' }}"
           :value="gpsData.accuracy">
</div>

<script>
function gpsCapture() {
    return {
        gpsStatus: 'idle',
        statusMessage: 'Click "Capture GPS" to get current location',
        gpsData: {
            latitude: null,
            longitude: null,
            accuracy: null,
        },

        init() {
            // Check if Geolocation API is available
            if (!navigator.geolocation) {
                this.gpsStatus = 'error';
                this.statusMessage = 'Geolocation is not supported by this browser';
            }
        },

        captureLocation() {
            this.gpsStatus = 'loading';
            this.statusMessage = 'Getting your location...';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.gpsData.latitude = position.coords.latitude.toFixed(7);
                    this.gpsData.longitude = position.coords.longitude.toFixed(7);
                    this.gpsData.accuracy = position.coords.accuracy ? position.coords.accuracy.toFixed(2) : null;

                    this.gpsStatus = 'success';
                    this.statusMessage = 'Location captured successfully!';

                    // Trigger Livewire update if needed
                    if (this.$wire) {
                        this.$wire.set('{{ $latitudeField ?? "latitude" }}', this.gpsData.latitude);
                        this.$wire.set('{{ $longitudeField ?? "longitude" }}', this.gpsData.longitude);
                        if (this.gpsData.accuracy) {
                            this.$wire.set('{{ $accuracyField ?? "gps_accuracy" }}', this.gpsData.accuracy);
                        }
                    }
                },
                (error) => {
                    this.gpsStatus = 'error';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            this.statusMessage = 'Permission denied. Please allow location access.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            this.statusMessage = 'Location information unavailable.';
                            break;
                        case error.TIMEOUT:
                            this.statusMessage = 'Request timeout. Please try again.';
                            break;
                        default:
                            this.statusMessage = 'An unknown error occurred.';
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
    }
}
</script>
