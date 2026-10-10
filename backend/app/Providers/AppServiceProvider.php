<?php

namespace App\Providers;

use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
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
        JsonResource::withoutWrapping();

        $this->app->booted(function () {
            $guard = Auth::guard('web');

            if ($guard instanceof SessionGuard) {
                $guard->setRememberDuration((int) config('auth.remember_minutes'));
            }
        });
    }
}
