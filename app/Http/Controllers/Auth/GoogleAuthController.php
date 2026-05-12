<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly MailService $mailService,
    ) {}

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Google login failed. Please try again.']);
        }

        $isNewUser = false;

        // Try to find existing user by google_id or email
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Existing user — update google_id if not set, then login
            if (!$user->google_id) {
                $user->update([
                    'google_id'        => $googleUser->getId(),
                    'avatar'           => $user->avatar ?: $googleUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?: now(),
                ]);
            }
        } else {
            // New user — register with auto-verified email
            $isNewUser = true;
            $name = trim($googleUser->getName() ?: explode('@', $googleUser->getEmail())[0]);
            $user = User::create([
                'name'              => $name,
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'password'          => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);
        }

        Auth::login($user, true);

        // Send welcome email to new users (Google auto-verifies, so skip verification step)
        if ($isNewUser) {
            $this->mailService->sendTemplateEmail(
                'welcome',
                $user->email,
                $user->name,
                [
                    '{{customer_name}}'  => $user->name,
                    '{{dashboard_link}}' => url('/dashboard'),
                    '{{unsubscribe_link}}' => url('/unsubscribe'),
                ],
            );
        }

        return redirect()->intended(route('dashboard'));
    }
}
