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
    // 1. Authenticate the user
    $request->authenticate();
    
    // 2. Regenerate the session
    $request->session()->regenerate();
    
    // 3. Debug: Check if the role is what you expect
    $user = $request->user();
    
    // 4. Force strict role checking
    if ($user && $user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    // Default to student
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
