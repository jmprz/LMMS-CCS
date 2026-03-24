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
            'middle_name' => ['nullable', 'string', 'max:100'], // Updated from middle_initial
            'last_name' => ['required', 'string', 'max:100'],
            'school_id' => ['required', 'string', 'max:255', 'unique:' . User::class],
            'role' => ['required', 'string', 'in:student,admin'],
            'year_level' => [$request->role === 'student' ? 'required' : 'nullable', 'integer', 'between:1,4'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Force 'admin' role if the user chose 'teacher' OR if they are the first user
        $isFirstUser = User::count() === 0;
        $assignedRole = ($isFirstUser || $request->role === 'admin') ? 'admin' : 'student';

        $user = User::create([
            'first_name' => strtoupper($request->first_name),
            'middle_name' => strtoupper($request->middle_name),
            'last_name' => strtoupper($request->last_name),
            'name' => strtoupper($request->first_name . ' ' . $request->last_name),
            'email' => $request->email,
            'school_id' => $request->school_id,
            'role' => $assignedRole,
            'year_level' => ($assignedRole === 'student') ? $request->year_level : null,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));
        Auth::login($user);

        // Simplified Redirection: Only Admin or Student
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('student.dashboard');
    }
}