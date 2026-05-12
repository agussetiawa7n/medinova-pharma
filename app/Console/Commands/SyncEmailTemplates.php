<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class SyncEmailTemplates extends Command
{
    protected $signature = 'email-templates:sync';
    protected $description = 'Sync all email templates with the latest default designs';

    private array $labels = [
        'order_complete'     => 'Order Completed',
        'order_tracking'     => 'Tracking Update',
        'email_verification' => 'Email Verification',
        'welcome'            => 'Welcome',
        'forgot_password'    => 'Forgot Password',
    ];

    public function handle(): int
    {
        $page = new \App\Filament\Pages\EmailTemplates();
        $defaults = $page->getDefaults();

        $count = 0;
        foreach ($defaults as $key => $config) {
            EmailTemplate::updateOrCreate(
                ['key' => $key],
                [
                    'label'     => $this->labels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                    'subject'   => $config['subject'],
                    'body'      => $config['body'],
                    'variables' => $config['variables'] ?? [],
                    'is_active' => true,
                ]
            );
            $this->line("  ✓ {$key} — synced");
            $count++;
        }

        $this->info("Done! {$count} templates updated with latest HTML designs.");
        return 0;
    }
}
