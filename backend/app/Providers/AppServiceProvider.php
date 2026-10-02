<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\PersonnelSource;
use App\Services\SimulatedPersonnelSource;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PersonnelSource::class, SimulatedPersonnelSource::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-personnel-integration', fn (User $user): bool => $user->is_active
            && in_array($user->role, [UserRole::SystemAdmin, UserRole::AdminSsdm], true));
        Gate::define('view-system-status', fn (User $user): bool => $user->is_active && $user->role === UserRole::SystemAdmin);
        Gate::define('view-discipline-prototype', fn (User $user): bool => $user->is_active
            && in_array($user->role, [UserRole::SystemAdmin, UserRole::AdminSsdm], true));
        Gate::define('manage-references', fn (User $user): bool => $user->is_active
            && in_array($user->role, [UserRole::SystemAdmin, UserRole::AdminSsdm], true));
        Gate::define('manage-reference', fn (User $user, string $type): bool => $type === 'pangkat'
            ? $user->is_active && $user->role === UserRole::SystemAdmin
            : $user->can('manage-references'));
        Model::preventLazyLoading(! $this->app->isProduction());

        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(120)
            ->by((string) ($request->user()?->id ?? $request->ip())));
    }
}
