<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\LaporanKeterlambatan;
use App\Models\Lokasi;
use App\Models\Penilaian;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get comprehensive dashboard statistics
     * Returns different data based on user role
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->hasRole('petugas')) {
                return $this->getPetugasDashboard($user);
            } else {
                // Admin, Supervisor, Pengurus
                return $this->getAdminDashboard($user, $request);
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve dashboard data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get dashboard data for Petugas
     */
    private function getPetugasDashboard(User $user): JsonResponse
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;

        // Today's schedule
        $todaySchedule = JadwalKebersihan::where('petugas_id', $user->id)
            ->whereDate('tanggal', $today)
            ->with('lokasi')
            ->get();

        // Today's activity reports
        $todayReports = ActivityReport::where('petugas_id', $user->id)
            ->whereDate('tanggal', $today)
            ->get();

        // This month's statistics
        $monthlyReports = ActivityReport::where('petugas_id', $user->id)
            ->whereMonth('tanggal', $thisMonth)
            ->whereYear('tanggal', $thisYear)
            ->get();

        // This month's late reports
        $monthlyLateReports = LaporanKeterlambatan::where('petugas_id', $user->id)
            ->whereMonth('tanggal', $thisMonth)
            ->whereYear('tanggal', $thisYear)
            ->get();

        // Latest evaluation
        $latestEvaluation = Penilaian::where('petugas_id', $user->id)
            ->orderBy('periode_tahun', 'desc')
            ->orderBy('periode_bulan', 'desc')
            ->first();

        // Pending reports (draft or submitted)
        $pendingReports = ActivityReport::where('petugas_id', $user->id)
            ->whereIn('status', ['draft', 'submitted'])
            ->count();

        return $this->successResponse([
            'user_info' => [
                'name' => $user->name,
                'role' => $user->roles->pluck('name')->first(),
            ],
            'today' => [
                'date' => $today->format('Y-m-d'),
                'schedule_count' => $todaySchedule->count(),
                'schedules' => $todaySchedule->map(function($jadwal) {
                    return [
                        'id' => $jadwal->id,
                        'shift' => $jadwal->shift,
                        'jam_mulai' => $jadwal->jam_mulai,
                        'jam_selesai' => $jadwal->jam_selesai,
                        'lokasi' => [
                            'id' => $jadwal->lokasi->id,
                            'nama_lokasi' => $jadwal->lokasi->nama_lokasi,
                            'kategori' => $jadwal->lokasi->kategori,
                        ],
                    ];
                }),
                'reports' => [
                    'total' => $todayReports->count(),
                    'completed' => $todayReports->whereIn('status', ['approved', 'submitted'])->count(),
                    'draft' => $todayReports->where('status', 'draft')->count(),
                ],
            ],
            'monthly_stats' => [
                'month' => $thisMonth,
                'year' => $thisYear,
                'performance' => [
                    'total_tasks' => $monthlyReports->count(),
                    'completed_on_time' => $monthlyReports->where('status', 'approved')->count() - $monthlyLateReports->count(),
                    'late_submissions' => $monthlyLateReports->count(),
                    'pending' => $monthlyReports->whereIn('status', ['draft', 'submitted'])->count(),
                ],
                'reports' => [
                    'total' => $monthlyReports->count(),
                    'draft' => $monthlyReports->where('status', 'draft')->count(),
                    'submitted' => $monthlyReports->where('status', 'submitted')->count(),
                    'approved' => $monthlyReports->where('status', 'approved')->count(),
                    'rejected' => $monthlyReports->where('status', 'rejected')->count(),
                    'average_rating' => $monthlyReports->whereNotNull('rating')->avg('rating')
                        ? round($monthlyReports->whereNotNull('rating')->avg('rating'), 2)
                        : null,
                ],
            ],
            'pending_tasks' => [
                'pending_reports' => $pendingReports,
            ],
            'latest_evaluation' => $latestEvaluation ? [
                'period' => $latestEvaluation->periode_tahun . '-' . str_pad($latestEvaluation->periode_bulan, 2, '0', STR_PAD_LEFT),
                'rata_rata' => $latestEvaluation->rata_rata,
                'kategori' => $latestEvaluation->kategori,
                'scores' => [
                    'kehadiran' => $latestEvaluation->skor_kehadiran,
                    'kualitas' => $latestEvaluation->skor_kualitas,
                    'ketepatan_waktu' => $latestEvaluation->skor_ketepatan_waktu,
                    'kebersihan' => $latestEvaluation->skor_kebersihan,
                ],
            ] : null,
        ], 'Dashboard data retrieved successfully');
    }

    /**
     * Get dashboard data for Admin/Supervisor/Pengurus
     */
    private function getAdminDashboard(User $user, Request $request): JsonResponse
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;

        // Overall counts
        $totalPetugas = User::role('petugas')->count();
        $totalLokasi = Lokasi::where('is_active', true)->count();

        // Today's statistics
        $todaySchedules = JadwalKebersihan::whereDate('tanggal', $today)->count();
        $todayReports = ActivityReport::whereDate('tanggal', $today)->count();
        $todayCompleted = ActivityReport::whereDate('tanggal', $today)
            ->whereIn('status', ['approved', 'submitted'])
            ->count();
        $todayLate = LaporanKeterlambatan::whereDate('tanggal', $today)
            ->count();

        // This month's activity reports
        $monthlyReports = ActivityReport::whereMonth('tanggal', $thisMonth)
            ->whereYear('tanggal', $thisYear)
            ->get();

        // Pending approvals (submitted reports)
        $pendingApprovals = ActivityReport::where('status', 'submitted')->count();

        // Top performers this month (by average rating) - optimized to avoid N+1
        $topPerformers = DB::table('activity_reports')
            ->join('users', 'activity_reports.petugas_id', '=', 'users.id')
            ->whereMonth('activity_reports.tanggal', $thisMonth)
            ->whereYear('activity_reports.tanggal', $thisYear)
            ->where('activity_reports.status', 'approved')
            ->whereNotNull('activity_reports.rating')
            ->select(
                'activity_reports.petugas_id',
                'users.name',
                DB::raw('AVG(activity_reports.rating) as average_rating'),
                DB::raw('COUNT(*) as total_reports')
            )
            ->groupBy('activity_reports.petugas_id', 'users.name')
            ->orderByDesc('average_rating')
            ->limit(5)
            ->get()
            ->map(function($performer) {
                return [
                    'petugas_id' => $performer->petugas_id,
                    'name' => $performer->name,
                    'average_rating' => round($performer->average_rating, 2),
                    'total_reports' => $performer->total_reports,
                ];
            });

        // Recent activity reports
        $recentReports = ActivityReport::with(['petugas', 'lokasi'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($report) {
                return [
                    'id' => $report->id,
                    'petugas_name' => $report->petugas->name,
                    'lokasi_name' => $report->lokasi->nama_lokasi,
                    'tanggal' => $report->tanggal,
                    'status' => $report->status,
                    'rating' => $report->rating,
                    'created_at' => $report->created_at->toISOString(),
                ];
            });

        // Task completion rate this month
        $expectedTasks = JadwalKebersihan::whereMonth('tanggal', $thisMonth)
            ->whereYear('tanggal', $thisYear)
            ->count();
        $completedTasks = ActivityReport::whereMonth('tanggal', $thisMonth)
            ->whereYear('tanggal', $thisYear)
            ->whereIn('status', ['approved', 'submitted'])
            ->count();
        $completionRate = $expectedTasks > 0
            ? round(($completedTasks / $expectedTasks) * 100, 2)
            : 0;

        // Location coverage (locations cleaned this month)
        $cleanedLocations = ActivityReport::whereMonth('tanggal', $thisMonth)
            ->whereYear('tanggal', $thisYear)
            ->where('status', 'approved')
            ->distinct('lokasi_id')
            ->count();
        $coverageRate = $totalLokasi > 0
            ? round(($cleanedLocations / $totalLokasi) * 100, 2)
            : 0;

        return $this->successResponse([
            'user_info' => [
                'name' => $user->name,
                'role' => $user->roles->pluck('name')->first(),
            ],
            'overview' => [
                'total_petugas' => $totalPetugas,
                'total_lokasi' => $totalLokasi,
                'pending_approvals' => $pendingApprovals,
            ],
            'today' => [
                'date' => $today->format('Y-m-d'),
                'total_schedules' => $todaySchedules,
                'total_reports' => $todayReports,
                'completed_tasks' => $todayCompleted,
                'late_submissions' => $todayLate,
                'completion_rate' => $todaySchedules > 0
                    ? round(($todayCompleted / $todaySchedules) * 100, 2)
                    : 0,
            ],
            'monthly_stats' => [
                'month' => $thisMonth,
                'year' => $thisYear,
                'reports' => [
                    'total' => $monthlyReports->count(),
                    'draft' => $monthlyReports->where('status', 'draft')->count(),
                    'submitted' => $monthlyReports->where('status', 'submitted')->count(),
                    'approved' => $monthlyReports->where('status', 'approved')->count(),
                    'rejected' => $monthlyReports->where('status', 'rejected')->count(),
                    'average_rating' => $monthlyReports->whereNotNull('rating')->avg('rating')
                        ? round($monthlyReports->whereNotNull('rating')->avg('rating'), 2)
                        : null,
                ],
                'completion_rate' => $completionRate,
                'coverage_rate' => $coverageRate,
                'cleaned_locations' => $cleanedLocations,
            ],
            'top_performers' => $topPerformers,
            'recent_reports' => $recentReports,
        ], 'Dashboard data retrieved successfully');
    }

    /**
     * Get summary statistics for charts/graphs
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get date range from request or default to last 30 days
            $startDate = $request->has('start_date')
                ? Carbon::parse($request->start_date)
                : Carbon::now()->subDays(30);
            $endDate = $request->has('end_date')
                ? Carbon::parse($request->end_date)
                : Carbon::now();

            // Filter by petugas if provided (admin/supervisor only)
            $petugasId = null;
            if ($user->hasRole('petugas')) {
                $petugasId = $user->id;
            } elseif ($request->has('petugas_id')) {
                $petugasId = $request->petugas_id;
            }

            // Activity reports trend
            $reportsQuery = ActivityReport::whereBetween('tanggal', [$startDate, $endDate]);
            if ($petugasId) {
                $reportsQuery->where('petugas_id', $petugasId);
            }
            $reportsTrend = $reportsQuery->selectRaw('DATE(tanggal) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Late submission trend
            $lateQuery = LaporanKeterlambatan::whereBetween('tanggal', [$startDate, $endDate]);
            if ($petugasId) {
                $lateQuery->where('petugas_id', $petugasId);
            }
            $lateTrend = $lateQuery->selectRaw('DATE(tanggal) as date, COUNT(*) as count,
                    SUM(CASE WHEN status = "terlambat" THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = "tidak_selesai" THEN 1 ELSE 0 END) as incomplete')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Reports by status
            $reportsByStatus = ActivityReport::whereBetween('tanggal', [$startDate, $endDate]);
            if ($petugasId) {
                $reportsByStatus->where('petugas_id', $petugasId);
            }
            $reportsByStatus = $reportsByStatus->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status');

            // Average rating trend
            $ratingQuery = ActivityReport::whereBetween('tanggal', [$startDate, $endDate])
                ->whereNotNull('rating');
            if ($petugasId) {
                $ratingQuery->where('petugas_id', $petugasId);
            }
            $ratingTrend = $ratingQuery->selectRaw('DATE(tanggal) as date, AVG(rating) as average_rating')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function($item) {
                    return [
                        'date' => $item->date,
                        'average_rating' => round($item->average_rating, 2),
                    ];
                });

            return $this->successResponse([
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ],
                'reports_trend' => $reportsTrend,
                'late_submissions_trend' => $lateTrend,
                'reports_by_status' => $reportsByStatus,
                'rating_trend' => $ratingTrend,
            ], 'Statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get leaderboard data
     * Ranking petugas by performance metrics
     */
    public function leaderboard(Request $request): JsonResponse
    {
        try {
            $thisMonth = $request->get('month', Carbon::now()->month);
            $thisYear = $request->get('year', Carbon::now()->year);
            $limit = $request->get('limit', 10);

            // === OPTIMIZED: Bulk queries instead of N+1 ===

            // Get all petugas IDs first
            $petugasIds = User::role('petugas')->pluck('id');

            // Bulk query #1: Activity reports aggregated by petugas (1 query instead of N)
            $reportsStats = ActivityReport::whereIn('petugas_id', $petugasIds)
                ->whereMonth('tanggal', $thisMonth)
                ->whereYear('tanggal', $thisYear)
                ->selectRaw('
                    petugas_id,
                    COUNT(*) as total_reports,
                    SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_reports,
                    AVG(CASE WHEN rating IS NOT NULL THEN rating END) as average_rating
                ')
                ->groupBy('petugas_id')
                ->get()
                ->keyBy('petugas_id');

            // Bulk query #2: Late submissions count by petugas (1 query instead of N)
            $lateStats = LaporanKeterlambatan::whereIn('petugas_id', $petugasIds)
                ->whereMonth('tanggal', $thisMonth)
                ->whereYear('tanggal', $thisYear)
                ->selectRaw('petugas_id, COUNT(*) as late_count')
                ->groupBy('petugas_id')
                ->get()
                ->keyBy('petugas_id');

            // Bulk query #3: Evaluations by petugas (1 query instead of N)
            $evaluations = Penilaian::whereIn('petugas_id', $petugasIds)
                ->where('periode_bulan', $thisMonth)
                ->where('periode_tahun', $thisYear)
                ->select('petugas_id', 'rata_rata', 'kategori', 'total_skor')
                ->get()
                ->keyBy('petugas_id');

            // Bulk query #4: Get petugas with names (1 query)
            $petugasList = User::role('petugas')
                ->select('id', 'name')
                ->get();

            // Now build leaderboard from cached data (only 4 queries total!)
            $leaderboard = $petugasList->map(function($user) use ($reportsStats, $lateStats, $evaluations) {
                $reportData = $reportsStats->get($user->id);
                $lateData = $lateStats->get($user->id);
                $evaluation = $evaluations->get($user->id);

                $totalReports = $reportData ? $reportData->total_reports : 0;
                $approvedReports = $reportData ? $reportData->approved_reports : 0;
                $averageRating = $reportData ? ($reportData->average_rating ?? 0) : 0;
                $lateCount = $lateData ? $lateData->late_count : 0;
                $evaluationScore = $evaluation ? $evaluation->rata_rata : 0;

                // Calculate punctuality rate
                $punctualityRate = $totalReports > 0
                    ? (($totalReports - $lateCount) / $totalReports) * 100
                    : 100;

                // Calculate overall score (weighted average)
                $overallScore = (
                    ($averageRating * 0.3) +
                    ($punctualityRate * 0.3) +
                    ($evaluationScore * 0.4)
                );

                return [
                    'petugas_id' => $user->id,
                    'name' => $user->name,
                    'total_reports' => (int) $totalReports,
                    'approved_reports' => (int) $approvedReports,
                    'average_rating' => round($averageRating, 2),
                    'punctuality_rate' => round($punctualityRate, 2),
                    'evaluation_score' => round($evaluationScore, 2),
                    'evaluation_kategori' => $evaluation ? $evaluation->kategori : null,
                    'overall_score' => round($overallScore, 2),
                ];
            })
            ->sortByDesc('overall_score')
            ->take($limit)
            ->values()
            ->map(function($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });

            return $this->successResponse([
                'period' => [
                    'month' => $thisMonth,
                    'year' => $thisYear,
                ],
                'leaderboard' => $leaderboard,
            ], 'Leaderboard retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve leaderboard: ' . $e->getMessage(), 500);
        }
    }
}
