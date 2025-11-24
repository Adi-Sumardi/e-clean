<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * CacheService - Centralized caching strategy untuk aplikasi E-Clean
 *
 * Cache Strategy:
 * - Dashboard stats: 5 minutes
 * - Chart data: 10 minutes
 * - User data: 15 minutes
 * - Static data (lokasi, jadwal): 30 minutes
 */
class CacheService
{
    /**
     * Cache TTL constants (in seconds)
     */
    const DASHBOARD_TTL = 300;      // 5 minutes
    const CHART_TTL = 600;          // 10 minutes
    const USER_TTL = 900;           // 15 minutes
    const STATIC_TTL = 1800;        // 30 minutes
    const LONG_TTL = 3600;          // 1 hour

    /**
     * Get cached dashboard stats dengan auto-refresh
     */
    public static function getDashboardStats(string $role, callable $callback): mixed
    {
        $key = "dashboard-stats-{$role}-" . now()->format('Y-m-d-H-i');
        return Cache::remember($key, self::DASHBOARD_TTL, $callback);
    }

    /**
     * Get cached chart data
     */
    public static function getChartData(string $chartName, callable $callback): mixed
    {
        $key = "chart-{$chartName}-" . now()->format('Y-m-d-H');
        return Cache::remember($key, self::CHART_TTL, $callback);
    }

    /**
     * Get cached user count by role
     */
    public static function getUserCount(string $role): int
    {
        $key = "user-count-{$role}-" . now()->format('Y-m-d-H');
        return Cache::remember($key, self::USER_TTL, function () use ($role) {
            return \App\Models\User::whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            })->count();
        });
    }

    /**
     * Get cached active lokasi count
     */
    public static function getActiveLokasiCount(): int
    {
        $key = "lokasi-active-count-" . now()->format('Y-m-d-H');
        return Cache::remember($key, self::STATIC_TTL, function () {
            return \App\Models\Lokasi::where('is_active', true)->count();
        });
    }

    /**
     * Clear all dashboard caches
     */
    public static function clearDashboardCache(): void
    {
        Cache::tags(['dashboard'])->flush();
    }

    /**
     * Clear specific role dashboard cache
     */
    public static function clearRoleCache(string $role): void
    {
        Cache::forget("dashboard-stats-{$role}-" . now()->format('Y-m-d-H-i'));
    }

    /**
     * Clear all caches
     */
    public static function clearAll(): void
    {
        Cache::flush();
    }

    /**
     * Get cached trend data for charts (last N days)
     */
    public static function getTrendData(string $model, string $column, int $days = 7): array
    {
        $key = "trend-{$model}-{$column}-{$days}-" . now()->format('Y-m-d');

        return Cache::remember($key, self::CHART_TTL, function () use ($model, $column, $days) {
            $data = [];
            $modelClass = "App\\Models\\" . $model;

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $count = $modelClass::whereDate($column, $date)->count();
                $data[] = $count;
            }

            return $data;
        });
    }

    /**
     * Get cached cumulative trend (total up to each day)
     */
    public static function getCumulativeTrend(string $model, string $column, int $days = 7): array
    {
        $key = "cumulative-trend-{$model}-{$column}-{$days}-" . now()->format('Y-m-d');

        return Cache::remember($key, self::CHART_TTL, function () use ($model, $column, $days) {
            $data = [];
            $modelClass = "App\\Models\\" . $model;

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $count = $modelClass::whereDate($column, '<=', $date)->count();
                $data[] = $count;
            }

            return $data;
        });
    }

    /**
     * Warm up cache - pre-populate frequently accessed data
     */
    public static function warmUp(): void
    {
        // Warm up dashboard stats for all roles
        self::getUserCount('petugas');
        self::getUserCount('admin');
        self::getUserCount('supervisor');
        self::getUserCount('pengurus');
        self::getActiveLokasiCount();

        // Warm up trend data
        self::getTrendData('ActivityReport', 'tanggal', 7);
        self::getTrendData('JadwalKebersihan', 'tanggal', 7);
        self::getCumulativeTrend('Lokasi', 'created_at', 7);
        self::getCumulativeTrend('User', 'created_at', 7);
    }

    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        return [
            'driver' => config('cache.default'),
            'ttl' => [
                'dashboard' => self::DASHBOARD_TTL . 's',
                'chart' => self::CHART_TTL . 's',
                'user' => self::USER_TTL . 's',
                'static' => self::STATIC_TTL . 's',
            ],
        ];
    }
}
