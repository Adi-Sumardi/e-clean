<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JadwalKebersihanResource;
use App\Models\JadwalKebersihan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class JadwalKebersihanController extends Controller
{
    use ApiResponse;

    /** Roles allowed to create/update/delete schedules. */
    private const MANAGER_ROLES = ['super_admin', 'admin', 'supervisor'];

    private function canManage(Request $request): bool
    {
        return $request->user()->hasAnyRole(self::MANAGER_ROLES);
    }

    private function scheduleRules(bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return [
            'petugas_id' => [$req, 'exists:users,id'],
            'lokasi_id' => [$req, 'exists:lokasis,id'],
            'tanggal' => [$req, 'date'],
            'shift' => [$req, 'in:pagi,standby,siang,sweeping,sore'],
            'jam_mulai' => [$req, 'date_format:H:i'],
            'jam_selesai' => [$req, 'date_format:H:i', 'after:jam_mulai'],
            'prioritas' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    /**
     * Get schedules for authenticated petugas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = JadwalKebersihan::with(['lokasi', 'petugas']);

            // Petugas only sees their own schedules
            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('tanggal', [
                    $request->start_date,
                    $request->end_date
                ]);
            } elseif ($request->has('date')) {
                // Filter by specific date
                $query->whereDate('tanggal', $request->date);
            } else {
                // Default: current month
                $query->whereMonth('tanggal', Carbon::now()->month)
                      ->whereYear('tanggal', Carbon::now()->year);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by shift
            if ($request->has('shift')) {
                $query->where('shift', $request->shift);
            }

            $schedules = $query->orderBy('tanggal')
                              ->orderBy('jam_mulai')
                              ->get();

            return $this->successResponse(
                JadwalKebersihanResource::collection($schedules),
                'Schedules retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve schedules: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single schedule by ID
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $query = JadwalKebersihan::with(['lokasi', 'petugas']);

            // Petugas can only view their own schedules
            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id);
            }

            $schedule = $query->findOrFail($id);

            return $this->successResponse(
                new JadwalKebersihanResource($schedule),
                'Schedule retrieved successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Schedule not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve schedule: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get today's schedules
     */
    public function today(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = JadwalKebersihan::with(['lokasi', 'petugas'])
                                    ->whereDate('tanggal', Carbon::today());

            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id);
            }

            $schedules = $query->orderBy('jam_mulai')->get();

            return $this->successResponse(
                JadwalKebersihanResource::collection($schedules),
                'Today schedules retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve today schedules: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get upcoming schedules (next 7 days)
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = JadwalKebersihan::with(['lokasi', 'petugas'])
                                    ->whereBetween('tanggal', [
                                        Carbon::today(),
                                        Carbon::today()->addDays(7)
                                    ]);

            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id);
            }

            $schedules = $query->orderBy('tanggal')
                              ->orderBy('jam_mulai')
                              ->get();

            return $this->successResponse(
                JadwalKebersihanResource::collection($schedules),
                'Upcoming schedules retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve upcoming schedules: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a cleaning schedule (supervisor/admin only).
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage schedules.');
            }

            $validated = $request->validate($this->scheduleRules());
            $validated['status'] = $validated['status'] ?? 'active';
            $validated['created_by'] = $request->user()->id;

            $jadwal = JadwalKebersihan::create($validated);
            $jadwal->load(['lokasi', 'petugas']);

            return $this->successResponse(
                new JadwalKebersihanResource($jadwal),
                'Schedule created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create schedule: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage schedules.');
            }

            $jadwal = JadwalKebersihan::find($id);
            if (! $jadwal) {
                return $this->notFoundResponse('Schedule not found');
            }

            $validated = $request->validate($this->scheduleRules(partial: true));
            $jadwal->update($validated);

            return $this->successResponse(
                new JadwalKebersihanResource($jadwal->fresh(['lokasi', 'petugas'])),
                'Schedule updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update schedule: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            if (! $this->canManage($request)) {
                return $this->forbiddenResponse('You are not allowed to manage schedules.');
            }

            $jadwal = JadwalKebersihan::find($id);
            if (! $jadwal) {
                return $this->notFoundResponse('Schedule not found');
            }

            $jadwal->delete();

            return $this->successResponse(null, 'Schedule deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete schedule: ' . $e->getMessage(), 500);
        }
    }
}
