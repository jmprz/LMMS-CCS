<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <--- YOU NEED THIS LINE!

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
    // Only force HTTPS if you are in production OR if you are using a tunnel 
    // that you know is serving HTTPS.
    if (config('app.env') === 'production' || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
    }
}
}