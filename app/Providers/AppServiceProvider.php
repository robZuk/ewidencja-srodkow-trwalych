<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // In production the app runs behind the Mikr.us edge that terminates TLS
        // and talks to the container over HTTP. Force https so generated URLs
        // (incl. Livewire's update endpoint) don't become mixed content.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
