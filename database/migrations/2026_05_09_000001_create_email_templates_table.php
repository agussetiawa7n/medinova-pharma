<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();       // e.g. order_complete
            $table->string('label');                        // Human-readable label
            $table->string('subject');                      // Email subject
            $table->longText('body');                       // HTML body
            $table->text('variables')->nullable();          // JSON list of available variables
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default templates
        $templates = [
            [
                'key'       => 'order_complete',
                'label'     => 'Order Completed',
                'subject'   => '✅ Your Order #{{order_number}} has been delivered!',
                'body'      => '<h2>Hi {{customer_name}},</h2><p>Great news! Your order <strong>#{{order_number}}</strong> has been successfully delivered.</p><p>Thank you for shopping with <strong>MediNova Pharma</strong>. We hope you are satisfied with your purchase.</p><p>If you have any questions, feel free to contact us.</p><p>Warm regards,<br><strong>MediNova Pharma Team</strong></p>',
                'variables' => json_encode(['{{order_number}}', '{{customer_name}}', '{{order_total}}', '{{order_date}}']),
                'is_active' => true,
            ],
            [
                'key'       => 'order_tracking',
                'label'     => 'Tracking Update',
                'subject'   => '🚚 Tracking Update for Your Order #{{order_number}}',
                'body'      => '<h2>Hi {{customer_name}},</h2><p>Your order <strong>#{{order_number}}</strong> is on its way!</p><p><strong>Tracking Number:</strong> {{tracking_number}}<br><strong>Courier:</strong> {{courier_name}}</p><p><a href="{{tracking_url}}" style="background:#e63946;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;">Track Your Order</a></p><p>Warm regards,<br><strong>MediNova Pharma Team</strong></p>',
                'variables' => json_encode(['{{order_number}}', '{{customer_name}}', '{{tracking_number}}', '{{tracking_url}}', '{{courier_name}}']),
                'is_active' => true,
            ],
            [
                'key'       => 'email_verification',
                'label'     => 'Email Verification',
                'subject'   => '🔐 Verify Your Email — MediNova Pharma',
                'body'      => '<h2>Welcome to MediNova Pharma!</h2><p>Hi {{customer_name}},</p><p>Please verify your email address by clicking the button below:</p><p><a href="{{verification_url}}" style="background:#e63946;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;">Verify Email</a></p><p>This link expires in 60 minutes. If you did not register, ignore this email.</p><p>Warm regards,<br><strong>MediNova Pharma Team</strong></p>',
                'variables' => json_encode(['{{customer_name}}', '{{verification_url}}']),
                'is_active' => true,
            ],
            [
                'key'       => 'forgot_password',
                'label'     => 'Forgot Password',
                'subject'   => '🔑 Reset Your MediNova Pharma Password',
                'body'      => '<h2>Hi {{customer_name}},</h2><p>We received a request to reset your password. Click the button below to set a new password:</p><p><a href="{{reset_url}}" style="background:#e63946;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;">Reset Password</a></p><p>This link expires in 60 minutes. If you did not request this, ignore this email — your password is safe.</p><p>Warm regards,<br><strong>MediNova Pharma Team</strong></p>',
                'variables' => json_encode(['{{customer_name}}', '{{reset_url}}']),
                'is_active' => true,
            ],
            [
                'key'       => 'welcome',
                'label'     => 'Welcome Email',
                'subject'   => '🎉 Welcome to MediNova Pharma, {{customer_name}}!',
                'body'      => '<h2>Welcome to MediNova Pharma, {{customer_name}}!</h2><p>We\'re thrilled to have you on board. Your account has been created successfully.</p><p>Start exploring our wide range of pharmaceutical products and enjoy a seamless shopping experience.</p><p><a href="{{dashboard_link}}" style="background:#e63946;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;">Go to Dashboard</a></p><p>If you have any questions, feel free to contact our support team.</p><p>Warm regards,<br><strong>MediNova Pharma Team</strong></p>',
                'variables' => json_encode(['{{customer_name}}', '{{dashboard_link}}', '{{unsubscribe_link}}']),
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            DB::table('email_templates')->insert(array_merge($template, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
