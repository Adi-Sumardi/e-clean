<?php

namespace App\Livewire;

use App\Models\Lokasi;
use App\Models\User;
use App\Services\WatermarkCameraService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WatermarkCamera extends Component
{
    public $photoType; // 'before' or 'after'
    public $activityReportId;
    public $lokasiId;
    public $lokasi;
    public $petugas;

    // Event listeners
    protected $listeners = ['capturePhoto'];

    public function mount($type, $lokasiId, $activityReportId = null)
    {
        $this->photoType = $type;
        $this->lokasiId = $lokasiId;
        $this->activityReportId = $activityReportId;

        // Load lokasi data
        $this->lokasi = Lokasi::findOrFail($lokasiId);

        // Load petugas (current user)
        $this->petugas = Auth::user();

        // Check if lokasi has GPS coordinates
        if (!$this->lokasi->latitude || !$this->lokasi->longitude) {
            $this->dispatch('camera-error', [
                'message' => 'Lokasi kerja belum memiliki koordinat GPS. Hubungi admin untuk menambahkan koordinat.'
            ]);
        }
    }

    public function capturePhoto($photoData, $gpsData, $deviceData)
    {
        try {
            // Validate required data
            if (empty($photoData) || empty($gpsData)) {
                $this->dispatch('photo-error', [
                    'message' => 'Data foto atau GPS tidak lengkap'
                ]);
                return;
            }

            // Validate GPS first
            $cameraService = app(WatermarkCameraService::class);
            $validation = $cameraService->validateGPS(
                $gpsData['latitude'],
                $gpsData['longitude'],
                $this->lokasiId,
                $gpsData['accuracy']
            );

            if (!$validation['valid']) {
                $this->dispatch('photo-error', [
                    'message' => $validation['error']
                ]);
                return;
            }

            // Process photo with watermark
            $result = $cameraService->processPhoto([
                'photo_data' => $photoData,
                'gps_data' => $gpsData,
                'device_data' => $deviceData,
                'petugas_id' => $this->petugas->id,
                'lokasi_id' => $this->lokasiId,
                'photo_type' => $this->photoType,
                'activity_report_id' => $this->activityReportId,
                'captured_at' => now(),
            ]);

            if ($result['success']) {
                $this->dispatch('photo-captured', [
                    'path' => $result['path'],
                    'url' => $result['url'],
                    'metadata' => [
                        'id' => $result['metadata']->id,
                        'confidence_score' => $result['confidence_score'],
                        'file_size' => $result['file_size'],
                        'compression_ratio' => $result['compression_ratio'],
                    ]
                ]);
            } else {
                $this->dispatch('photo-error', [
                    'message' => $result['error']
                ]);
            }

        } catch (\Exception $e) {
            $this->dispatch('photo-error', [
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.watermark-camera', [
            'petugasName' => $this->petugas->name,
            'lokasiName' => $this->lokasi->nama_lokasi,
            'lokasiLat' => $this->lokasi->latitude,
            'lokasiLon' => $this->lokasi->longitude,
        ]);
    }
}
