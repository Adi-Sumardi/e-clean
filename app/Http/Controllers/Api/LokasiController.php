<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LokasiResource;
use App\Models\Lokasi;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LokasiController extends Controller
{
    use ApiResponse;

    /**
     * Get all active locations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Lokasi::query()->where('is_active', true);

            // Filter by category
            if ($request->has('kategori')) {
                $query->where('kategori', $request->kategori);
            }

            // Filter by floor
            if ($request->has('lantai')) {
                $query->where('lantai', $request->lantai);
            }

            // Search by name or code
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nama_lokasi', 'like', "%{$search}%")
                      ->orWhere('kode_lokasi', 'like', "%{$search}%");
                });
            }

            $locations = $query->orderBy('nama_lokasi')->get();

            return $this->successResponse(
                LokasiResource::collection($locations),
                'Locations retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve locations: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single location by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $location = Lokasi::findOrFail($id);

            return $this->successResponse(
                new LokasiResource($location),
                'Location retrieved successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Location not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve location: ' . $e->getMessage(), 500);
        }
    }
}
