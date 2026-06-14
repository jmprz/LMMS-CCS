<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;

class ForgotPasswordController extends Controller
{
    /**
     * Step 1: Display the email request view.
     */
    public function emailView()
    {
        return view('auth.forgot-password-email');
    }

    /**
     * Step 1 Post: Validate email, generate 6-digit OTP, and dispatch email.
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'We could not find an account associated with that email address.',
        ]);

        // Generate a cryptographically secure 6-digit token
        $code = random_int(100000, 999999);

        // Stash data securely in the session with a 15-minute lifetime expiration window
        Session::put('password_reset', [
            'email' => $request->email,
            'code' => $code,
            'expires_at' => now()->addMinutes(15),
            'verified' => false
        ]);

        // Dispatch the email matching your custom template layout
        Mail::send('emails.password_reset_code', ['code' => $code], function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('LMMS - Password Recovery Security Token');
        });

        return redirect()->route('password.otp_view')
            ->with('status', 'A new security token validation code has been sent to your inbox.');
    }

    /**
     * Step 2: Display the 6-digit OTP input view.
     */
    public function otpView()
    {
        if (!Session::has('password_reset')) {
            return redirect()->route('password.request');
        }

        return view('auth.forgot-password-otp');
    }

    /**
     * Step 2 Post: Evaluate user entry code matches the session register token.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $sessionData = Session::get('password_reset');

        if (!$sessionData) {
            return redirect()->route('password.request');
        }

        // Verify the code hasn't expired past its 15-minute threshold parameters
        if (now()->isAfter($sessionData['expires_at'])) {
            Session::forget('password_reset');
            return redirect()->route('password.request')->withErrors(['email' => 'The validation code has expired. Please try again.']);
        }

        // String comparison evaluation check
        if ($request->code !== (string)$sessionData['code']) {
            return back()->withErrors(['code' => 'The authorization entry code provided is invalid.']);
        }

        // Upgrade session registration phase level to verified
        $sessionData['verified'] = true;
        Session::put('password_reset', $sessionData);

        return redirect()->route('password.reset_view');
    }

    /**
     * Step 3: Display the password adjustment view wrapper.
     */
    public function passwordView()
    {
        $sessionData = Session::get('password_reset');

        if (!$sessionData || !$sessionData['verified']) {
            return redirect()->route('password.request');
        }

        return view('auth.forgot-password-reset');
    }

    /**
     * Step 3 Post: Apply updates, establish authenticated session, and entry route.
     */
    public function updatePassword(Request $request)
    {
        $sessionData = Session::get('password_reset');

        if (!$sessionData || !$sessionData['verified']) {
            return redirect()->route('password.request');
        }

        // Validates constraints: min 8 length, requires an uppercase item and a digit element
        $request->validate([
            'password' => [
                'required', 
                'confirmed', 
                Password::min(8)->letters()->mixedCase()->numbers()
            ],
        ], [
            'password.min' => 'Your password signature must be at least 8 characters long.',
            'password' => 'Password must include both uppercase and lowercase lettering alongside numbers.',
        ]);

        // Extract and perform the record changes on the database matching model indices
        $user = User::where('email', $sessionData['email'])->firstOrFail();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Clean up password recovery tracking parameters completely
        Session::forget('password_reset');

        // 1. Authenticate the active user instance into the system
        Auth::login($user);

        // 2. Regenerate secure session payload keys to deter fixation exploits
        $request->session()->regenerate();

        // 3. Dispatch redirect straight into the internal dashboard structure
        return redirect()->route('dashboard')->with('status', 'Your account password has been successfully updated.');
    }
}