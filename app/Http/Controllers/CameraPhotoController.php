<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Services\WatermarkCameraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CameraPhotoController extends Controller
{
    public function capture(Request $request, WatermarkCameraService $cameraService)
    {
        try {
            Log::info('Camera capture request received', [
                'user_id' => Auth::id(),
                'request_data' => $request->except('photo_data') // Don't log photo data (too large)
            ]);

            $validated = $request->validate([
                'photo_data' => 'required|string',
                'gps_data' => 'required|array',
                'gps_data.latitude' => 'required|numeric',
                'gps_data.longitude' => 'required|numeric',
                'gps_data.accuracy' => 'required|numeric',
                'device_data' => 'required|array',
                'lokasi_id' => 'required|integer|exists:lokasis,id',
                'photo_type' => 'required|in:before,after',
                'activity_report_id' => 'nullable|integer|exists:activity_reports,id',
            ]);

            Log::info('Validation passed, validating GPS...');

            // Validate GPS
            $validation = $cameraService->validateGPS(
                $validated['gps_data']['latitude'],
                $validated['gps_data']['longitude'],
                $validated['lokasi_id'],
                $validated['gps_data']['accuracy']
            );

            if (!$validation['valid']) {
                Log::warning('GPS validation failed', $validation);
                return response()->json([
                    'success' => false,
                    'error' => $validation['error']
                ], 422);
            }

            Log::info('GPS validation passed, processing photo...');

            // Process photo
            $result = $cameraService->processPhoto([
                'photo_data' => $validated['photo_data'],
                'gps_data' => $validated['gps_data'],
                'device_data' => $validated['device_data'],
                'petugas_id' => Auth::id(),
                'lokasi_id' => $validated['lokasi_id'],
                'photo_type' => $validated['photo_type'],
                'activity_report_id' => $validated['activity_report_id'] ?? null,
                'captured_at' => now(),
            ]);

            if ($result['success']) {
                Log::info('Photo processed successfully', [
                    'path' => $result['path'],
                    'metadata_id' => $result['metadata']->id
                ]);

                return response()->json([
                    'success' => true,
                    'path' => $result['path'],
                    'url' => $result['url'],
                    'metadata' => [
                        'id' => $result['metadata']->id,
                        'confidence_score' => $result['confidence_score'],
                        'file_size' => $result['file_size'],
                        'compression_ratio' => $result['compression_ratio'],
                    ]
                ]);
            }

            Log::error('Photo processing failed', $result);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to process photo'
            ], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in camera capture', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Validation error: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Exception in camera capture', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function getLokasiInfo($id)
    {
        try {
            $lokasi = Lokasi::findOrFail($id);
            $user = Auth::user();

            return response()->json([
                'success' => true,
                'petugas_name' => $user->name,
                'lokasi_name' => $lokasi->nama_lokasi,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Lokasi tidak ditemukan'
            ], 404);
        }
    }
}
