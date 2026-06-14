<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActivated
{
    public function handle(Request $request, Closure $next): Response
    {
        // If the user is authenticated but hasn't completed activation, force redirect
        if (auth()->check() && !auth()->user()->is_activated) {
            if (!$request->routeIs('activation.*')) {
                return redirect()->route('activation.index');
            }
        }

        return $next($request);
    }
}
