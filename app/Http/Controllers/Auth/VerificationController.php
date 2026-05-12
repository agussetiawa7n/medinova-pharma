<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class VerificationController extends Controller
{
    public function __construct(
        private readonly MailService $mailService,
    ) {}

    public function notice()
    {
        return view('auth.verify-notice');
    }

    public function verify(Request $request, User $user)
    {
        // Verify signed URL is valid and not expired
        if (!URL::hasValidSignature($request)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'The verification link is invalid or has expired. Please register again.']);
        }

        if ($user->email_verified_at) {
            Auth::login($user);
            return redirect()->intended(route('dashboard'));
        }

        $user->update(['email_verified_at' => now()]);

        // Send welcome email
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

        Auth::login($user);

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Email verified successfully! Welcome to MediNova Pharma.');
    }

    public function resend(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->whereNull('email_verified_at')->first();

        if (!$user) {
            return back()->withErrors(['email' => 'No unverified account found with this email.']);
        }

        $this->mailService->sendVerificationEmail($user);

        return back()->with('success', 'Verification email has been resent. Please check your inbox.');
    }
}
