<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Models\JadwalKebersihan;
use App\Models\ActivityReport;
use App\Observers\JadwalKebersihanObserver;
use App\Observers\ActivityReportObserver;
use App\Policies\RolePolicy;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure rate limiters
        $this->configureRateLimiting();

        // Register observers for automatic WhatsApp notifications
        JadwalKebersihan::observe(JadwalKebersihanObserver::class);
        ActivityReport::observe(ActivityReportObserver::class);

        // Register Role policy to hide Shield menu from non-admins
        Gate::policy(Role::class, RolePolicy::class);
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // API Rate Limit: 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login Rate Limit: 5 attempts per minute
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email') . $request->ip());
        });

        // WhatsApp API Rate Limit: 10 requests per minute
        RateLimiter::for('whatsapp', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // File Upload Rate Limit: 20 uploads per hour
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });

        // Export Rate Limit: 5 exports per hour
        RateLimiter::for('export', function (Request $request) {
            return Limit::perHour(5)->by($request->user()?->id ?: $request->ip());
        });
    }
}
