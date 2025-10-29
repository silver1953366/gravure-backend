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
        // Les services spécifiques à votre application vont ici.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Les opérations de démarrage de l'application vont ici.
    }
}
