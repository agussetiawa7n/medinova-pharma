<?php

namespace App\Filament\Pages;

use App\Models\EmailTemplate;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class EmailTemplates extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-envelope-open';
    protected static string|\UnitEnum|null $navigationGroup   = 'Settings';
    protected static ?int $navigationSort                     = 2;  // Right below SMTP
    protected static ?string $title                           = 'Email Templates';
    protected static ?string $navigationLabel                 = 'Email Templates';

    protected string $view = 'filament.pages.email-templates';

    // Current editing template key
    public string $activeKey = 'order_complete';

    // Form fields per template
    public string $subject   = '';
    public string $body      = '';
    public bool   $is_active = true;
    public string $preview   = '';

    public array $availableVariables = [];

    public function mount(): void
    {
        $this->loadTemplate($this->activeKey);
    }

    public function loadTemplate(string $key): void
    {
        $this->activeKey = $key;
        $template = EmailTemplate::where('key', $key)->first();

        if ($template) {
            $this->subject           = $template->subject;
            $this->body              = $template->body;
            $this->is_active         = $template->is_active;
            $this->availableVariables = $template->variables ?? [];
        } else {
            $defaults = $this->getDefaults();
            $this->subject           = $defaults[$key]['subject'] ?? '';
            $this->body              = $defaults[$key]['body'] ?? '';
            $this->is_active         = true;
            $this->availableVariables = $defaults[$key]['variables'] ?? [];
        }

        $this->preview = '';
    }

    public function save(): void
    {
        $defaults = $this->getDefaults();
        EmailTemplate::updateOrCreate(
            ['key' => $this->activeKey],
            [
                'subject'   => $this->subject,
                'body'      => $this->body,
                'is_active' => $this->is_active,
                'variables' => $defaults[$this->activeKey]['variables'] ?? [],
            ]
        );

        Notification::make()
            ->title('Template saved!')
            ->body('The "' . $this->getTemplateLabel($this->activeKey) . '" template has been updated.')
            ->success()
            ->send();
    }

    public function previewTemplate(): void
    {
        $allVars = [
            '{{customer_name}}'     => 'Rahul Sharma',
            '{{order_number}}'      => 'ORD-2026-00123',
            '{{order_id}}'          => 'ORD-2026-00123',
            '{{product_name}}'      => 'Paracetamol 500mg (strip of 10)',
            '{{amount}}'            => '$89.00',
            '{{quantity}}'          => '2',
            '{{total_amount}}'      => '$1,299.00',
            '{{order_date}}'        => now()->format('d M Y'),
            '{{estimated_delivery}}'=> now()->addDays(3)->format('d M Y'),
            '{{order_link}}'        => '#',
            '{{tracking_number}}'   => 'DELHIVERY123456789',
            '{{tracking_url}}'      => '#',
            '{{courier_name}}'      => 'Delhivery',
            '{{verification_url}}'  => '#',
            '{{reset_url}}'         => '#',
            '{{unsubscribe_link}}'  => '#',
        ];

        $body    = str_replace(array_keys($allVars), array_values($allVars), $this->body);
        $subject = str_replace(array_keys($allVars), array_values($allVars), $this->subject);

        // Wrap in a sandboxed iframe so full email HTML renders cleanly
        $html = str_replace('</body>', "<div style='background:#f4f4f7;padding:8px;text-align:center;font-family:sans-serif;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;'>⬆ This is a preview of how the email will look in an inbox.</div></body>", $body);
        $this->preview = $html;
    }

    public function resetToDefault(): void
    {
        $defaults = $this->getDefaults();
        if (isset($defaults[$this->activeKey])) {
            $this->subject           = $defaults[$this->activeKey]['subject'];
            $this->body              = $defaults[$this->activeKey]['body'];
            $this->is_active         = true;
            $this->availableVariables = $defaults[$this->activeKey]['variables'] ?? [];
        }

        Notification::make()->title('Reset to default')->body('Click Save to apply.')->warning()->send();
    }

    public function getTemplates(): array
    {
        return [
            'order_complete'     => '✅ Order Completed',
            'order_tracking'     => '🚚 Tracking Update',
            'email_verification' => '🔐 Email Verification',
            'welcome'            => '👋 Welcome',
            'forgot_password'    => '🔑 Forgot Password',
        ];
    }

    public function getTemplateLabel(string $key): string
    {
        return $this->getTemplates()[$key] ?? $key;
    }

    public function getDefaults(): array
    {
        return [
            'order_complete' => [
                'subject'   => '✅ Your Order #{{order_number}} is Confirmed!',
                'variables' => ['{{customer_name}}', '{{product_name}}', '{{amount}}', '{{quantity}}', '{{order_id}}', '{{subtotal}}', '{{shipping_fee}}', '{{tax}}', '{{wallet_used}}', '{{total_amount}}', '{{estimated_delivery}}', '{{order_link}}', '{{unsubscribe_link}}'],
                'body'      => '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<style type="text/css">body{margin:0;padding:0;background:#f4f4f7;font-family:"Segoe UI",Arial,sans-serif;-webkit-font-smoothing:antialiased;}
</style></head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:\'Segoe UI\',Arial,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;font-size:1px;color:#f4f4f7;line-height:1px;">Your order #{{order_id}} is confirmed! Thank you {{customer_name}}.</div>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f4f7;min-height:100vh;"><tr><td align="center" style="padding:40px 16px;">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

<tr><td style="background:linear-gradient(135deg,#e61f7f 0%,#c01468 100%);padding:36px 48px 32px;text-align:center;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">
<div style="display:inline-block;background:rgba(255,255,255,0.15);border-radius:12px;padding:10px 22px;margin-bottom:20px;">
<span style="color:#fff;font-size:20px;font-weight:700;">✦ MediNova Pharma</span></div></td></tr>
<tr><td align="center">
<h1 style="margin:0;color:#fff;font-size:26px;font-weight:700;line-height:1.3;">Order Confirmed!</h1>
<p style="margin:10px 0 0;color:rgba(255,255,255,0.85);font-size:15px;line-height:1.5;">Thank you for your purchase, {{customer_name}}</p>
</td></tr></table></td></tr>

<tr><td style="padding:40px 48px 32px;">
<p style="margin:0 0 20px;font-size:16px;color:#1f2937;line-height:1.7;">Hi <strong style="color:#e61f7f;">{{customer_name}}</strong>,</p>
<p style="margin:0 0 32px;font-size:16px;color:#4b5563;line-height:1.8;">Your order has been successfully placed. Here\'s a quick summary:</p>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:32px;overflow:hidden;">
<tr><td style="padding:14px 20px;border-bottom:1px solid #e5e7eb;background:#f3f4f6;">
<p style="margin:0;font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:1px;">Order Summary</p></td></tr>
<tr><td style="padding:16px 20px;border-bottom:1px solid #f0f0f0;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td style="font-size:14px;color:#374151;font-weight:500;">{{product_name}}</td>
<td align="right" style="font-size:14px;color:#374151;font-weight:600;">${{amount}}</td></tr>
<tr><td style="font-size:12px;color:#9ca3af;padding-top:2px;">Qty: {{quantity}}</td><td></td></tr>
</table></td></tr>
<tr><td style="padding:14px 20px;background:#fff;">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td style="font-size:14px;color:#6b7280;padding-bottom:8px;">Subtotal</td>
<td align="right" style="font-size:14px;color:#374151;padding-bottom:8px;">{{subtotal}}</td></tr>
<tr><td style="font-size:14px;color:#6b7280;padding-bottom:8px;">Shipping Fee</td>
<td align="right" style="font-size:14px;color:#374151;padding-bottom:8px;">{{shipping_fee}}</td></tr>
<tr><td style="font-size:14px;color:#6b7280;padding-bottom:8px;">Tax</td>
<td align="right" style="font-size:14px;color:#374151;padding-bottom:8px;">{{tax}}</td></tr>
<tr><td style="font-size:14px;color:#6b7280;padding-bottom:12px;">Wallet Used</td>
<td align="right" style="font-size:14px;color:#dc2626;padding-bottom:12px;">−{{wallet_used}}</td></tr>
<tr><td style="border-top:1px solid #e5e7eb;padding-top:12px;font-size:15px;color:#1f2937;font-weight:700;">Total Paid</td>
<td align="right" style="border-top:1px solid #e5e7eb;padding-top:12px;font-size:18px;color:#e61f7f;font-weight:700;">{{total_amount}}</td></tr>
</table></td></tr></table>

<table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;"><tr>
<td style="background:#fef3c7;border-radius:100px;padding:6px 16px;border:1px solid #fcd34d;">
<span style="font-size:13px;color:#92400e;font-weight:600;">Processing your order</span></td></tr></table>

<hr style="border:none;border-top:1px solid #f3f4f6;margin:0 0 32px;">
<p style="margin:0 0 16px;font-size:15px;font-weight:700;color:#1f2937;">What happens next?</p>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:36px;">
<tr><td width="40" valign="top" style="padding-bottom:20px;">
<div style="width:32px;height:32px;background:#fce7f3;border-radius:50%;text-align:center;line-height:32px;font-size:14px;font-weight:700;color:#e61f7f;">1</div></td>
<td valign="top" style="padding-bottom:20px;padding-left:12px;">
<p style="margin:0;font-size:14px;font-weight:600;color:#1f2937;">Order Processing</p>
<p style="margin:4px 0 0;font-size:13px;color:#6b7280;">Our team is reviewing and preparing your order.</p></td></tr>
<tr><td width="40" valign="top" style="padding-bottom:20px;">
<div style="width:32px;height:32px;background:#ede9fe;border-radius:50%;text-align:center;line-height:32px;font-size:14px;font-weight:700;color:#7c3aed;">2</div></td>
<td valign="top" style="padding-bottom:20px;padding-left:12px;">
<p style="margin:0;font-size:14px;font-weight:600;color:#1f2937;">Dispatch & Tracking</p>
<p style="margin:4px 0 0;font-size:13px;color:#6b7280;">You\'ll receive a tracking link once shipped.</p></td></tr>
<tr><td width="40" valign="top">
<div style="width:32px;height:32px;background:#d1fae5;border-radius:50%;text-align:center;line-height:32px;font-size:14px;font-weight:700;color:#065f46;">3</div></td>
<td valign="top" style="padding-left:12px;">
<p style="margin:0;font-size:14px;font-weight:600;color:#1f2937;">Delivery</p>
<p style="margin:4px 0 0;font-size:13px;color:#6b7280;">Estimated: <strong>{{estimated_delivery}}</strong></p></td></tr></table>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;"><tr><td align="center">
<a href="{{order_link}}" style="display:inline-block;background:#e61f7f;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:10px;">Track Your Order</a>
</td></tr></table>

<p style="margin:0;font-size:14px;color:#9ca3af;text-align:center;line-height:1.7;">Questions? Reply to this email or contact us at <a href="mailto:support@medinovapharma.com" style="color:#e61f7f;text-decoration:none;font-weight:500;">support@medinovapharma.com</a></p>
</td></tr>

<tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:28px 48px;text-align:center;border-radius:0 0 16px 16px;">
<p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#6b7280;">MediNova Pharma Pvt. Ltd.</p>
<p style="margin:0 0 16px;font-size:12px;color:#9ca3af;line-height:1.6;">123, Pharma Tower, Mumbai, Maharashtra 400001, India</p>
<table cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 16px;"><tr>
<td style="padding:0 8px;"><a href="#" style="font-size:12px;color:#e61f7f;text-decoration:none;">Privacy Policy</a></td>
<td style="color:#d1d5db;font-size:12px;">|</td>
<td style="padding:0 8px;"><a href="#" style="font-size:12px;color:#e61f7f;text-decoration:none;">Terms of Service</a></td>
<td style="color:#d1d5db;font-size:12px;">|</td>
<td style="padding:0 8px;"><a href="{{unsubscribe_link}}" style="font-size:12px;color:#9ca3af;text-decoration:none;">Unsubscribe</a></td></tr></table>
<p style="margin:0;font-size:11px;color:#d1d5db;">&copy; 2025 MediNova Pharma. All rights reserved.</p>
</td></tr>

</table></td></tr></table>
</body>
</html>',
            ],
            'order_tracking' => [
                'subject'   => '🚚 Tracking Update for Your Order #{{order_number}}',
                'variables' => ['{{customer_name}}', '{{order_number}}', '{{order_id}}', '{{tracking_number}}', '{{tracking_url}}', '{{courier_name}}', '{{estimated_delivery}}', '{{unsubscribe_link}}'],
                'body'      => '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<style type="text/css">body{margin:0;padding:0;background:#f4f4f7;font-family:"Segoe UI",Arial,sans-serif;-webkit-font-smoothing:antialiased;}</style></head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:\'Segoe UI\',Arial,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;font-size:1px;color:#f4f4f7;line-height:1px;">Your order #{{order_id}} is on its way! Track it now.</div>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f4f7;min-height:100vh;"><tr><td align="center" style="padding:40px 16px;">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
<tr><td style="background:linear-gradient(135deg,#e61f7f 0%,#c01468 100%);padding:36px 48px 32px;text-align:center;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">
<div style="display:inline-block;background:rgba(255,255,255,0.15);border-radius:12px;padding:10px 22px;margin-bottom:20px;">
<span style="color:#fff;font-size:20px;font-weight:700;">✦ MediNova Pharma</span></div></td></tr>
<tr><td align="center">
<h1 style="margin:0;color:#fff;font-size:26px;font-weight:700;line-height:1.3;">Tracking Update!</h1>
<p style="margin:10px 0 0;color:rgba(255,255,255,0.85);font-size:15px;line-height:1.5;">Your package is on the move, {{customer_name}}</p>
</td></tr></table></td></tr>
<tr><td style="padding:40px 48px 32px;">
<p style="margin:0 0 20px;font-size:16px;color:#1f2937;line-height:1.7;">Hi <strong style="color:#e61f7f;">{{customer_name}}</strong>,</p>
<p style="margin:0 0 32px;font-size:16px;color:#4b5563;line-height:1.8;">Great news! Your order <strong>#{{order_number}}</strong> is on its way and will arrive soon. Here are the tracking details:</p>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:32px;overflow:hidden;">
<tr><td style="padding:14px 20px;border-bottom:1px solid #e5e7eb;background:#f3f4f6;">
<p style="margin:0;font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:1px;">Tracking Details</p></td></tr>
<tr><td style="padding:20px;">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td style="font-size:14px;color:#6b7280;padding-bottom:12px;">Order ID</td>
<td align="right" style="font-size:14px;color:#374151;font-weight:600;padding-bottom:12px;">#{{order_id}}</td></tr>
<tr><td style="font-size:14px;color:#6b7280;padding-bottom:12px;border-bottom:1px solid #f0f0f0;">Courier Partner</td>
<td align="right" style="font-size:14px;color:#374151;font-weight:600;padding-bottom:12px;border-bottom:1px solid #f0f0f0;">{{courier_name}}</td></tr>
<tr><td style="font-size:14px;color:#6b7280;padding-top:12px;padding-bottom:12px;">Tracking Number</td>
<td align="right" style="font-size:14px;color:#e61f7f;font-weight:700;padding-top:12px;padding-bottom:12px;font-family:monospace;">{{tracking_number}}</td></tr>
<tr><td style="font-size:14px;color:#6b7280;padding-top:12px;">Est. Delivery</td>
<td align="right" style="font-size:14px;color:#374151;font-weight:600;padding-top:12px;">{{estimated_delivery}}</td></tr>
</table></td></tr></table>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;"><tr><td align="center">
<a href="{{tracking_url}}" style="display:inline-block;background:#e61f7f;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:10px;">Track Your Order</a>
</td></tr></table>
<p style="margin:0;font-size:14px;color:#9ca3af;text-align:center;line-height:1.7;">Questions? Contact us at <a href="mailto:support@medinovapharma.com" style="color:#e61f7f;text-decoration:none;font-weight:500;">support@medinovapharma.com</a></p>
</td></tr>
<tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:28px 48px;text-align:center;border-radius:0 0 16px 16px;">
<p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#6b7280;">MediNova Pharma Pvt. Ltd.</p>
<p style="margin:0 0 16px;font-size:12px;color:#9ca3af;line-height:1.6;">123, Pharma Tower, Mumbai, Maharashtra 400001, India</p>
<table cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 16px;"><tr>
<td style="padding:0 8px;"><a href="#" style="font-size:12px;color:#e61f7f;text-decoration:none;">Privacy Policy</a></td>
<td style="color:#d1d5db;font-size:12px;">|</td>
<td style="padding:0 8px;"><a href="#" style="font-size:12px;color:#e61f7f;text-decoration:none;">Terms of Service</a></td>
<td style="color:#d1d5db;font-size:12px;">|</td>
<td style="padding:0 8px;"><a href="{{unsubscribe_link}}" style="font-size:12px;color:#9ca3af;text-decoration:none;">Unsubscribe</a></td></tr></table>
<p style="margin:0;font-size:11px;color:#d1d5db;">&copy; 2025 MediNova Pharma. All rights reserved.</p>
</td></tr>
</table></td></tr></table>
</body>
</html>',
            ],
            'email_verification' => [
                'subject'   => '🔐 Verify Your Email — MediNova Pharma',
                'variables' => ['{{customer_name}}', '{{verification_url}}', '{{unsubscribe_link}}'],
                'body'      => '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<style type="text/css">body{margin:0;padding:0;background:#f4f4f7;font-family:"Segoe UI",Arial,sans-serif;-webkit-font-smoothing:antialiased;}</style></head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:\'Segoe UI\',Arial,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;font-size:1px;color:#f4f4f7;line-height:1px;">Verify your email address to get started with MediNova Pharma.</div>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f4f7;min-height:100vh;"><tr><td align="center" style="padding:40px 16px;">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
<tr><td style="background:linear-gradient(135deg,#e61f7f 0%,#c01468 100%);padding:36px 48px 32px;text-align:center;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">
<div style="display:inline-block;background:rgba(255,255,255,0.15);border-radius:12px;padding:10px 22px;margin-bottom:20px;">
<span style="color:#fff;font-size:20px;font-weight:700;">✦ MediNova Pharma</span></div></td></tr>
<tr><td align="center">
<h1 style="margin:0;color:#fff;font-size:26px;font-weight:700;line-height:1.3;">Verify Your Email</h1>
<p style="margin:10px 0 0;color:rgba(255,255,255,0.85);font-size:15px;line-height:1.5;">Welcome aboard, {{customer_name}}!</p>
</td></tr></table></td></tr>
<tr><td style="padding:40px 48px 32px;">
<p style="margin:0 0 20px;font-size:16px;color:#1f2937;line-height:1.7;">Hi <strong style="color:#e61f7f;">{{customer_name}}</strong>,</p>
<p style="margin:0 0 24px;font-size:16px;color:#4b5563;line-height:1.8;">Thank you for creating an account with <strong>MediNova Pharma</strong>. Please verify your email address by clicking the button below:</p>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;"><tr><td align="center">
<a href="{{verification_url}}" style="display:inline-block;background:#e61f7f;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:10px;">Verify Email Address</a>
</td></tr></table>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fffbeb;border-radius:12px;border:1px solid #fde68a;margin-bottom:32px;"><tr><td style="padding:16px 20px;">
<p style="margin:0;font-size:13px;color:#92400e;line-height:1.6;">This verification link will expire in <strong>60 minutes</strong>. If you did not create this account, you can safely ignore this email.</p>
</td></tr></table>
<p style="margin:0;font-size:14px;color:#9ca3af;text-align:center;line-height:1.7;">Need help? Contact us at <a href="mailto:support@medinovapharma.com" style="color:#e61f7f;text-decoration:none;font-weight:500;">support@medinovapharma.com</a></p>
</td></tr>
<tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:28px 48px;text-align:center;border-radius:0 0 16px 16px;">
<p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#6b7280;">MediNova Pharma Pvt. Ltd.</p>
<p style="margin:0 0 16px;font-size:12px;color:#9ca3af;line-height:1.6;">123, Pharma Tower, Mumbai, Maharashtra 400001, India</p>
<table cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 16px;"><tr>
<td style="padding:0 8px;"><a href="#" style="font-size:12px;color:#e61f7f;text-decoration:none;">Privacy Policy</a></td>
<td style="color:#d1d5db;font-size:12px;">|</td>
<td style="padding:0 8px;"><a href="#" style="font-size:12px;color:#e61f7f;text-decoration:none;">Terms of Service</a></td>
<td style="color:#d1d5db;font-size:12px;">|</td>
<td style="padding:0 8px;"><a href="{{unsubscribe_link}}" style="font-size:12px;color:#9ca3af;text-decoration:none;">Unsubscribe</a></td></tr></table>
<p style="margin:0;font-size:11px;color:#d1d5db;">&copy; 2025 MediNova Pharma. All rights reserved.</p>
</td></tr>
</table></td></tr></table>
</body>
</html>',
            ],
            'forgot_password' => [
                'subject'   => '🔑 Reset Your MediNova Pharma Password',
                'variables' => ['{{customer_name}}', '{{reset_url}}', '{{unsubscribe_link}}'],
                'body'      => '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<style type="text/css">body{margin:0;padding:0;background:#f4f4f7;font-family:"Segoe UI",Arial,sans-serif;-webkit-font-smoothing:antialiased;}</style></head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:\'Segoe UI\',Arial,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;font-size:1px;color:#f4f4f7;line-height:1px;">Reset your MediNova Pharma password. We received a password reset request.</div>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f4f7;min-height:100vh;"><tr><td align="center" style="padding:40px 16px;">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
<tr><td style="background:linear-gradient(135deg,#e61f7f 0%,#c01468 100%);padding:36px 48px 32px;text-align:center;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">
<div style="display:inline-block;background:rgba(255,255,255,0.15);border-radius:12px;padding:10px 22px;margin-bottom:20px;">
<span style="color:#fff;font-size:20px;font-weight:700;">✦ MediNova Pharma</span></div></td></tr>
<tr><td align="center">
<h1 style="margin:0;color:#fff;font-size:26px;font-weight:700;line-height:1.3;">Reset Your Password</h1>
<p style="margin:10px 0 0;color:rgba(255,255,255,0.85);font-size:15px;line-height:1.5;">We\'ve got you covered, {{customer_name}}</p>
</td></tr></table></td></tr>
<tr><td style="padding:40px 48px 32px;">
<p style="margin:0 0 20px;font-size:16px;color:#1f2937;line-height:1.7;">Hi <strong style="color:#e61f7f;">{{customer_name}}</strong>,</p>
<p style="margin:0 0 24px;font-size:16px;color:#4b5563;line-height:1.8;">We received a request to reset your password for your <strong>MediNova Pharma</strong> account. Click the button below to set a new password:</p>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;"><tr><td align="center">
<a href="{{reset_url}}" style="display:inline-block;background:#e61f7f;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:10px;">Reset Password</a>
</td></tr></table>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fffbeb;border-radius:12px;border:1px solid #fde68a;margin-bottom:32px;"><tr><td style="padding:16px 20px;">
<p style="margin:0;font-size:13px;color:#92400e;line-height:1.6;">This password reset link will expire in <strong>60 minutes</strong>. If you did not request a password reset, please ignore this email — your account is safe.</p>
</td></tr></table>
<p style="margin:0;font-size:14px;color:#9ca3af;text-align:center;line-height:1.7;">Need help? Contact us at <a href="mailto:support@medinovapharma.com" style="color:#e61f7f;text-decoration:none;font-weight:500;">support@medinovapharma.com</a></p>
</td></tr>
<tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:28px 48px;text-align:center;border-radius:0 0 16px 16px;">
<p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#6b7280;">MediNova Pharma Pvt. Ltd.</p>
<p style="margin:0 0 16px;font-size:12px;color:#9ca3af;line-height:1.6;">123, Pharma Tower, Mumbai, Maharashtra 400001, India</p>
<table cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 16px;"><tr>
<td style="padding:0 8px;"><a href="#" style="font-size:12px;color:#e61f7f;text-decoration:none;">Privacy Policy</a></td>
<td style="color:#d1d5db;font-size:12px;">|</td>
<td style="padding:0 8px;"><a href="#" style="font-size:12px;color:#e61f7f;text-decoration:none;">Terms of Service</a></td>
<td style="color:#d1d5db;font-size:12px;">|</td>
<td style="padding:0 8px;"><a href="{{unsubscribe_link}}" style="font-size:12px;color:#9ca3af;text-decoration:none;">Unsubscribe</a></td></tr></table>
<p style="margin:0;font-size:11px;color:#d1d5db;">&copy; 2025 MediNova Pharma. All rights reserved.</p>
</td></tr>
</table></td></tr></table>
</body>
</html>',
            ],
            'welcome' => [
                'subject'   => '👋 Welcome to MediNova Pharma, {{customer_name}}!',
                'variables' => ['{{customer_name}}', '{{dashboard_link}}', '{{unsubscribe_link}}'],
                'body'      => '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<style type="text/css">body{margin:0;padding:0;background:#f4f4f7;font-family:"Segoe UI",Arial,sans-serif;-webkit-font-smoothing:antialiased;}</style></head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:\'Segoe UI\',Arial,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;font-size:1px;color:#f4f4f7;line-height:1px;">Welcome to MediNova Pharma! Start exploring your health journey with us.</div>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f4f7;min-height:100vh;"><tr><td align="center" style="padding:40px 16px;">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
<tr><td style="background:linear-gradient(135deg,#e61f7f 0%,#c01468 100%);padding:36px 48px 32px;text-align:center;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">
<div style="display:inline-block;background:rgba(255,255,255,0.15);border-radius:12px;padding:10px 22px;margin-bottom:20px;">
<span style="color:#fff;font-size:20px;font-weight:700;">✦ MediNova Pharma</span></div></td></tr>
<tr><td align="center">
<h1 style="margin:0;color:#fff;font-size:26px;font-weight:700;line-height:1.3;">Welcome Aboard! 🎉</h1>
<p style="margin:10px 0 0;color:rgba(255,255,255,0.85);font-size:15px;line-height:1.5;">We\'re thrilled to have you, {{customer_name}}</p>
</td></tr></table></td></tr>
<tr><td style="padding:40px 48px 32px;">
<p style="margin:0 0 20px;font-size:16px;color:#1f2937;line-height:1.7;">Hi <strong style="color:#e61f7f;">{{customer_name}}</strong>,</p>
<p style="margin:0 0 24px;font-size:16px;color:#4b5563;line-height:1.8;">A warm welcome from the entire <strong>MediNova Pharma</strong> family! We\'re delighted to have you with us.</p>
<p style="margin:0 0 24px;font-size:15px;color:#4b5563;line-height:1.8;">Your account is all set and ready to go. Here\'s what you can do now:</p>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
<tr><td width="40" valign="top" style="padding-bottom:16px;">
<div style="width:28px;height:28px;background:#fce7f3;border-radius:50%;text-align:center;line-height:28px;font-size:13px;font-weight:700;color:#e61f7f;">1</div></td>
<td valign="top" style="padding-bottom:16px;padding-left:12px;">
<p style="margin:0;font-size:14px;font-weight:600;color:#1f2937;">Browse 10,000+ Products</p>
<p style="margin:2px 0 0;font-size:13px;color:#6b7280;">From prescription medicines to wellness essentials — we have it all.</p></td></tr>
<tr><td width="40" valign="top" style="padding-bottom:16px;">
<div style="width:28px;height:28px;background:#ede9fe;border-radius:50%;text-align:center;line-height:28px;font-size:13px;font-weight:700;color:#7c3aed;">2</div></td>
<td valign="top" style="padding-bottom:16px;padding-left:12px;">
<p style="margin:0;font-size:14px;font-weight:600;color:#1f2937;">Upload Prescriptions</p>
<p style="margin:2px 0 0;font-size:13px;color:#6b7280;">Order Rx medicines easily by uploading your prescription.</p></td></tr>
<tr><td width="40" valign="top">
<div style="width:28px;height:28px;background:#d1fae5;border-radius:50%;text-align:center;line-height:28px;font-size:13px;font-weight:700;color:#065f46;">3</div></td>
<td valign="top" style="padding-left:12px;">
<p style="margin:0;font-size:14px;font-weight:600;color:#1f2937;">Track Orders & Wallet</p>
<p style="margin:2px 0 0;font-size:13px;color:#6b7280;">Get real-time updates and manage your wallet balance.</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;"><tr><td align="center">
<a href="{{dashboard_link}}" style="display:inline-block;background:#e61f7f;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:10px;">Go to Your Dashboard</a>
</td></tr></table>
<hr style="border:none;border-top:1px solid #f3f4f6;margin:0 0 24px;">
<p style="margin:0 0 8px;font-size:14px;color:#1f2937;font-weight:600;">Got a referral code?</p>
<p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.7;">Share your referral link with friends and family. You both get <strong style="color:#e61f7f;">$50 off</strong> on your next order!</p>
<p style="margin:0;font-size:14px;color:#9ca3af;text-align:center;line-height:1.7;">Questions? We\'re here to help at <a href="mailto:support@medinovapharma.com" style="color:#e61f7f;text-decoration:none;font-weight:500;">support@medinovapharma.com</a></p>
</td></tr>
<tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:28px 48px;text-align:center;border-radius:0 0 16px 16px;">
<p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#6b7280;">MediNova Pharma Pvt. Ltd.</p>
<p style="margin:0 0 16px;font-size:12px;color:#9ca3af;line-height:1.6;">123, Pharma Tower, Mumbai, Maharashtra 400001, India</p>
<table cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 16px;"><tr>
<td style="padding:0 8px;"><a href="#" style="font-size:12px;color:#e61f7f;text-decoration:none;">Privacy Policy</a></td>
<td style="color:#d1d5db;font-size:12px;">|</td>
<td style="padding:0 8px;"><a href="#" style="font-size:12px;color:#e61f7f;text-decoration:none;">Terms of Service</a></td>
<td style="color:#d1d5db;font-size:12px;">|</td>
<td style="padding:0 8px;"><a href="{{unsubscribe_link}}" style="font-size:12px;color:#9ca3af;text-decoration:none;">Unsubscribe</a></td></tr></table>
<p style="margin:0;font-size:11px;color:#d1d5db;">&copy; 2025 MediNova Pharma. All rights reserved.</p>
</td></tr>
</table></td></tr></table>
</body>
</html>',
            ],
        ];
    }
}
