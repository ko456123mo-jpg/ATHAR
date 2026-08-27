<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::define('manage-tools', fn (User $user): bool => $user->isAdministrator());
        Gate::define('manage-agents', fn (User $user): bool => $user->isAdministrator());
        Gate::define('review-findings', fn (User $user): bool => $user->hasRole('reviewer', 'administrator'));
        Gate::define('view-audit-log', fn (User $user): bool => $user->hasRole('administrator', 'reviewer'));
    }
}
