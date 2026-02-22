<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
  public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();
    $request->session()->regenerate();
    
    // Use redirect()->route() instead of intended() to clear the 404 memory
    if ($request->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('student.dashboard');
}
    /**
     * Destroy an authenticated session.
     */
   public function destroy(Request $request)
{
    // Mark student as not present before logging out
    if (auth()->user()->role === 'student') {
        \DB::table('class_student')
            ->where('user_id', auth()->id())
            ->update(['is_present' => false]);
    }

    // Standard logout logic
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
}
}
