<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use App\Mail\TwoFactorCodeMail; 
class ActivationController extends Controller
{
    // Step 1: View email configuration form
    public function index()
    {
        return view('auth.activation-email', ['user' => auth()->user()]);
    }

    // Step 2: Process email and broadcast OTP token
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . auth()->id(),
        ]);

        $user = auth()->user();
        $code = rand(100000, 999999);

        // Store code lifecycle and targeted temporary email destination
        $user->forceFill([
            'otp_code' => $code,
            'otp_expires_at' => now()->addMinutes(15),
            'temp_email' => $request->email,
        ])->save();

        // Dispatch verification mail component
        Mail::to($request->email)->send(new TwoFactorCodeMail($code));

        return redirect()->route('activation.otp_view')->with('status', 'A verification code has been dispatched to your designated email address.');
    }

    public function otpView()
    {
       return view('auth.activation-otp', ['user' => auth()->user()]);
    }

    // Step 3: Verify OTP Code
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric'
        ]);

        $user = auth()->user();

        if ($request->code == $user->otp_code && now()->lt($user->otp_expires_at)) {
            // Commit and sync the temporary email layout to the user's main email column
            $user->email = $user->temp_email;
            $user->otp_code = null;
            $user->save();

            return redirect()->route('activation.password_view');
        }

        return back()->withErrors(['code' => 'The provided security code is invalid or has expired. Please try again.']);
    }

    public function passwordView()
    {
        return view('auth.activation-password', ['user' => auth()->user()]);
    }

    // Step 4: Handle password update and activate the account
    public function updatePassword(Request $request)
    {
        // Enforces: 8+ chars, mixed case (upper/lower), and numbers
        $request->validate([
            'password' => [
                'required', 
                'string', 
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
            ],
        ]);

        $user = auth()->user();

        $user->forceFill([
            'password' => Hash::make($request->password),
            'is_activated' => true,
            'temp_email' => null
        ])->save();

        return redirect()->route('dashboard')->with('status', 'Account activated successfully! Welcome to LMMS.');
    }
}
