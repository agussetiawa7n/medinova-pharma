<?php

namespace App\Services;

use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function sendTemplateEmail(string $templateKey, string $to, string $toName, array $variables): bool
    {
        $template = EmailTemplate::getByKey($templateKey);
        if (!$template) {
            return false;
        }

        $subject = $template->renderSubject($variables);
        $body    = $template->renderBody($variables);

        // Apply runtime config from .env (purges cached mailer)
        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.transport'  => 'smtp',
            'mail.mailers.smtp.host'       => config('mail.mailers.smtp.host', env('MAIL_HOST')),
            'mail.mailers.smtp.port'       => (int) (config('mail.mailers.smtp.port', env('MAIL_PORT', 587))),
            'mail.mailers.smtp.username'   => config('mail.mailers.smtp.username', env('MAIL_USERNAME')),
            'mail.mailers.smtp.password'   => config('mail.mailers.smtp.password', env('MAIL_PASSWORD')),
            'mail.mailers.smtp.encryption' => config('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION', 'tls')) ?: null,
            'mail.from.address'            => config('mail.from.address', env('MAIL_FROM_ADDRESS')),
            'mail.from.name'               => config('mail.from.name', env('MAIL_FROM_NAME', 'MediNova Pharma')),
        ]);

        try {
            // Purge cached transport so it picks up fresh config
            Mail::purge('smtp');

            Mail::send([], [], function ($message) use ($to, $toName, $subject, $body) {
                $message->to($to, $toName)
                    ->subject($subject)
                    ->html($body);
            });

            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Send email verification link to a newly registered user.
     */
    public function sendVerificationEmail(\App\Models\User $user): bool
    {
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['user' => $user->id],
        );

        return $this->sendTemplateEmail(
            'email_verification',
            $user->email,
            $user->name,
            [
                '{{customer_name}}'  => $user->name,
                '{{verification_url}}' => $verificationUrl,
                '{{unsubscribe_link}}' => url('/unsubscribe'),
            ],
        );
    }

    /**
     * Build order confirmation variables from an Order model.
     */
    public function orderVariables(\App\Models\Order $order): array
    {
        $firstItem = $order->items->first();
        $productName = $firstItem ? $firstItem->product_name . ($firstItem->variant_name ? ' (' . $firstItem->variant_name . ')' : '') : '—';
        $amount = $firstItem ? number_format($firstItem->unit_price, 2) : '0.00';
        $quantity = $firstItem ? $firstItem->quantity : 1;

        $walletUsed = $order->wallet_amount_used > 0
            ? '$' . number_format($order->wallet_amount_used, 2)
            : '—';

        return [
            '{{customer_name}}'      => $order->user?->name ?? 'Valued Customer',
            '{{order_number}}'       => $order->order_number,
            '{{order_id}}'           => $order->order_number,
            '{{product_name}}'       => $productName,
            '{{amount}}'             => '$' . $amount,
            '{{quantity}}'           => (string) $quantity,
            '{{subtotal}}'           => '$' . number_format($order->subtotal, 2),
            '{{shipping_fee}}'       => '$' . number_format($order->shipping_amount, 2),
            '{{tax}}'                => '$' . number_format($order->tax_amount, 2),
            '{{wallet_used}}'        => $walletUsed,
            '{{total_amount}}'       => '$' . number_format($order->total, 2),
            '{{order_date}}'         => $order->created_at->format('d M Y'),
            '{{estimated_delivery}}' => now()->addDays(3)->format('d M Y'),
            '{{order_link}}'         => url('/orders/' . $order->id),
            '{{unsubscribe_link}}'   => url('/unsubscribe'),
        ];
    }
}
