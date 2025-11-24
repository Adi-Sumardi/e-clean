<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * MonitoringService - Application monitoring dan health checks
 *
 * Features:
 * - Health checks
 * - Performance monitoring
 * - Error tracking
 * - System metrics
 */
class MonitoringService
{
    /**
     * Perform comprehensive health check
     */
    public static function healthCheck(): array
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toDateTimeString(),
            'checks' => [],
        ];

        // Database check
        $health['checks']['database'] = self::checkDatabase();

        // Cache check
        $health['checks']['cache'] = self::checkCache();

        // Storage check
        $health['checks']['storage'] = self::checkStorage();

        // Queue check
        $health['checks']['queue'] = self::checkQueue();

        // Determine overall status
        $failed = collect($health['checks'])->where('status', 'unhealthy')->count();

        if ($failed > 0) {
            $health['status'] = 'unhealthy';
        } elseif ($failed === 0 && collect($health['checks'])->where('status', 'degraded')->count() > 0) {
            $health['status'] = 'degraded';
        }

        return $health;
    }

    /**
     * Check database connectivity
     */
    private static function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $duration = (microtime(true) - $start) * 1000;

            return [
                'status' => 'healthy',
                'response_time_ms' => round($duration, 2),
                'driver' => config('database.default'),
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache system
     */
    private static function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key = 'health_check_' . time();

            Cache::put($key, 'test', 60);
            $value = Cache::get($key);
            Cache::forget($key);

            $duration = (microtime(true) - $start) * 1000;

            if ($value === 'test') {
                return [
                    'status' => 'healthy',
                    'response_time_ms' => round($duration, 2),
                    'driver' => config('cache.default'),
                ];
            }

            return [
                'status' => 'degraded',
                'message' => 'Cache not working properly',
            ];
        } catch (\Exception $e) {
            Log::error('Cache health check failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage availability
     */
    private static function checkStorage(): array
    {
        try {
            $storagePath = storage_path('app');
            $publicPath = storage_path('app/public');

            $checks = [
                'writable' => is_writable($storagePath),
                'public_linked' => is_link(public_path('storage')),
                'disk_space' => disk_free_space($storagePath),
            ];

            $status = $checks['writable'] ? 'healthy' : 'unhealthy';

            return [
                'status' => $status,
                'checks' => $checks,
                'free_space_mb' => round($checks['disk_space'] / 1024 / 1024, 2),
            ];
        } catch (\Exception $e) {
            Log::error('Storage health check failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue system
     */
    private static function checkQueue(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            $pendingJobs = DB::table('jobs')->count();

            return [
                'status' => 'healthy',
                'failed_jobs' => $failedJobs,
                'pending_jobs' => $pendingJobs,
                'driver' => config('queue.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'degraded',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system metrics
     */
    public static function getMetrics(): array
    {
        return [
            'timestamp' => now()->toDateTimeString(),
            'database' => self::getDatabaseMetrics(),
            'application' => self::getApplicationMetrics(),
            'performance' => self::getPerformanceMetrics(),
        ];
    }

    /**
     * Get database metrics
     */
    private static function getDatabaseMetrics(): array
    {
        try {
            return [
                'users_total' => DB::table('users')->count(),
                'lokasi_total' => DB::table('lokasis')->count(),
                'reports_total' => DB::table('activity_reports')->count(),
                'jadwal_total' => DB::table('jadwal_kebersihanans')->count(),
                'reports_today' => DB::table('activity_reports')
                    ->whereDate('tanggal', today())
                    ->count(),
                'reports_this_month' => DB::table('activity_reports')
                    ->whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)
                    ->count(),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get application metrics
     */
    private static function getApplicationMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];
    }

    /**
     * Get performance metrics
     */
    private static function getPerformanceMetrics(): array
    {
        return [
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit'),
            'uptime_seconds' => self::getUptime(),
        ];
    }

    /**
     * Get application uptime (since cache)
     */
    private static function getUptime(): int
    {
        $startTime = Cache::remember('app_start_time', 86400, function () {
            return time();
        });

        return time() - $startTime;
    }

    /**
     * Log application event
     */
    public static function logEvent(string $event, array $data = [], string $level = 'info'): void
    {
        Log::log($level, "Application Event: {$event}", array_merge($data, [
            'timestamp' => now()->toDateTimeString(),
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]));
    }

    /**
     * Track performance metric
     */
    public static function trackPerformance(string $metric, float $value, array $tags = []): void
    {
        Log::info("Performance Metric: {$metric}", [
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Could integrate dengan external monitoring services (New Relic, DataDog, etc)
        // self::sendToMonitoringService($metric, $value, $tags);
    }

    /**
     * Get error statistics
     */
    public static function getErrorStats(int $days = 7): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            return ['error' => 'Log file not found'];
        }

        // Simple error count (could be improved with better log parsing)
        $content = file_get_contents($logPath);
        $errors = substr_count($content, '[ERROR]');
        $warnings = substr_count($content, '[WARNING]');
        $criticals = substr_count($content, '[CRITICAL]');

        return [
            'days' => $days,
            'errors' => $errors,
            'warnings' => $warnings,
            'criticals' => $criticals,
            'total' => $errors + $warnings + $criticals,
        ];
    }

    /**
     * Clear old logs
     */
    public static function clearOldLogs(int $daysToKeep = 30): int
    {
        $logPath = storage_path('logs');
        $files = glob($logPath . '/laravel-*.log');
        $deleted = 0;

        foreach ($files as $file) {
            $fileTime = filemtime($file);
            $age = (time() - $fileTime) / 86400; // Convert to days

            if ($age > $daysToKeep) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
