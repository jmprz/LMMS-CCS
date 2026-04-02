<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsProfessor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is logged in and IS a professor
        if (auth()->check() && auth()->user()->role === 'professor') {
            return $next($request);
        }

        // Standard security response for unauthorized roles
        abort(403, 'Unauthorized access.'); 
    }
}