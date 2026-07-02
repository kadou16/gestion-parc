<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        Gate::before(function ($user, string $ability) {
            return method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($ability) ? true : null;
        });

        Blade::if('role', function (string ...$roles) {
            $user = auth()->user();

            return $user && method_exists($user, 'hasRole') && $user->hasRole($roles);
        });
    }
}
