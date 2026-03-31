<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        if (env('APP_ENV') === 'production' || app()->environment(['production', 'staging'])) {
            URL::forceScheme('https');
        }

        // For local development with Caddy reverse proxy
        if ($this->app->runningInConsole() === false && request()->getScheme() === 'https') {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
        }
    }
}
