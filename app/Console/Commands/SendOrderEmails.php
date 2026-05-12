<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\MailService;
use Illuminate\Console\Command;

class SendOrderEmails extends Command
{
    protected $signature = 'orders:send-emails {--resend-pending : Re-send confirmation for confirmed orders that might have missed it}';
    protected $description = 'Send pending order confirmation/tracking emails for existing orders';

    public function handle(MailService $mailService): int
    {
        if ($this->option('resend-pending')) {
            $orders = Order::whereIn('status', [
                OrderStatus::Confirmed->value,
                OrderStatus::Processing->value,
            ])->whereNull('cancelled_at')->get();

            if ($orders->isEmpty()) {
                $this->info('No pending orders found.');
                return 0;
            }

            $this->info('Sending confirmation emails for ' . $orders->count() . ' orders...');

            foreach ($orders as $order) {
                $sent = $mailService->sendTemplateEmail(
                    'order_complete',
                    $order->user?->email,
                    $order->shipping_name,
                    $mailService->orderVariables($order),
                );

                if ($sent) {
                    $this->line("  ✓ #{$order->order_number} → {$order->user?->email}");
                } else {
                    $this->warn("  ✗ #{$order->order_number} → FAILED (no template or SMTP error)");
                }
            }
        } else {
            $this->info('Usage: php artisan orders:send-emails --resend-pending');
        }

        return 0;
    }
}
