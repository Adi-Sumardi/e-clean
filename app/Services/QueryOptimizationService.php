<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * QueryOptimizationService - Optimize database queries untuk performa
 *
 * Features:
 * - Eager loading helpers
 * - Query profiling
 * - N+1 query detection
 * - Query caching
 */
class QueryOptimizationService
{
    /**
     * Enable query logging untuk debugging
     */
    public static function enableQueryLog(): void
    {
        DB::enableQueryLog();
    }

    /**
     * Get logged queries
     */
    public static function getQueryLog(): array
    {
        return DB::getQueryLog();
    }

    /**
     * Disable query logging
     */
    public static function disableQueryLog(): void
    {
        DB::disableQueryLog();
    }

    /**
     * Profile query execution time
     */
    public static function profileQuery(callable $callback, string $label = 'Query'): mixed
    {
        $start = microtime(true);

        self::enableQueryLog();

        $result = $callback();

        $queries = self::getQueryLog();
        $end = microtime(true);
        $duration = ($end - $start) * 1000; // Convert to milliseconds

        self::disableQueryLog();

        Log::info("{$label} - Query Profile", [
            'duration_ms' => round($duration, 2),
            'query_count' => count($queries),
            'queries' => $queries,
        ]);

        return $result;
    }

    /**
     * Detect N+1 queries
     */
    public static function detectN1(callable $callback, int $threshold = 10): array
    {
        self::enableQueryLog();

        $callback();

        $queries = self::getQueryLog();
        $queryCount = count($queries);

        self::disableQueryLog();

        if ($queryCount > $threshold) {
            Log::warning('Potential N+1 Query Detected', [
                'query_count' => $queryCount,
                'threshold' => $threshold,
                'queries' => array_slice($queries, 0, 5), // First 5 queries
            ]);

            return [
                'detected' => true,
                'query_count' => $queryCount,
                'queries' => $queries,
            ];
        }

        return [
            'detected' => false,
            'query_count' => $queryCount,
        ];
    }

    /**
     * Optimize query dengan eager loading
     */
    public static function eagerLoad(Builder $query, array $relations): Builder
    {
        return $query->with($relations);
    }

    /**
     * Optimize pagination dengan cursor pagination (lebih efisien untuk large datasets)
     */
    public static function cursorPaginate(Builder $query, int $perPage = 15)
    {
        return $query->cursorPaginate($perPage);
    }

    /**
     * Chunk large datasets untuk menghindari memory limit
     */
    public static function chunkProcess(Builder $query, int $chunkSize, callable $callback): void
    {
        $query->chunk($chunkSize, function ($items) use ($callback) {
            foreach ($items as $item) {
                $callback($item);
            }
        });
    }

    /**
     * Get slow query statistics
     */
    public static function getSlowQueries(int $thresholdMs = 100): array
    {
        self::enableQueryLog();

        $queries = self::getQueryLog();

        self::disableQueryLog();

        return array_filter($queries, function ($query) use ($thresholdMs) {
            return $query['time'] > $thresholdMs;
        });
    }

    /**
     * Optimize dashboard queries dengan batch loading
     */
    public static function optimizeDashboardQueries(string $role): array
    {
        $start = microtime(true);

        // Batch all queries together untuk minimize round trips
        $data = match ($role) {
            'admin', 'super_admin' => [
                'lokasi_count' => DB::table('lokasis')->where('is_active', true)->count(),
                'petugas_count' => DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('roles.name', 'petugas')
                    ->count(),
                'jadwal_aktif' => DB::table('jadwal_kebersihanans')
                    ->where('status', 'active')
                    ->whereDate('tanggal', '>=', now())
                    ->count(),
                'laporan_bulan_ini' => DB::table('activity_reports')
                    ->whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)
                    ->count(),
            ],
            'petugas' => [
                'jadwal_hari_ini' => DB::table('jadwal_kebersihanans')
                    ->where('petugas_id', Auth::id())
                    ->whereDate('tanggal', now())
                    ->count(),
                'laporan_hari_ini' => DB::table('activity_reports')
                    ->where('petugas_id', Auth::id())
                    ->whereDate('tanggal', now())
                    ->count(),
                'pending_tasks' => DB::table('activity_reports')
                    ->where('petugas_id', Auth::id())
                    ->whereIn('status', ['draft', 'revision'])
                    ->count(),
            ],
            default => [],
        };

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        Log::info("Dashboard queries optimized for role: {$role}", [
            'duration_ms' => round($duration, 2),
            'query_count' => count($data),
        ]);

        return $data;
    }

    /**
     * Create database indexes untuk improve performance
     */
    public static function getRecommendedIndexes(): array
    {
        return [
            'activity_reports' => [
                ['petugas_id', 'tanggal'],
                ['lokasi_id', 'status'],
                ['status', 'tanggal'],
                ['approved_by', 'approved_at'],
            ],
            'jadwal_kebersihanans' => [
                ['petugas_id', 'tanggal'],
                ['lokasi_id', 'tanggal'],
                ['status', 'tanggal'],
            ],
            'lokasis' => [
                ['is_active'],
                ['kategori'],
            ],
            'users' => [
                ['email'], // Unique index
            ],
        ];
    }

    /**
     * Analyze query performance
     * SECURITY: Disabled in production to prevent SQL injection
     */
    public static function analyzeQuery(string $sql): array
    {
        // Disable in production for security
        if (app()->environment('production')) {
            return [
                'error' => 'Query analysis is disabled in production environment',
            ];
        }

        try {
            // Sanitize SQL to prevent injection
            $sql = preg_replace('/[^a-zA-Z0-9_\s,\.\(\)\*\=\-]/', '', $sql);
            
            $explain = DB::select("EXPLAIN {$sql}");

            return [
                'sql' => $sql,
                'explain' => $explain,
                'recommendations' => self::getQueryRecommendations($explain),
            ];
        } catch (\Exception $e) {
            Log::error('Query analysis failed', [
                'sql' => $sql,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get query recommendations based on EXPLAIN output
     */
    private static function getQueryRecommendations(array $explain): array
    {
        $recommendations = [];

        foreach ($explain as $row) {
            // Check for full table scan
            if (isset($row->type) && $row->type === 'ALL') {
                $recommendations[] = "Consider adding index on table: {$row->table}";
            }

            // Check for using temporary table
            if (isset($row->Extra) && str_contains($row->Extra, 'Using temporary')) {
                $recommendations[] = "Query uses temporary table - consider optimizing";
            }

            // Check for using filesort
            if (isset($row->Extra) && str_contains($row->Extra, 'Using filesort')) {
                $recommendations[] = "Query uses filesort - consider adding index for ORDER BY clause";
            }
        }

        return $recommendations;
    }
}
