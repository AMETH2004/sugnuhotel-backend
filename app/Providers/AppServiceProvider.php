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
        // Railway (et la plupart des hébergeurs) déchiffrent le HTTPS au niveau de leur
        // proxy et transmettent la requête en simple HTTP en interne : sans cette ligne,
        // Laravel génère des URLs (photos, liens...) en http:// même si le site est bien
        // servi en https://, ce qui peut être bloqué par le navigateur ("contenu mixte").
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
