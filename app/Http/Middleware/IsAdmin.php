<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
  public function handle(Request $request, Closure $next)
{
    // Check if user is logged in and IS an admin
    if (auth()->check() && auth()->user()->role === 'admin') {
        return $next($request);
    }

    // If they aren't an admin, do NOT automatically force them to student.dashboard.
    // Instead, return a 403 Forbidden or redirect to a public page.
    abort(403, 'Unauthorized access.'); 
}
}
