<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LokasiResource;
use App\Models\Lokasi;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LokasiController extends Controller
{
    use ApiResponse;

    /** Roles allowed to manage (create/update/delete) master data. */
    private const MANAGER_ROLES = ['super_admin', 'admin', 'supervisor'];

    private function canManage(Request $request): bool
    {
        return $request->user()->hasAnyRole(self::MANAGER_ROLES);
    }

    /**
     * Get locations. Managers can see all (incl. inactive) and filter by unit;
     * field staff only see active ones.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $cacheKey = 'api_lokasi_index_' . $user->id . '_' . md5(serialize($request->all()));

            $locations = \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($request, $user) {
                $query = Lokasi::query()->with('unit');

                // Field staff only see active locations; managers can opt-in to all.
                if (! $this->canManage($request) || ! $request->boolean('include_inactive')) {
                    $query->where('is_active', true);
                }

                if ($request->filled('unit_id')) {
                    $query->where('unit_id', $request->unit_id);
                }
                if ($request->filled('kategori')) {
                    $query->where('kategori', $request->kategori);
                }
                if ($request->filled('lantai')) {
                    $query->where('lantai', $request->lantai);
                }
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where(function ($q) use ($search) {
                        $q->where('nama_lokasi', 'like', "%{$search}%")
                            ->orWhere('kode_lokasi', 'like', "%{$search}%");
                    });
                }

                return $query->orderBy('nama_lokasi')->get();
            });

            return $this->successResponse(
                LokasiResource::collection($locations),
                'Locations retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve locations: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Daftar QR code lokasi untuk halaman cetak di PWA. QR yang belum ada
     * di-generate otomatis (SVG berisi URL form keluhan tamu /keluhan/{kode}).
     */
    public function qrCodes(Request $request): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to print QR codes.');
            }

            $query = Lokasi::query()->with('unit')->where('is_active', true);
            if ($request->filled('unit_id')) {
                $query->where('unit_id', $request->unit_id);
            }

            $qrService = app(\App\Services\QRCodeService::class);
            $lokasis = $query->orderBy('kode_lokasi')->get()->map(function (Lokasi $lokasi) use ($qrService) {
                // Generate bila belum ada / file hilang dari storage.
                $exists = $lokasi->qr_code
                    && \Illuminate\Support\Facades\Storage::disk('public')->exists($lokasi->qr_code);
                if (! $exists) {
                    $qrService->generateForLokasi($lokasi);
                }

                return [
                    'id' => $lokasi->id,
                    'kode_lokasi' => $lokasi->kode_lokasi,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'kategori' => $lokasi->kategori,
                    'lantai' => $lokasi->lantai,
                    'unit' => $lokasi->unit?->nama_unit,
                    'qr_url' => asset('storage/' . $lokasi->qr_code),
                    'keluhan_url' => url('/keluhan/' . $lokasi->kode_lokasi),
                ];
            });

            return $this->successResponse($lokasis->values(), 'QR codes retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve QR codes: ' . $e->getMessage(), 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $location = Lokasi::with('unit')->findOrFail($id);

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

    public function store(Request $request): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage locations.');
            }

            $validated = $request->validate([
                'unit_id' => ['required', 'exists:units,id'],
                'kode_lokasi' => ['required', 'string', 'max:50', 'unique:lokasis,kode_lokasi'],
                'nama_lokasi' => ['required', 'string', 'max:255'],
                'kategori' => ['required', 'string', 'max:100'],
                'lantai' => ['nullable', 'string', 'max:50'],
                'deskripsi' => ['nullable', 'string', 'max:1000'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            // Lokasi::created() auto-generates the QR code.
            $lokasi = Lokasi::create(array_merge($validated, [
                'is_active' => $validated['is_active'] ?? true,
            ]));

            \Illuminate\Support\Facades\Cache::flush();

            return $this->successResponse(
                new LokasiResource($lokasi->load('unit')),
                'Location created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create location: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage locations.');
            }

            $lokasi = Lokasi::find($id);
            if (! $lokasi) {
                return $this->notFoundResponse('Location not found');
            }

            $validated = $request->validate([
                'unit_id' => ['sometimes', 'exists:units,id'],
                'kode_lokasi' => ['sometimes', 'string', 'max:50', 'unique:lokasis,kode_lokasi,' . $lokasi->id],
                'nama_lokasi' => ['sometimes', 'string', 'max:255'],
                'kategori' => ['sometimes', 'string', 'max:100'],
                'lantai' => ['nullable', 'string', 'max:50'],
                'deskripsi' => ['nullable', 'string', 'max:1000'],
                'is_active' => ['sometimes', 'boolean'],
            ]);

            $lokasi->update($validated);

            \Illuminate\Support\Facades\Cache::flush();

            return $this->successResponse(
                new LokasiResource($lokasi->fresh('unit')),
                'Location updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update location: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage locations.');
            }

            $lokasi = Lokasi::find($id);
            if (! $lokasi) {
                return $this->notFoundResponse('Location not found');
            }

            $lokasi->delete();

            \Illuminate\Support\Facades\Cache::flush();

            return $this->successResponse(null, 'Location deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete location: ' . $e->getMessage(), 500);
        }
    }
}
