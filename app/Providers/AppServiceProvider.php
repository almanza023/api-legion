<?php

namespace App\Providers;

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
        // Configurar autenticación con documento_identidad
        \Illuminate\Support\Facades\Auth::provider('custom_eloquent', function ($app, array $config) {
            return new \App\Auth\CustomUserProvider($app['hash'], $config['model']);
        });
    }
}
