<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
  public function store(Request $request): RedirectResponse
{
    $request->validate([
        'first_name' => ['required', 'string', 'max:100'],
        'last_name' => ['required', 'string', 'max:100'],
        'school_id' => ['required', 'string', 'max:255', 'unique:users'],
        'role' => ['required', 'string', 'in:student,professor'],
        
        // CONDITIONAL VALIDATION: Only required if role is student
        'program' => [$request->role === 'student' ? 'required' : 'nullable', 'string'],
        'year_level' => [$request->role === 'student' ? 'required' : 'nullable', 'integer', 'between:1,4'],
        
        'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
    ]);

    $user = User::create([
        'first_name' => strtoupper($request->first_name),
        'last_name' => strtoupper($request->last_name),
        'name' => strtoupper($request->first_name . ' ' . $request->last_name),
        'email' => $request->email,
        'school_id' => $request->school_id,
        'role' => $request->role,
        // Save these as null for Professors
        'program' => ($request->role === 'student') ? $request->program : null,
        'year_level' => ($request->role === 'student') ? $request->year_level : null,
        'password' => \Hash::make($request->password),
    ]);

    event(new \Illuminate\Auth\Events\Registered($user));
    Auth::login($user);

    return $user->role === 'professor' 
        ? redirect()->route('professor.dashboard') 
        : redirect()->route('student.dashboard');
}
}