<?php

namespace App\Providers;

use App\Domain\Forensics\Services\AuditLogger;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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
        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->save();
                app(AuditLogger::class)->record($event->user, 'auth.login', $event->user, metadata: ['guard' => $event->guard]);
            }
        });
        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->record($event->user, 'auth.logout', $event->user, metadata: ['guard' => $event->guard]);
            }
        });
    }
}
