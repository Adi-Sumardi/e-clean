<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Units (organisational areas). Used by supervisors/admins to filter approval
 * queues and reports per unit on the mobile dashboard.
 *
 * @group Units
 */
class UnitController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Unit::query()->withCount('lokasis');

            if ($request->boolean('active_only', true)) {
                $query->where('is_active', true);
            }

            $units = $query->orderBy('nama_unit')->get();

            return $this->successResponse(
                UnitResource::collection($units),
                'Units retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve units: ' . $e->getMessage(), 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $unit = Unit::withCount('lokasis')->find($id);
            if (! $unit) {
                return $this->notFoundResponse('Unit not found');
            }

            return $this->successResponse(new UnitResource($unit), 'Unit retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve unit: ' . $e->getMessage(), 500);
        }
    }

    private const MANAGER_ROLES = ['super_admin', 'admin', 'supervisor'];

    private function canManage(Request $request): bool
    {
        return $request->user()->hasAnyRole(self::MANAGER_ROLES);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage units.');
            }

            $validated = $request->validate([
                'kode_unit' => ['required', 'string', 'max:50', 'unique:units,kode_unit'],
                'nama_unit' => ['required', 'string', 'max:255'],
                'deskripsi' => ['nullable', 'string', 'max:1000'],
                'alamat' => ['nullable', 'string', 'max:500'],
                'penanggung_jawab' => ['nullable', 'string', 'max:255'],
                'telepon' => ['nullable', 'string', 'max:30'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $unit = Unit::create(array_merge($validated, [
                'is_active' => $validated['is_active'] ?? true,
            ]));

            return $this->successResponse(
                new UnitResource($unit->loadCount('lokasis')),
                'Unit created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create unit: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage units.');
            }

            $unit = Unit::find($id);
            if (! $unit) {
                return $this->notFoundResponse('Unit not found');
            }

            $validated = $request->validate([
                'kode_unit' => ['sometimes', 'string', 'max:50', 'unique:units,kode_unit,' . $unit->id],
                'nama_unit' => ['sometimes', 'string', 'max:255'],
                'deskripsi' => ['nullable', 'string', 'max:1000'],
                'alamat' => ['nullable', 'string', 'max:500'],
                'penanggung_jawab' => ['nullable', 'string', 'max:255'],
                'telepon' => ['nullable', 'string', 'max:30'],
                'is_active' => ['sometimes', 'boolean'],
            ]);

            $unit->update($validated);

            return $this->successResponse(
                new UnitResource($unit->fresh()->loadCount('lokasis')),
                'Unit updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update unit: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage units.');
            }

            $unit = Unit::withCount('lokasis')->find($id);
            if (! $unit) {
                return $this->notFoundResponse('Unit not found');
            }

            if ($unit->lokasis_count > 0) {
                return $this->errorResponse('Unit masih memiliki lokasi terkait. Pindahkan atau hapus lokasi terlebih dahulu.', 422);
            }

            $unit->delete();

            return $this->successResponse(null, 'Unit deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete unit: ' . $e->getMessage(), 500);
        }
    }
}
