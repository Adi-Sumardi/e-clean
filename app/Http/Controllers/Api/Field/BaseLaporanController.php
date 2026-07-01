<?php

namespace App\Http\Controllers\Api\Field;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Traits\HandlesIdempotency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Shared API for a field-staff report domain (satpam / OB / toko) including
 * the supervisor approval workflow.
 *
 * Field staff (owning role) only see and create their own reports. Supervisors,
 * pengurus and admins see everything, may filter by unit/status, and may
 * approve or reject. Subclasses provide the model, resource, owning role and
 * the role-specific create logic.
 */
abstract class BaseLaporanController extends Controller
{
    use ApiResponse;
    use HandlesIdempotency;

    abstract protected function model(): string;

    abstract protected function resourceClass(): string;

    abstract protected function ownerRole(): string;

    /** Validation rules for creating a report (role-specific fields). */
    abstract protected function storeRules(): array;

    /**
     * Build the persisted attributes from the validated payload + request.
     * Handles role-specific fields such as photo uploads.
     */
    abstract protected function buildAttributes(array $validated, Request $request): array;

    protected function isSupervisor(Request $request): bool
    {
        return ! $request->user()->hasRole($this->ownerRole());
    }

    /**
     * Compress and store any uploaded files for the given multipart field,
     * returning the stored relative paths (empty array when none).
     */
    protected function storePhotos(Request $request, string $field, string $dir): array
    {
        if (! $request->hasFile($field)) {
            return [];
        }

        $imageService = app(\App\Services\ImageService::class);
        $paths = [];
        foreach ($request->file($field) as $foto) {
            $paths[] = $imageService->compressAndStore(
                $foto,
                $dir,
                quality: 85,
                maxWidth: 1920,
                maxHeight: 1920
            );
        }

        return $paths;
    }

    protected function baseQuery(Request $request): Builder
    {
        $model = $this->model();
        $query = $model::query()->with(['lokasi.unit', 'petugas', 'jadwal', 'approver']);

        $user = $request->user();
        if ($user->hasRole($this->ownerRole())) {
            $query->where('petugas_id', $user->id);
        } else {
            if ($request->filled('petugas_id')) {
                $query->where('petugas_id', $request->petugas_id);
            }
            if ($request->filled('unit_id')) {
                $unitId = $request->unit_id;
                $query->whereHas('lokasi', fn (Builder $q) => $q->where('unit_id', $unitId));
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('lokasi_id')) {
            $query->where('lokasi_id', $request->lokasi_id);
        }
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('date')) {
            $query->whereDate('tanggal', $request->date);
        } elseif ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('tanggal', $request->month)->whereYear('tanggal', $request->year);
        }

        return $query;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $items = $this->baseQuery($request)
                ->orderBy('tanggal', 'desc')
                ->orderBy('jam_mulai', 'desc')
                ->get();

            $resource = $this->resourceClass();

            return $this->successResponse(
                $resource::collection($items),
                'Reports retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve reports: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $item = $this->baseQuery($request)->find($id);
            if (! $item) {
                return $this->notFoundResponse('Report not found');
            }
            $resource = $this->resourceClass();

            return $this->successResponse(new $resource($item), 'Report retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve report: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // Only the owning field role may submit reports.
            if (! $request->user()->hasRole($this->ownerRole())) {
                return $this->forbiddenResponse('Only ' . $this->ownerRole() . ' can submit this report.');
            }

            // Idempotency: retry dengan key yang sama mengembalikan laporan lama.
            if ($existingId = $this->idempotentHit($request, $request->user()->id)) {
                $model = $this->model();
                $existing = $model::with(['lokasi.unit', 'petugas', 'jadwal'])->find($existingId);
                if ($existing) {
                    $resource = $this->resourceClass();
                    return $this->successResponse(new $resource($existing), 'Report already submitted', 200);
                }
            }

            $rules = array_merge([
                'jadwal_id' => 'nullable|integer',
                'lokasi_id' => 'required|exists:lokasis,id',
                'tanggal' => 'required|date|before_or_equal:today',
                'jam_mulai' => 'required|date_format:H:i',
                'jam_selesai' => 'nullable|date_format:H:i|after:jam_mulai',
                'catatan_petugas' => 'nullable|string|max:1000',
                'status' => 'nullable|in:draft,submitted',
            ], $this->storeRules());

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $validated = $validator->validated();
            $attributes = array_merge(
                $this->buildAttributes($validated, $request),
                [
                    'petugas_id' => $request->user()->id,
                    'status' => $validated['status'] ?? 'submitted',
                ]
            );

            $model = $this->model();
            $report = $model::create($attributes);
            $report->load(['lokasi.unit', 'petugas', 'jadwal']);

            $this->rememberIdempotency($request, $request->user()->id, class_basename($model), $report->id);

            $resource = $this->resourceClass();

            return $this->successResponse(
                new $resource($report),
                'Report created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create report: ' . $e->getMessage(), 500);
        }
    }

    public function approve(Request $request, $id): JsonResponse
    {
        return $this->decide($request, $id, true);
    }

    public function reject(Request $request, $id): JsonResponse
    {
        return $this->decide($request, $id, false);
    }

    /** Shared approve/reject handler — supervisor/admin only. */
    protected function decide(Request $request, $id, bool $approved): JsonResponse
    {
        try {
            if (! $this->isSupervisor($request)) {
                return $this->forbiddenResponse('You are not allowed to review reports.');
            }

            $model = $this->model();
            $report = $model::find($id);
            if (! $report) {
                return $this->notFoundResponse('Report not found');
            }

            if (! in_array($report->status, ['submitted', 'pending'])) {
                return $this->errorResponse('Laporan sudah pernah ditinjau (status: ' . $report->status . ').', 422);
            }

            if ($approved) {
                $validated = $request->validate([
                    'rating' => 'nullable|integer|min:1|max:5',
                    'catatan_supervisor' => 'nullable|string|max:1000',
                ]);
                $report->update([
                    'status' => 'approved',
                    'rating' => $validated['rating'] ?? $report->rating,
                    'catatan_supervisor' => $validated['catatan_supervisor'] ?? $report->catatan_supervisor,
                    'rejected_reason' => null,
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);
            } else {
                $validated = $request->validate([
                    'rejected_reason' => 'required|string|max:500',
                ]);
                $report->update([
                    'status' => 'rejected',
                    'rejected_reason' => $validated['rejected_reason'],
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);
            }

            $report->load(['lokasi.unit', 'petugas', 'jadwal', 'approver']);

            if ($report->petugas) {
                app(\App\Services\WebPushService::class)->sendToUser(
                    $report->petugas,
                    $approved ? 'Laporan Disetujui' : 'Laporan Ditolak',
                    $approved
                        ? 'Laporan Anda telah disetujui supervisor.'
                        : 'Laporan Anda ditolak: ' . \Illuminate\Support\Str::limit($report->rejected_reason, 80),
                    ['type' => $approved ? 'report_approved' : 'report_rejected', 'ref_id' => $report->id, 'url' => '/laporan']
                );
            }

            $resource = $this->resourceClass();

            return $this->successResponse(
                new $resource($report),
                $approved ? 'Report approved' : 'Report rejected'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to review report: ' . $e->getMessage(), 500);
        }
    }
}
