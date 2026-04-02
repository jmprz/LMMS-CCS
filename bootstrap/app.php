<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
   ->withMiddleware(function (Middleware $middleware) {
        // 1. Register your Custom Middleware Aliases
        $middleware->alias([
            'admin'   => \App\Http\Middleware\IsAdmin::class,
            'student' => \App\Http\Middleware\IsStudent::class,
            'professor' => \App\Http\Middleware\IsProfessor::class,
        ]);

        // 2. Disable CSRF for Electron Logging
        $middleware->validateCsrfTokens(except: [
            'student/log-behavior',
        ]);

        // 3. IMPORTANT: Disable Auth Redirects for this route
        // This stops the "405 Method Not Allowed" caused by 
        // redirecting unauthenticated Electron requests to /login
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('student/log-behavior')) {
                return null; // Do NOT redirect; let the request hit the controller
            }
            return route('login'); // Redirect everyone else to login
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
