<?php

namespace App\Http\Controllers\Api\Field;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared read API for a field-staff schedule domain (satpam / OB / toko).
 *
 * Subclasses bind the concrete model, the API resource and the owning role.
 * Field staff (the owning role) only see their own schedules; supervisors,
 * pengurus and admins see everything and may filter by unit.
 */
abstract class BaseJadwalController extends Controller
{
    use ApiResponse;

    /** Fully-qualified Eloquent model class (e.g. JadwalSatpam::class). */
    abstract protected function model(): string;

    /** Fully-qualified API resource class. */
    abstract protected function resourceClass(): string;

    /** Role name that "owns" this schedule (e.g. 'satpam'). */
    abstract protected function ownerRole(): string;

    protected function baseQuery(Request $request): Builder
    {
        $model = $this->model();
        $query = $model::query()->with(['lokasi.unit', 'petugas']);

        $user = $request->user();
        // Field staff are scoped to their own schedules.
        if ($user->hasRole($this->ownerRole())) {
            $query->where('petugas_id', $user->id);
        } else {
            // Supervisors/admins may narrow by petugas and/or unit.
            if ($request->filled('petugas_id')) {
                $query->where('petugas_id', $request->petugas_id);
            }
            $this->applyUnitFilter($query, $request);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }

        return $query;
    }

    /** Filter by the unit the location belongs to (supervisor/admin only). */
    protected function applyUnitFilter(Builder $query, Request $request): void
    {
        if ($request->filled('unit_id')) {
            $unitId = $request->unit_id;
            $query->whereHas('lokasi', fn (Builder $q) => $q->where('unit_id', $unitId));
        }
    }

    protected function collection($items): JsonResponse
    {
        $resource = $this->resourceClass();

        return $this->successResponse(
            $resource::collection($items),
            'Schedules retrieved successfully'
        );
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = $this->baseQuery($request);

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
            } elseif ($request->has('date')) {
                $query->whereDate('tanggal', $request->date);
            } else {
                $query->whereMonth('tanggal', Carbon::now()->month)
                    ->whereYear('tanggal', Carbon::now()->year);
            }

            $items = $query->orderBy('tanggal')->orderBy('jam_mulai')->get();

            return $this->collection($items);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve schedules: ' . $e->getMessage(), 500);
        }
    }

    public function today(Request $request): JsonResponse
    {
        try {
            $items = $this->baseQuery($request)
                ->whereDate('tanggal', Carbon::today())
                ->orderBy('jam_mulai')
                ->get();

            return $this->collection($items);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve today schedules: ' . $e->getMessage(), 500);
        }
    }

    public function upcoming(Request $request): JsonResponse
    {
        try {
            $items = $this->baseQuery($request)
                ->whereBetween('tanggal', [Carbon::today(), Carbon::today()->addDays(7)])
                ->orderBy('tanggal')->orderBy('jam_mulai')
                ->get();

            return $this->collection($items);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve upcoming schedules: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $item = $this->baseQuery($request)->find($id);
            if (! $item) {
                return $this->notFoundResponse('Schedule not found');
            }
            $resource = $this->resourceClass();

            return $this->successResponse(new $resource($item), 'Schedule retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve schedule: ' . $e->getMessage(), 500);
        }
    }
}
