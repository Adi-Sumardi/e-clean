<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
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
        // Register observers for automatic WhatsApp notifications
        JadwalKebersihan::observe(JadwalKebersihanObserver::class);
        ActivityReport::observe(ActivityReportObserver::class);

        // Register Role policy to hide Shield menu from non-admins
        Gate::policy(Role::class, RolePolicy::class);
    }
}
