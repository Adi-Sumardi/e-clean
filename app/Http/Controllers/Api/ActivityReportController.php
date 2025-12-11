<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityReportResource;
use App\Models\ActivityReport;
use App\Traits\ApiResponse;
use App\Traits\SecureErrorHandling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ActivityReportController extends Controller
{
    use ApiResponse, SecureErrorHandling;

    /**
     * Get activity reports with filtering
     * Petugas can only see their own reports
     * Admin/Supervisor can see all reports
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = ActivityReport::with(['petugas', 'lokasi', 'jadwal', 'approver']);

            // Role-based filtering - Petugas only sees their own reports
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
            } elseif ($request->has('month') && $request->has('year')) {
                // Filter by month and year
                $query->whereMonth('tanggal', $request->month)
                      ->whereYear('tanggal', $request->year);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by location
            if ($request->has('lokasi_id')) {
                $query->where('lokasi_id', $request->lokasi_id);
            }

            // Filter by petugas (admin/supervisor only)
            if ($request->has('petugas_id') && !$user->hasRole('petugas')) {
                $query->where('petugas_id', $request->petugas_id);
            }

            // Filter by jadwal
            if ($request->has('jadwal_id')) {
                $query->where('jadwal_id', $request->jadwal_id);
            }

            // Filter by rating
            if ($request->has('min_rating')) {
                $query->where('rating', '>=', $request->min_rating);
            }

            // Search by activity description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('kegiatan', 'like', "%{$search}%")
                      ->orWhere('catatan_petugas', 'like', "%{$search}%")
                      ->orWhere('catatan_supervisor', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            if ($perPage === 'all') {
                $reports = $query->orderBy('tanggal', 'desc')
                                ->orderBy('jam_mulai', 'desc')
                                ->get();

                return $this->successResponse(
                    ActivityReportResource::collection($reports),
                    'Activity reports retrieved successfully'
                );
            }

            $reports = $query->orderBy('tanggal', 'desc')
                            ->orderBy('jam_mulai', 'desc')
                            ->paginate($perPage);

            return $this->successResponse([
                'data' => ActivityReportResource::collection($reports->items()),
                'pagination' => [
                    'current_page' => $reports->currentPage(),
                    'last_page' => $reports->lastPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                    'from' => $reports->firstItem(),
                    'to' => $reports->lastItem(),
                ]
            ], 'Activity reports retrieved successfully');

        } catch (\Exception $e) {
            return $this->handleSecureException($e, 'Failed to retrieve activity reports', 'ActivityReportController@index');
        }
    }

    /**
     * Create new activity report
     * Only petugas can create reports for themselves
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Validate input with enhanced image validation
            $validator = Validator::make($request->all(), [
                'jadwal_id' => 'required|exists:jadwal_kebersihans,id',
                'lokasi_id' => 'required|exists:lokasis,id',
                'tanggal' => 'required|date|before_or_equal:today',
                'jam_mulai' => 'required|date_format:H:i',
                'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
                'kegiatan' => 'required|string|min:10|max:1000',
                'foto_sebelum' => 'required|array|min:1|max:5',
                'foto_sebelum.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120|dimensions:min_width=100,min_height=100',
                'foto_sesudah' => 'required|array|min:1|max:5',
                'foto_sesudah.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120|dimensions:min_width=100,min_height=100',
                'koordinat_lokasi' => 'nullable|string|max:255',
                'catatan_petugas' => 'nullable|string|max:1000',
                'status' => 'nullable|in:draft,submitted',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $validated = $validator->validated();

            // Use ImageService for compression (saves ~80% storage!)
            $imageService = app(\App\Services\ImageService::class);

            // Handle foto_sebelum upload with compression
            $fotoSebelumPaths = [];
            if ($request->hasFile('foto_sebelum')) {
                foreach ($request->file('foto_sebelum') as $foto) {
                    $path = $imageService->compressAndStore(
                        $foto,
                        'activity-reports/before',
                        quality: 85,
                        maxWidth: 1920,
                        maxHeight: 1920
                    );
                    $fotoSebelumPaths[] = $path;
                }
            }

            // Handle foto_sesudah upload with compression
            $fotoSesudahPaths = [];
            if ($request->hasFile('foto_sesudah')) {
                foreach ($request->file('foto_sesudah') as $foto) {
                    $path = $imageService->compressAndStore(
                        $foto,
                        'activity-reports/after',
                        quality: 85,
                        maxWidth: 1920,
                        maxHeight: 1920
                    );
                    $fotoSesudahPaths[] = $path;
                }
            }

            // Create activity report
            $report = ActivityReport::create([
                'jadwal_id' => $validated['jadwal_id'],
                'lokasi_id' => $validated['lokasi_id'],
                'petugas_id' => $user->id,
                'tanggal' => $validated['tanggal'],
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $validated['jam_selesai'],
                'kegiatan' => $validated['kegiatan'],
                'foto_sebelum' => !empty($fotoSebelumPaths) ? $fotoSebelumPaths : null,
                'foto_sesudah' => !empty($fotoSesudahPaths) ? $fotoSesudahPaths : null,
                'koordinat_lokasi' => $validated['koordinat_lokasi'] ?? null,
                'catatan_petugas' => $validated['catatan_petugas'] ?? null,
                'status' => $validated['status'] ?? 'draft',
            ]);

            // Load relationships
            $report->load(['petugas', 'lokasi', 'jadwal']);

            return $this->successResponse(
                new ActivityReportResource($report),
                'Activity report created successfully',
                201
            );

        } catch (\Exception $e) {
            // Clean up uploaded files if report creation fails
            if (!empty($fotoSebelumPaths)) {
                foreach ($fotoSebelumPaths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }
            if (!empty($fotoSesudahPaths)) {
                foreach ($fotoSesudahPaths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }

            return $this->errorResponse('Failed to create activity report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single activity report by ID
     * Petugas can only view their own reports
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $query = ActivityReport::with(['petugas', 'lokasi', 'jadwal', 'approver']);

            // Role-based authorization
            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id);
            }

            $report = $query->findOrFail($id);

            return $this->successResponse(
                new ActivityReportResource($report),
                'Activity report retrieved successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Activity report not found or unauthorized');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve activity report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update activity report
     * Petugas can only update their own draft/submitted reports
     * Admin/Supervisor can update any report
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $query = ActivityReport::query();

            // Role-based authorization
            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id)
                      ->whereIn('status', ['draft', 'submitted']); // Petugas can't edit approved/rejected
            }

            $report = $query->findOrFail($id);

            // Validate input
            $validator = Validator::make($request->all(), [
                'jadwal_id' => 'nullable|exists:jadwal_kebersihans,id',
                'lokasi_id' => 'nullable|exists:lokasis,id',
                'tanggal' => 'nullable|date',
                'jam_mulai' => 'nullable|date_format:H:i',
                'jam_selesai' => 'nullable|date_format:H:i|after:jam_mulai',
                'kegiatan' => 'nullable|string|max:1000',
                'foto_sebelum' => 'nullable|array',
                'foto_sebelum.*' => 'image|mimes:jpeg,png,jpg|max:5120',
                'foto_sesudah' => 'nullable|array',
                'foto_sesudah.*' => 'image|mimes:jpeg,png,jpg|max:5120',
                'koordinat_lokasi' => 'nullable|string|max:255',
                'catatan_petugas' => 'nullable|string|max:1000',
                'catatan_supervisor' => 'nullable|string|max:1000',
                'status' => 'nullable|in:draft,submitted,approved,rejected',
                'rating' => 'nullable|integer|min:1|max:5',
                'rejected_reason' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $validated = $validator->validated();
            $oldFotoSebelum = $report->foto_sebelum;
            $oldFotoSesudah = $report->foto_sesudah;

            // Handle foto_sebelum upload
            if ($request->hasFile('foto_sebelum')) {
                $fotoSebelumPaths = [];
                foreach ($request->file('foto_sebelum') as $foto) {
                    $path = $foto->store('activity-reports/before', 'public');
                    $fotoSebelumPaths[] = $path;
                }
                $validated['foto_sebelum'] = $fotoSebelumPaths;

                // Delete old photos
                if (is_array($oldFotoSebelum)) {
                    foreach ($oldFotoSebelum as $oldPath) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
            }

            // Handle foto_sesudah upload
            if ($request->hasFile('foto_sesudah')) {
                $fotoSesudahPaths = [];
                foreach ($request->file('foto_sesudah') as $foto) {
                    $path = $foto->store('activity-reports/after', 'public');
                    $fotoSesudahPaths[] = $path;
                }
                $validated['foto_sesudah'] = $fotoSesudahPaths;

                // Delete old photos
                if (is_array($oldFotoSesudah)) {
                    foreach ($oldFotoSesudah as $oldPath) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
            }

            // Handle status changes
            if (isset($validated['status'])) {
                // Only admin/supervisor can approve/reject
                if (in_array($validated['status'], ['approved', 'rejected']) && $user->hasRole('petugas')) {
                    return $this->forbiddenResponse('Only admin/supervisor can approve or reject reports');
                }

                // Set approved_at and approver_id when approving
                if ($validated['status'] === 'approved') {
                    $validated['approved_at'] = Carbon::now();
                    $validated['approver_id'] = $user->id;
                }

                // Reset approval when changing from approved to other status
                if ($report->status === 'approved' && $validated['status'] !== 'approved') {
                    $validated['approved_at'] = null;
                    $validated['approver_id'] = null;
                }
            }

            // Update report
            $report->update($validated);

            // Reload relationships
            $report->load(['petugas', 'lokasi', 'jadwal', 'approver']);

            return $this->successResponse(
                new ActivityReportResource($report),
                'Activity report updated successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Activity report not found or unauthorized to update');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update activity report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete activity report
     * Petugas can only delete their own draft reports
     * Admin/Supervisor can delete any report
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $query = ActivityReport::query();

            // Role-based authorization
            if ($user->hasRole('petugas')) {
                // Petugas can only delete draft reports
                $query->where('petugas_id', $user->id)
                      ->where('status', 'draft');
            }

            $report = $query->findOrFail($id);

            // Delete associated photos
            if (is_array($report->foto_sebelum)) {
                foreach ($report->foto_sebelum as $path) {
                    Storage::disk('public')->delete($path);
                }
            }
            if (is_array($report->foto_sesudah)) {
                foreach ($report->foto_sesudah as $path) {
                    Storage::disk('public')->delete($path);
                }
            }

            $report->delete();

            return $this->successResponse(
                null,
                'Activity report deleted successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Activity report not found or unauthorized to delete');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete activity report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get activity reports statistics
     * Petugas gets their own stats
     * Admin/Supervisor gets overall stats or filtered by petugas
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = ActivityReport::query();

            // Role-based filtering
            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id);
            } elseif ($request->has('petugas_id')) {
                $query->where('petugas_id', $request->petugas_id);
            }

            // Date range filter
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('tanggal', [
                    $request->start_date,
                    $request->end_date
                ]);
            } elseif ($request->has('month') && $request->has('year')) {
                $query->whereMonth('tanggal', $request->month)
                      ->whereYear('tanggal', $request->year);
            }

            $totalReports = (clone $query)->count();
            $draftReports = (clone $query)->where('status', 'draft')->count();
            $submittedReports = (clone $query)->where('status', 'submitted')->count();
            $approvedReports = (clone $query)->where('status', 'approved')->count();
            $rejectedReports = (clone $query)->where('status', 'rejected')->count();
            $averageRating = (clone $query)->whereNotNull('rating')->avg('rating');

            return $this->successResponse([
                'total_reports' => $totalReports,
                'draft_reports' => $draftReports,
                'submitted_reports' => $submittedReports,
                'approved_reports' => $approvedReports,
                'rejected_reports' => $rejectedReports,
                'average_rating' => $averageRating ? round($averageRating, 2) : null,
            ], 'Statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Submit multiple draft reports at once
     * Petugas only
     */
    public function bulkSubmit(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'report_ids' => 'required|array',
                'report_ids.*' => 'required|exists:activity_reports,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $reportIds = $request->report_ids;

            // Update only the user's draft reports
            $updated = ActivityReport::whereIn('id', $reportIds)
                ->where('petugas_id', $user->id)
                ->where('status', 'draft')
                ->update(['status' => 'submitted']);

            return $this->successResponse([
                'updated_count' => $updated,
            ], "Successfully submitted {$updated} reports");

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to submit reports: ' . $e->getMessage(), 500);
        }
    }
}
