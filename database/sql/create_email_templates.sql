-- ============================================================
-- MediNova Pharma — email_templates table fix
-- Run this in Hostinger phpMyAdmin if Laravel migration fails
-- ============================================================

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(255) NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` LONGTEXT NOT NULL,
  `variables` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_key_unique` (`key`),
  KEY `email_templates_key_index` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default templates (ignore if already exists)
INSERT IGNORE INTO `email_templates` (`key`, `label`, `subject`, `body`, `variables`, `is_active`, `created_at`, `updated_at`) VALUES
('order_complete', 'Order Completed', '✅ Your Order #{{order_number}} has been delivered!',
 '<h2>Hi {{customer_name}},</h2><p>Great news! Your order <strong>#{{order_number}}</strong> has been successfully delivered.</p><p>Thank you for shopping with <strong>MediNova Pharma</strong>. We hope you are satisfied with your purchase.</p><p>If you have any questions, feel free to contact us.</p><p>Warm regards,<br><strong>MediNova Pharma Team</strong></p>',
 '["{{order_number}}","{{customer_name}}","{{order_total}}","{{order_date}}"]', 1, NOW(), NOW()),

('order_tracking', 'Tracking Update', '🚚 Tracking Update for Your Order #{{order_number}}',
 '<h2>Hi {{customer_name}},</h2><p>Your order <strong>#{{order_number}}</strong> is on its way!</p><p><strong>Tracking Number:</strong> {{tracking_number}}<br><strong>Courier:</strong> {{courier_name}}</p><p><a href="{{tracking_url}}" style="background:#e63946;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;">Track Your Order</a></p><p>Warm regards,<br><strong>MediNova Pharma Team</strong></p>',
 '["{{order_number}}","{{customer_name}}","{{tracking_number}}","{{tracking_url}}","{{courier_name}}"]', 1, NOW(), NOW()),

('email_verification', 'Email Verification', '🔐 Verify Your Email — MediNova Pharma',
 '<h2>Welcome to MediNova Pharma!</h2><p>Hi {{customer_name}},</p><p>Please verify your email address by clicking the button below:</p><p><a href="{{verification_url}}" style="background:#e63946;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;">Verify Email</a></p><p>This link expires in 60 minutes. If you did not register, ignore this email.</p><p>Warm regards,<br><strong>MediNova Pharma Team</strong></p>',
 '["{{customer_name}}","{{verification_url}}"]', 1, NOW(), NOW()),

('forgot_password', 'Forgot Password', '🔑 Reset Your MediNova Pharma Password',
 '<h2>Hi {{customer_name}},</h2><p>We received a request to reset your password. Click the button below to set a new password:</p><p><a href="{{reset_url}}" style="background:#e63946;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;">Reset Password</a></p><p>This link expires in 60 minutes. If you did not request this, ignore this email — your password is safe.</p><p>Warm regards,<br><strong>MediNova Pharma Team</strong></p>',
 '["{{customer_name}}","{{reset_url}}"]', 1, NOW(), NOW()),

('welcome', 'Welcome Email', '🎉 Welcome to MediNova Pharma, {{customer_name}}!',
 '<h2>Welcome to MediNova Pharma, {{customer_name}}!</h2><p>We\'re thrilled to have you on board. Your account has been created successfully.</p><p>Start exploring our wide range of pharmaceutical products and enjoy a seamless shopping experience.</p><p><a href="{{dashboard_link}}" style="background:#e63946;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;">Go to Dashboard</a></p><p>If you have any questions, feel free to contact our support team.</p><p>Warm regards,<br><strong>MediNova Pharma Team</strong></p>',
 '["{{customer_name}}","{{dashboard_link}}","{{unsubscribe_link}}"]', 1, NOW(), NOW());

-- Also mark this migration as run so Laravel doesn't try to run it again
-- (only needed if you ran it manually instead of via artisan)
INSERT IGNORE INTO `migrations` (`migration`, `batch`)
SELECT '2026_05_09_000001_create_email_templates_table', COALESCE(MAX(batch), 0) + 1 FROM `migrations`;
