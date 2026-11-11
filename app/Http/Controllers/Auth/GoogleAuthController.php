<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google Sign-In is not configured yet. Please add your GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env file.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google and authenticate.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in was cancelled or encountered an error. Please try again or use your password.',
            ]);
        }

        $email = $googleUser->getEmail();
        if (empty($email)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Unable to retrieve your email address from Google. Please register manually.',
            ]);
        }

        // 1. Look for user by google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        if (!$user) {
            // 2. Look for user by email to link Google account
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar() ?: $user->avatar,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            } else {
                // 3. Register as a new patient user
                $user = User::create([
                    'name' => $googleUser->getName() ?: 'Patient User',
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'role' => 'patient',
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(), // Google emails are pre-verified
                ]);
            }
        } else {
            // Update avatar if provided
            if ($googleUser->getAvatar() && $user->avatar !== $googleUser->getAvatar()) {
                $user->update(['avatar' => $googleUser->getAvatar()]);
            }
        }

        // Access control check: ensure account is active
        if (isset($user->is_active) && !$user->is_active) {
            AuditLogger::log(
                module: 'auth',
                action: 'login_blocked',
                description: "Google login blocked for deactivated account {$user->name} ({$user->email}).",
                model: $user
            );

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated by the system administrator. Please contact clinic management.',
            ]);
        }

        // Ensure patient profile exists for verified patients
        if ($user->role === 'patient') {
            $user->ensurePatientProfile();
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        AuditLogger::log(
            module: 'auth',
            action: 'google_login',
            description: "User {$user->name} logged in via Google OAuth.",
            model: $user
        );

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
