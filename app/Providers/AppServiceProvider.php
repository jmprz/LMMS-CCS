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
        // For your thesis demo, it's safer to just force it.
        // You can add the 'if' back once everything is on a real server.
        URL::forceScheme('https');
    }
}