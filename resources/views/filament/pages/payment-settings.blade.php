<x-filament-panels::page>

<style>
/* ── Gateway card shell ───────────────────────────── */
.ps-card {
    position: relative;
    background: #fff;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    overflow: hidden;
    transition: border-color .25s, box-shadow .25s;
    margin-bottom: 1.25rem;
}
.ps-card.active  { border-color: var(--card-color, #a78bfa); box-shadow: 0 4px 20px -4px var(--card-glow, rgba(139,92,246,.18)); }
.ps-card .stripe { position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #e2e8f0; transition: background .25s; }
.ps-card.active .stripe { background: linear-gradient(to bottom, var(--stripe-top, #a78bfa), var(--stripe-bot, #6366f1)); }

/* ── Card header row ──────────────────────────────── */
.ps-header  { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.75rem; }
.ps-icon-bg { display: flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; border-radius: .75rem; background: #f1f5f9; transition: background .25s; flex-shrink: 0; }
.ps-card.active .ps-icon-bg { background: var(--icon-bg, #ede9fe); }
.ps-icon    { width: 1.25rem; height: 1.25rem; color: #94a3b8; transition: color .25s; }
.ps-card.active .ps-icon { color: var(--icon-color, #7c3aed); }

/* ── Toggle switch ────────────────────────────────── */
.ps-toggle       { position: relative; display: inline-flex; cursor: pointer; flex-shrink: 0; }
.ps-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
.ps-track {
    width: 44px; height: 24px; border-radius: 9999px;
    background: #cbd5e1; transition: background .2s;
    position: relative;
}
.ps-toggle input:checked ~ .ps-track { background: var(--toggle-on, #7c3aed); }
.ps-track::after {
    content: ''; position: absolute; top: 2px; left: 2px;
    width: 20px; height: 20px; border-radius: 50%;
    background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2);
    transition: transform .2s;
}
.ps-toggle input:checked ~ .ps-track::after { transform: translateX(20px); }

/* ── Credentials panel ────────────────────────────── */
.ps-creds {
    border-top: 1px solid #f1f5f9;
    background: #fafafa;
    padding: 1.25rem 1.75rem;
}
.ps-label  { display: block; font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; margin-bottom: .4rem; }
.ps-input  {
    display: block; width: 100%; border-radius: .625rem;
    border: 1px solid #e2e8f0; background: #fff;
    padding: .625rem 2.5rem .625rem 1rem;
    font-size: .875rem; color: #1e293b;
    box-shadow: 0 1px 2px rgba(0,0,0,.04);
    transition: border-color .15s, box-shadow .15s;
    outline: none;
    box-sizing: border-box;
}
.ps-input:focus { border-color: var(--focus-ring, #7c3aed); box-shadow: 0 0 0 3px var(--focus-glow, rgba(124,58,237,.12)); }
.ps-input::placeholder { color: #cbd5e1; }
.ps-input-wrap { position: relative; }
.ps-input-wrap svg { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: #cbd5e1; pointer-events: none; }

/* ── Status badge ─────────────────────────────────── */
.ps-badge { display: inline-flex; align-items: center; gap: .3rem; border-radius: 9999px; padding: .15rem .6rem; font-size: .7rem; font-weight: 600; }
.ps-badge.on  { background: #dcfce7; color: #15803d; }
.ps-badge.off { background: #f1f5f9; color: #94a3b8; }
.ps-dot { width: .45rem; height: .45rem; border-radius: 50%; background: currentColor; animation: pulse-dot 2s infinite; }
@keyframes pulse-dot { 0%,100%{ opacity:1 } 50%{ opacity:.4 } }

/* ── Compact simple card (COD / Wallet) ───────────── */
.ps-simple { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; }
.ps-status-line { padding: .25rem 1.5rem .75rem; font-size: .72rem; font-weight: 600; display: flex; align-items: center; gap: .4rem; }

/* ── Sticky save bar ──────────────────────────────── */
.ps-savebar {
    position: sticky; bottom: 1rem; z-index: 20;
    display: flex; align-items: center; justify-content: space-between;
    border-radius: 1rem; border: 1px solid #e2e8f0;
    background: rgba(255,255,255,.95); backdrop-filter: blur(8px);
    padding: 1rem 1.5rem;
    box-shadow: 0 8px 24px -8px rgba(0,0,0,.15);
    margin-top: 1.5rem;
}
.ps-save-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    border-radius: .75rem; border: none; cursor: pointer;
    padding: .625rem 1.5rem; font-size: .875rem; font-weight: 600; color: #fff;
    background: linear-gradient(135deg, #ff647b 0%, #a855f7 100%);
    box-shadow: 0 4px 14px -4px rgba(168,85,247,.5);
    transition: opacity .2s, transform .1s;
}
.ps-save-btn:hover  { opacity: .92; transform: translateY(-1px); }
.ps-save-btn:active { transform: translateY(0); }
.ps-save-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }
@keyframes spin { from { transform: rotate(0deg) } to { transform: rotate(360deg) } }
@media (max-width: 640px) {
    div[style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
    .ps-creds div[style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>

{{-- ── Banner ── --}}
<div style="position:relative;overflow:hidden;border-radius:1rem;background:linear-gradient(135deg,#ff647b 0%,#a855f7 60%,#6366f1 100%);padding:1.5rem 2rem;margin-bottom:2rem;box-shadow:0 8px 24px -8px rgba(168,85,247,.4)">
    <div style="position:relative;z-index:1">
        <p style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.75);margin:0 0 .25rem">Admin › Settings</p>
        <h2 style="font-size:1.4rem;font-weight:800;color:#fff;margin:0 0 .35rem">Payment Gateway Configuration</h2>
        <p style="font-size:.8rem;color:rgba(255,255,255,.8);max-width:36rem;margin:0">Enable or disable payment methods and configure API credentials. Credentials are saved to the database and synced to your <code style="background:rgba(255,255,255,.2);border-radius:.25rem;padding:0 .3rem;font-size:.75rem">.env</code> automatically.</p>
    </div>
    <div style="position:absolute;right:1.5rem;top:50%;transform:translateY(-50%);width:3.5rem;height:3.5rem;border-radius:.75rem;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center">
        <x-filament::icon icon="heroicon-o-credit-card" style="width:1.75rem;height:1.75rem;color:#fff" />
    </div>
    <div style="position:absolute;right:-2rem;top:-2rem;width:8rem;height:8rem;border-radius:50%;background:rgba(255,255,255,.1)"></div>
    <div style="position:absolute;left:40%;bottom:-2rem;width:6rem;height:6rem;border-radius:50%;background:rgba(255,255,255,.08)"></div>
</div>

<form wire:submit.prevent="save">

    {{-- ══ RAZORPAY ══ --}}
    @php $rzOn = $payment_razorpay_enabled; @endphp
    <div class="ps-card {{ $rzOn ? 'active' : '' }}"
         style="--card-color:#fb923c;--card-glow:rgba(251,146,60,.2);--stripe-top:#fb923c;--stripe-bot:#f97316;--icon-bg:#fff7ed;--icon-color:#ea580c">
        <div class="stripe"></div>
        <div class="ps-header" style="padding-left:1.75rem">
            <div style="display:flex;align-items:center;gap:.875rem">
                <div class="ps-icon-bg">
                    <x-filament::icon icon="heroicon-o-credit-card" class="ps-icon" />
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.2rem">
                        <span style="font-size:.875rem;font-weight:700;color:#1e293b">Razorpay</span>
                        @if($rzOn)
                            <span class="ps-badge on"><span class="ps-dot"></span> Active</span>
                        @else
                            <span class="ps-badge off">Inactive</span>
                        @endif
                    </div>
                    <p style="font-size:.72rem;color:#94a3b8;margin:0">India-first gateway · UPI, Cards, Netbanking</p>
                </div>
            </div>
            <label class="ps-toggle" style="--toggle-on:#ea580c">
                <input type="checkbox" wire:model.live="payment_razorpay_enabled">
                <div class="ps-track"></div>
            </label>
        </div>
        @if($rzOn)
        <div class="ps-creds">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label class="ps-label">Key ID</label>
                    <div class="ps-input-wrap">
                        <input type="text" wire:model="razorpay_key" placeholder="rzp_live_xxxx" class="ps-input" style="--focus-ring:#ea580c;--focus-glow:rgba(234,88,12,.12)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                    </div>
                </div>
                <div>
                    <label class="ps-label">Key Secret</label>
                    <p style="padding:.6rem .75rem;background:#fefce8;border:1px solid #fde68a;border-radius:.5rem;color:#92400e;font-size:.8rem;">
                        <strong>RAZORPAY_SECRET</strong> — Configure in <code>.env</code> only (not stored in database).
                    </p>
                </div>
            </div>
            <p style="margin-top:.75rem;font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:.35rem">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:.9rem;height:.9rem;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                Get your keys from the <a href="https://dashboard.razorpay.com/app/keys" target="_blank" style="color:#ea580c;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Razorpay Dashboard &rarr;</a>
            </p>
        </div>
        @endif
    </div>

    {{-- ══ STRIPE ══ --}}
    @php $strOn = $payment_stripe_enabled; @endphp
    <div class="ps-card {{ $strOn ? 'active' : '' }}"
         style="--card-color:#8b5cf6;--card-glow:rgba(139,92,246,.2);--stripe-top:#8b5cf6;--stripe-bot:#6366f1;--icon-bg:#f5f3ff;--icon-color:#7c3aed">
        <div class="stripe"></div>
        <div class="ps-header" style="padding-left:1.75rem">
            <div style="display:flex;align-items:center;gap:.875rem">
                <div class="ps-icon-bg">
                    <x-filament::icon icon="heroicon-o-credit-card" class="ps-icon" />
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.2rem">
                        <span style="font-size:.875rem;font-weight:700;color:#1e293b">Stripe</span>
                        @if($strOn)
                            <span class="ps-badge on"><span class="ps-dot"></span> Active</span>
                        @else
                            <span class="ps-badge off">Inactive</span>
                        @endif
                    </div>
                    <p style="font-size:.72rem;color:#94a3b8;margin:0">Global card payments · 135+ currencies</p>
                </div>
            </div>
            <label class="ps-toggle" style="--toggle-on:#7c3aed">
                <input type="checkbox" wire:model.live="payment_stripe_enabled">
                <div class="ps-track"></div>
            </label>
        </div>
        @if($strOn)
        <div class="ps-creds">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label class="ps-label">Publishable Key</label>
                    <div class="ps-input-wrap">
                        <input type="text" wire:model="stripe_key" placeholder="pk_live_xxxx" class="ps-input" style="--focus-ring:#7c3aed;--focus-glow:rgba(124,58,237,.12)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                    </div>
                </div>
                <div>
                    <label class="ps-label">Secret Key</label>
                    <p style="padding:.6rem .75rem;background:#fefce8;border:1px solid #fde68a;border-radius:.5rem;color:#92400e;font-size:.8rem;">
                        <strong>STRIPE_SECRET</strong> — Configure in <code>.env</code> only (not stored in database).
                    </p>
                </div>
                <div style="grid-column:1/-1">
                    <label class="ps-label">Webhook Secret</label>
                    <p style="padding:.6rem .75rem;background:#fefce8;border:1px solid #fde68a;border-radius:.5rem;color:#92400e;font-size:.8rem;">
                        <strong>STRIPE_WEBHOOK_SECRET</strong> — Configure in <code>.env</code> only (not stored in database).
                    </p>
                </div>
            </div>
            <p style="margin-top:.75rem;font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:.35rem">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:.9rem;height:.9rem;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                Get your keys from the <a href="https://dashboard.stripe.com/apikeys" target="_blank" style="color:#7c3aed;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Stripe Dashboard &rarr;</a>
            </p>
        </div>
        @endif
    </div>

    {{-- ══ PAYPAL ══ --}}
    @php $ppOn = $payment_paypal_enabled; @endphp
    <div class="ps-card {{ $ppOn ? 'active' : '' }}"
         style="--card-color:#0ea5e9;--card-glow:rgba(14,165,233,.2);--stripe-top:#38bdf8;--stripe-bot:#0284c7;--icon-bg:#f0f9ff;--icon-color:#0284c7">
        <div class="stripe"></div>
        <div class="ps-header" style="padding-left:1.75rem">
            <div style="display:flex;align-items:center;gap:.875rem">
                <div class="ps-icon-bg">
                    <x-filament::icon icon="heroicon-o-globe-alt" class="ps-icon" />
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.2rem">
                        <span style="font-size:.875rem;font-weight:700;color:#1e293b">PayPal</span>
                        @if($ppOn)
                            <span class="ps-badge on"><span class="ps-dot"></span> Active</span>
                        @else
                            <span class="ps-badge off">Inactive</span>
                        @endif
                    </div>
                    <p style="font-size:.72rem;color:#94a3b8;margin:0">Global PayPal payments · 200+ countries</p>
                </div>
            </div>
            <label class="ps-toggle" style="--toggle-on:#0284c7">
                <input type="checkbox" wire:model.live="payment_paypal_enabled">
                <div class="ps-track"></div>
            </label>
        </div>
        @if($ppOn)
        <div class="ps-creds">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label class="ps-label">Mode</label>
                    <select wire:model="paypal_mode" class="ps-input" style="--focus-ring:#0284c7;--focus-glow:rgba(2,132,199,.12);padding-right:1rem">
                        <option value="sandbox">Sandbox (Testing)</option>
                        <option value="live">Live</option>
                    </select>
                </div>
                <div>
                    <label class="ps-label">Client ID</label>
                    <input type="text" wire:model="paypal_client_id" placeholder="Axxxxxxx" class="ps-input" style="--focus-ring:#0284c7;--focus-glow:rgba(2,132,199,.12)">
                </div>
                <div style="grid-column:1/-1">
                    <label class="ps-label">Client Secret</label>
                    <p style="padding:.6rem .75rem;background:#fefce8;border:1px solid #fde68a;border-radius:.5rem;color:#92400e;font-size:.8rem;">
                        <strong>PAYPAL_CLIENT_SECRET</strong> — Configure in <code>.env</code> only (not stored in database).
                    </p>
                </div>
            </div>
            <p style="margin-top:.75rem;font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:.35rem">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:.9rem;height:.9rem;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                Get credentials from <a href="https://developer.paypal.com/developer/applications" target="_blank" style="color:#0284c7;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">PayPal Developer &rarr;</a>
            </p>
        </div>
        @endif
    </div>

    {{-- ══ COD + WALLET ══ --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:0">

        @php $codOn = $payment_cod_enabled; @endphp
        <div class="ps-card {{ $codOn ? 'active' : '' }}"
             style="--card-color:#f59e0b;--card-glow:rgba(245,158,11,.18);--stripe-top:#fbbf24;--stripe-bot:#d97706;--icon-bg:#fffbeb;--icon-color:#d97706;margin-bottom:0">
            <div class="stripe"></div>
            <div class="ps-simple" style="padding-left:1.5rem">
                <div style="display:flex;align-items:center;gap:.75rem">
                    <div class="ps-icon-bg">
                        <x-filament::icon icon="heroicon-o-banknotes" class="ps-icon" />
                    </div>
                    <div>
                        <p style="font-size:.875rem;font-weight:700;color:#1e293b;margin:0 0 .15rem">Cash on Delivery</p>
                        <p style="font-size:.7rem;color:#94a3b8;margin:0">Pay at your doorstep</p>
                    </div>
                </div>
                <label class="ps-toggle" style="--toggle-on:#d97706">
                    <input type="checkbox" wire:model.live="payment_cod_enabled">
                    <div class="ps-track"></div>
                </label>
            </div>
            <div class="ps-status-line" style="color:{{ $codOn ? '#15803d' : '#94a3b8' }}">
                @if($codOn)
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1rem;height:1rem"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                    Enabled at checkout
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1rem;height:1rem"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" /></svg>
                    Disabled at checkout
                @endif
            </div>
        </div>

        @php $walOn = $payment_wallet_enabled; @endphp
        <div class="ps-card {{ $walOn ? 'active' : '' }}"
             style="--card-color:#10b981;--card-glow:rgba(16,185,129,.18);--stripe-top:#34d399;--stripe-bot:#059669;--icon-bg:#ecfdf5;--icon-color:#059669;margin-bottom:0">
            <div class="stripe"></div>
            <div class="ps-simple" style="padding-left:1.5rem">
                <div style="display:flex;align-items:center;gap:.75rem">
                    <div class="ps-icon-bg">
                        <x-filament::icon icon="heroicon-o-wallet" class="ps-icon" />
                    </div>
                    <div>
                        <p style="font-size:.875rem;font-weight:700;color:#1e293b;margin:0 0 .15rem">Wallet Balance</p>
                        <p style="font-size:.7rem;color:#94a3b8;margin:0">Instant balance deduction</p>
                    </div>
                </div>
                <label class="ps-toggle" style="--toggle-on:#059669">
                    <input type="checkbox" wire:model.live="payment_wallet_enabled">
                    <div class="ps-track"></div>
                </label>
            </div>
            <div class="ps-status-line" style="color:{{ $walOn ? '#15803d' : '#94a3b8' }}">
                @if($walOn)
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1rem;height:1rem"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                    Enabled at checkout
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1rem;height:1rem"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" /></svg>
                    Disabled at checkout
                @endif
            </div>
        </div>

    </div>

    {{-- ══ EXTERNAL WALLET PURCHASE ══ --}}
    @php $extOn = $wallet_external_enabled; @endphp
    <div class="ps-card {{ $extOn ? 'active' : '' }}" style="margin-top:1.25rem;"
         style="--card-color:#ec4899;--card-glow:rgba(236,72,153,.2);--stripe-top:#f472b6;--stripe-bot:#db2777;--icon-bg:#fdf2f8;--icon-color:#db2777">
        <div class="stripe"></div>
        <div class="ps-header" style="padding-left:1.75rem">
            <div style="display:flex;align-items:center;gap:.875rem">
                <div class="ps-icon-bg">
                    <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="ps-icon" />
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.2rem">
                        <span style="font-size:.875rem;font-weight:700;color:#1e293b">External Wallet Purchase</span>
                        @if($extOn)
                            <span class="ps-badge on"><span class="ps-dot"></span> Active</span>
                        @else
                            <span class="ps-badge off">Inactive</span>
                        @endif
                    </div>
                    <p style="font-size:.72rem;color:#94a3b8;margin:0">Redirect users to external site for wallet top-up · Code redemption flow</p>
                </div>
            </div>
            <label class="ps-toggle" style="--toggle-on:#db2777">
                <input type="checkbox" wire:model.live="wallet_external_enabled">
                <div class="ps-track"></div>
            </label>
        </div>
        @if($extOn)
        <div class="ps-creds">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div style="grid-column:1/-1">
                    <label class="ps-label">External Purchase URL</label>
                    <div class="ps-input-wrap">
                        <input type="url" wire:model="wallet_purchase_url" placeholder="https://pay.your-other-site.com/topup" class="ps-input" style="--focus-ring:#db2777;--focus-glow:rgba(219,39,119,.12)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                    </div>
                </div>
                <div>
                    <label class="ps-label">Shared Secret (HMAC Key)</label>
                    <p style="padding:.6rem .75rem;background:#fefce8;border:1px solid #fde68a;border-radius:.5rem;color:#92400e;font-size:.8rem;">
                        <strong>WALLET_SHARED_SECRET</strong> — Configure in <code>.env</code> only (not stored in database).
                        Required for wallet top-up code generation and verification.
                    </p>
                </div>
                <div>
                    <label class="ps-label">Tutorial Video URL</label>
                    <div class="ps-input-wrap">
                        <input type="url" wire:model="wallet_tutorial_video" placeholder="https://www.youtube.com/watch?v=xxxx" class="ps-input" style="--focus-ring:#db2777;--focus-glow:rgba(219,39,119,.12)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z" /></svg>
                    </div>
                </div>
            </div>

            <div style="margin-top:1rem; padding:.75rem 1rem; border-radius:.625rem; background:#fdf2f8; border:1px solid #fce7f3;">
                <p style="font-size:.72rem;font-weight:700;color:#9d174d;margin:0 0 .4rem">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:.85rem;height:.85rem;vertical-align:middle;margin-right:.25rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                    How It Works
                </p>
                <ol style="margin:0;padding-left:1.1rem;font-size:.7rem;color:#831843;line-height:1.7">
                    <li>User clicks <strong>"Add Money"</strong> in wallet → lands on the <code style="background:rgba(219,39,119,.1);padding:0 .25rem;border-radius:.2rem">/wallet/add-balance</code> page with guidelines + video.</li>
                    <li>User visits the <strong>External Purchase URL</strong> (above), completes payment, and receives a <strong>unique redemption code</strong>.</li>
                    <li>User pastes the code back on the add-balance page. System verifies the code using <strong>HMAC-SHA256</strong> + the Shared Secret above, then credits the wallet instantly.</li>
                </ol>
                <p style="margin:.5rem 0 0;font-size:.68rem;color:#9d174d;">
                    <strong>External site code format:</strong>
                    <code style="background:rgba(219,39,119,.1);padding:.1rem .3rem;border-radius:.2rem;font-size:.65rem;word-break:break-all">
                        base64_encode(json_encode(['user_id', 'amount', 'transaction_id', 'timestamp', 'signature' => HMAC(user_id:amount:transaction_id:timestamp, secret)]))
                    </code>
                </p>
            </div>

            <p style="margin-top:.75rem;font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:.35rem">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:.9rem;height:.9rem;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                The same Shared Secret must be configured on your external payment site for code generation.
            </p>
        </div>
        @endif
    </div>

    {{-- ══ STICKY SAVE BAR ══ --}}
    <div class="ps-savebar">
        <div style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;color:#94a3b8">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1rem;height:1rem;color:#10b981;flex-shrink:0"><path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53-1.324-1.324a.75.75 0 00-1.06 1.06l2 2a.75.75 0 001.14-.094l3.7-5.3z" clip-rule="evenodd" /></svg>
            Saved to database &amp; synced to <code style="background:#f1f5f9;padding:0 .3rem;border-radius:.25rem;font-size:.7rem">.env</code>
        </div>
        <button type="submit" class="ps-save-btn" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1rem;height:1rem"><path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" clip-rule="evenodd" /></svg>
            </span>
            <span wire:loading wire:target="save">
                <svg style="width:1rem;height:1rem;animation:spin 1s linear infinite" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </span>
            <span wire:loading.remove wire:target="save">Save Settings</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </div>

</form>

</x-filament::page>
