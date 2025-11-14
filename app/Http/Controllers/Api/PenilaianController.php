<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PenilaianResource;
use App\Models\Penilaian;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PenilaianController extends Controller
{
    use ApiResponse;

    /**
     * Get performance evaluations with filtering
     * Petugas can only see their own evaluations
     * Admin/Supervisor can see all evaluations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Penilaian::with(['petugas', 'penilai']);

            // Role-based filtering - Petugas only sees their own evaluations
            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id);
            }

            // Filter by petugas (admin/supervisor only)
            if ($request->has('petugas_id') && !$user->hasRole('petugas')) {
                $query->where('petugas_id', $request->petugas_id);
            }

            // Filter by period
            if ($request->has('periode_bulan')) {
                $query->where('periode_bulan', $request->periode_bulan);
            }

            if ($request->has('periode_tahun')) {
                $query->where('periode_tahun', $request->periode_tahun);
            }

            // Filter by category
            if ($request->has('kategori')) {
                $query->where('kategori', $request->kategori);
            }

            // Filter by minimum score
            if ($request->has('min_rata_rata')) {
                $query->where('rata_rata', '>=', $request->min_rata_rata);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            if ($perPage === 'all') {
                $penilaian = $query->orderBy('periode_tahun', 'desc')
                                   ->orderBy('periode_bulan', 'desc')
                                   ->get();

                return $this->successResponse(
                    PenilaianResource::collection($penilaian),
                    'Evaluations retrieved successfully'
                );
            }

            $penilaian = $query->orderBy('periode_tahun', 'desc')
                              ->orderBy('periode_bulan', 'desc')
                              ->paginate($perPage);

            return $this->successResponse([
                'data' => PenilaianResource::collection($penilaian->items()),
                'pagination' => [
                    'current_page' => $penilaian->currentPage(),
                    'last_page' => $penilaian->lastPage(),
                    'per_page' => $penilaian->perPage(),
                    'total' => $penilaian->total(),
                    'from' => $penilaian->firstItem(),
                    'to' => $penilaian->lastItem(),
                ]
            ], 'Evaluations retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve evaluations: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single evaluation by ID
     * Petugas can only view their own evaluations
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Penilaian::with(['petugas', 'penilai']);

            // Role-based authorization
            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id);
            }

            $penilaian = $query->findOrFail($id);

            return $this->successResponse(
                new PenilaianResource($penilaian),
                'Evaluation retrieved successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Evaluation not found or unauthorized');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve evaluation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create new performance evaluation
     * Only admin/supervisor can create evaluations
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Only admin/supervisor can create evaluations
            if ($user->hasRole('petugas')) {
                return $this->forbiddenResponse('Only admin/supervisor can create evaluations');
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'petugas_id' => 'required|exists:users,id',
                'periode_bulan' => 'required|integer|min:1|max:12',
                'periode_tahun' => 'required|integer|min:2020|max:2100',
                'skor_kehadiran' => 'required|numeric|min:0|max:100',
                'skor_kualitas' => 'required|numeric|min:0|max:100',
                'skor_ketepatan_waktu' => 'required|numeric|min:0|max:100',
                'skor_kebersihan' => 'required|numeric|min:0|max:100',
                'catatan' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $validated = $validator->validated();

            // Check if evaluation already exists for this period
            $existing = Penilaian::where('petugas_id', $validated['petugas_id'])
                ->where('periode_bulan', $validated['periode_bulan'])
                ->where('periode_tahun', $validated['periode_tahun'])
                ->first();

            if ($existing) {
                return $this->errorResponse('Evaluation already exists for this period', 400);
            }

            // Calculate total score and average
            $totalSkor = $validated['skor_kehadiran'] +
                        $validated['skor_kualitas'] +
                        $validated['skor_ketepatan_waktu'] +
                        $validated['skor_kebersihan'];

            $rataRata = $totalSkor / 4;

            // Determine category based on average score
            $kategori = $this->determineCategory($rataRata);

            // Create evaluation
            $penilaian = Penilaian::create([
                'petugas_id' => $validated['petugas_id'],
                'penilai_id' => $user->id,
                'periode_bulan' => $validated['periode_bulan'],
                'periode_tahun' => $validated['periode_tahun'],
                'skor_kehadiran' => $validated['skor_kehadiran'],
                'skor_kualitas' => $validated['skor_kualitas'],
                'skor_ketepatan_waktu' => $validated['skor_ketepatan_waktu'],
                'skor_kebersihan' => $validated['skor_kebersihan'],
                'total_skor' => $totalSkor,
                'rata_rata' => round($rataRata, 2),
                'kategori' => $kategori,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            // Load relationships
            $penilaian->load(['petugas', 'penilai']);

            return $this->successResponse(
                new PenilaianResource($penilaian),
                'Evaluation created successfully',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create evaluation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update performance evaluation
     * Only admin/supervisor can update evaluations
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();

            // Only admin/supervisor can update evaluations
            if ($user->hasRole('petugas')) {
                return $this->forbiddenResponse('Only admin/supervisor can update evaluations');
            }

            $penilaian = Penilaian::findOrFail($id);

            // Validate input
            $validator = Validator::make($request->all(), [
                'periode_bulan' => 'nullable|integer|min:1|max:12',
                'periode_tahun' => 'nullable|integer|min:2020|max:2100',
                'skor_kehadiran' => 'nullable|numeric|min:0|max:100',
                'skor_kualitas' => 'nullable|numeric|min:0|max:100',
                'skor_ketepatan_waktu' => 'nullable|numeric|min:0|max:100',
                'skor_kebersihan' => 'nullable|numeric|min:0|max:100',
                'catatan' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $validated = $validator->validated();

            // Check if period is being changed and conflicts with existing evaluation
            if ((isset($validated['periode_bulan']) || isset($validated['periode_tahun']))) {
                $bulan = $validated['periode_bulan'] ?? $penilaian->periode_bulan;
                $tahun = $validated['periode_tahun'] ?? $penilaian->periode_tahun;

                $existing = Penilaian::where('petugas_id', $penilaian->petugas_id)
                    ->where('periode_bulan', $bulan)
                    ->where('periode_tahun', $tahun)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existing) {
                    return $this->errorResponse('Evaluation already exists for this period', 400);
                }
            }

            // Recalculate if any score is updated
            if (isset($validated['skor_kehadiran']) ||
                isset($validated['skor_kualitas']) ||
                isset($validated['skor_ketepatan_waktu']) ||
                isset($validated['skor_kebersihan'])) {

                $skorKehadiran = $validated['skor_kehadiran'] ?? $penilaian->skor_kehadiran;
                $skorKualitas = $validated['skor_kualitas'] ?? $penilaian->skor_kualitas;
                $skorKetepatanWaktu = $validated['skor_ketepatan_waktu'] ?? $penilaian->skor_ketepatan_waktu;
                $skorKebersihan = $validated['skor_kebersihan'] ?? $penilaian->skor_kebersihan;

                $totalSkor = $skorKehadiran + $skorKualitas + $skorKetepatanWaktu + $skorKebersihan;
                $rataRata = $totalSkor / 4;

                $validated['total_skor'] = $totalSkor;
                $validated['rata_rata'] = round($rataRata, 2);
                $validated['kategori'] = $this->determineCategory($rataRata);
            }

            // Update penilai_id to current user
            $validated['penilai_id'] = $user->id;

            // Update evaluation
            $penilaian->update($validated);

            // Reload relationships
            $penilaian->load(['petugas', 'penilai']);

            return $this->successResponse(
                new PenilaianResource($penilaian),
                'Evaluation updated successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Evaluation not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update evaluation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete evaluation
     * Only admin/supervisor can delete evaluations
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();

            // Only admin/supervisor can delete evaluations
            if ($user->hasRole('petugas')) {
                return $this->forbiddenResponse('Only admin/supervisor can delete evaluations');
            }

            $penilaian = Penilaian::findOrFail($id);
            $penilaian->delete();

            return $this->successResponse(
                null,
                'Evaluation deleted successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Evaluation not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete evaluation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get evaluation statistics
     * Petugas gets their own stats
     * Admin/Supervisor gets overall stats or filtered by petugas
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Penilaian::query();

            // Role-based filtering
            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id);
            } elseif ($request->has('petugas_id')) {
                $query->where('petugas_id', $request->petugas_id);
            }

            // Date range filter
            if ($request->has('start_month') && $request->has('start_year')) {
                $query->where(function($q) use ($request) {
                    $q->where('periode_tahun', '>', $request->start_year)
                      ->orWhere(function($q2) use ($request) {
                          $q2->where('periode_tahun', $request->start_year)
                             ->where('periode_bulan', '>=', $request->start_month);
                      });
                });
            }

            if ($request->has('end_month') && $request->has('end_year')) {
                $query->where(function($q) use ($request) {
                    $q->where('periode_tahun', '<', $request->end_year)
                      ->orWhere(function($q2) use ($request) {
                          $q2->where('periode_tahun', $request->end_year)
                             ->where('periode_bulan', '<=', $request->end_month);
                      });
                });
            }

            $totalEvaluations = (clone $query)->count();
            $sangat_baikCount = (clone $query)->where('kategori', 'Sangat Baik')->count();
            $baikCount = (clone $query)->where('kategori', 'Baik')->count();
            $cukupCount = (clone $query)->where('kategori', 'Cukup')->count();
            $kurangCount = (clone $query)->where('kategori', 'Kurang')->count();

            $averageKehadiran = (clone $query)->avg('skor_kehadiran');
            $averageKualitas = (clone $query)->avg('skor_kualitas');
            $averageKetepatanWaktu = (clone $query)->avg('skor_ketepatan_waktu');
            $averageKebersihan = (clone $query)->avg('skor_kebersihan');
            $averageTotal = (clone $query)->avg('rata_rata');

            return $this->successResponse([
                'total_evaluations' => $totalEvaluations,
                'by_category' => [
                    'sangat_baik' => $sangat_baikCount,
                    'baik' => $baikCount,
                    'cukup' => $cukupCount,
                    'kurang' => $kurangCount,
                ],
                'averages' => [
                    'kehadiran' => $averageKehadiran ? round($averageKehadiran, 2) : null,
                    'kualitas' => $averageKualitas ? round($averageKualitas, 2) : null,
                    'ketepatan_waktu' => $averageKetepatanWaktu ? round($averageKetepatanWaktu, 2) : null,
                    'kebersihan' => $averageKebersihan ? round($averageKebersihan, 2) : null,
                    'total' => $averageTotal ? round($averageTotal, 2) : null,
                ],
            ], 'Statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get latest evaluation for a petugas
     */
    public function latest(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Penilaian::with(['petugas', 'penilai']);

            // Role-based filtering
            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id);
            } elseif ($request->has('petugas_id')) {
                $query->where('petugas_id', $request->petugas_id);
            } else {
                return $this->errorResponse('petugas_id is required for admin/supervisor', 400);
            }

            $penilaian = $query->orderBy('periode_tahun', 'desc')
                              ->orderBy('periode_bulan', 'desc')
                              ->first();

            if (!$penilaian) {
                return $this->notFoundResponse('No evaluation found');
            }

            return $this->successResponse(
                new PenilaianResource($penilaian),
                'Latest evaluation retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve latest evaluation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get evaluations history (trend over time)
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Penilaian::query();

            // Role-based filtering
            if ($user->hasRole('petugas')) {
                $query->where('petugas_id', $user->id);
            } elseif ($request->has('petugas_id')) {
                $query->where('petugas_id', $request->petugas_id);
            } else {
                return $this->errorResponse('petugas_id is required for admin/supervisor', 400);
            }

            // Optional limit for number of records
            $limit = $request->get('limit', 12); // Default last 12 months

            $evaluations = $query->orderBy('periode_tahun', 'desc')
                                 ->orderBy('periode_bulan', 'desc')
                                 ->limit($limit)
                                 ->get();

            // Format data for chart/trend display
            $history = $evaluations->map(function($item) {
                return [
                    'period' => $item->periode_tahun . '-' . str_pad($item->periode_bulan, 2, '0', STR_PAD_LEFT),
                    'periode_bulan' => $item->periode_bulan,
                    'periode_tahun' => $item->periode_tahun,
                    'skor_kehadiran' => $item->skor_kehadiran,
                    'skor_kualitas' => $item->skor_kualitas,
                    'skor_ketepatan_waktu' => $item->skor_ketepatan_waktu,
                    'skor_kebersihan' => $item->skor_kebersihan,
                    'rata_rata' => $item->rata_rata,
                    'kategori' => $item->kategori,
                ];
            })->reverse()->values(); // Reverse to show oldest to newest

            return $this->successResponse(
                $history,
                'Evaluation history retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve evaluation history: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Determine category based on average score
     *
     * @param float $rataRata
     * @return string
     */
    private function determineCategory(float $rataRata): string
    {
        if ($rataRata >= 85) {
            return 'Sangat Baik';
        } elseif ($rataRata >= 70) {
            return 'Baik';
        } elseif ($rataRata >= 60) {
            return 'Cukup';
        } else {
            return 'Kurang';
        }
    }
}
