@extends('layouts.app')

@section('title', 'Verify Your Email')

@section('content')
<div style="min-height:60vh; display:flex; align-items:center; justify-content:center; padding:40px 16px; background:#f8fafc;">
    <div style="max-width:480px; width:100%; background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,0.08); border:1px solid #e5e7eb; overflow:hidden; text-align:center;">

        {{-- Header --}}
        <div style="background:linear-gradient(135deg,#e61f7f 0%,#c01468 100%); padding:32px 24px;">
            <div style="width:56px; height:56px; background:rgba(255,255,255,0.18); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                <svg width="28" height="28" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <h1 style="margin:0; color:#fff; font-size:22px; font-weight:700;">Verify Your Email</h1>
            <p style="margin:8px 0 0; color:rgba(255,255,255,0.8); font-size:14px;">One last step to get started!</p>
        </div>

        {{-- Body --}}
        <div style="padding:32px 24px;">
            @if(session('success'))
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; padding:12px 16px; border-radius:10px; font-size:14px; margin-bottom:20px;">
                    {{ session('success') }}
                </div>
            @endif

            <div style="width:64px; height:64px; background:#fce7f3; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                <svg width="32" height="32" fill="none" stroke="#e61f7f" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>

            <h2 style="font-size:18px; font-weight:700; color:#1f2937; margin:0 0 8px;">Check Your Inbox</h2>
            <p style="font-size:14px; color:#6b7280; line-height:1.7; margin:0 0 24px;">
                We've sent a verification link to your email address. Click the link to activate your account and start shopping!
            </p>

            <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px; margin-bottom:24px; text-align:left;">
                <p style="margin:0 0 6px; font-size:13px; font-weight:600; color:#92400e;">Didn't receive the email?</p>
                <p style="margin:0 0 4px; font-size:12px; color:#b45309; line-height:1.6;">• Check your spam/promotions folder</p>
                <p style="margin:0; font-size:12px; color:#b45309; line-height:1.6;">• Make sure you entered your email correctly</p>
            </div>

            <a href="{{ route('login') }}"
               style="display:inline-block; background:#e61f7f; color:#fff; text-decoration:none; font-size:15px; font-weight:600; padding:12px 32px; border-radius:10px;">
                Back to Login
            </a>

            <p style="margin:20px 0 0; font-size:13px; color:#9ca3af;">
                Still having trouble?
                <a href="mailto:support@medinovapharma.com" style="color:#e61f7f; text-decoration:none; font-weight:500;">Contact Support</a>
            </p>
        </div>
    </div>
</div>
@endsection
