<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);
        
        // Configurar Carbon para usar español y zona horaria de Lima, Perú
        Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_PE.UTF-8', 'es_PE', 'es', 'Spanish_Peru.1252');
    }
}
