<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('settings')->delete();

        $rows = [
            [
                'id' => 1,
                'group' => 'general',
                'key' => 'site_name',
                'value' => 'MediNova Pharma',
                'type' => 'string',
                'created_at' => '2026-04-17 15:04:39',
                'updated_at' => '2026-04-17 15:04:39'
            ],
            [
                'id' => 2,
                'group' => 'general',
                'key' => 'site_tagline',
                'value' => 'Your trusted online pharmacy',
                'type' => 'string',
                'created_at' => '2026-04-17 15:04:39',
                'updated_at' => '2026-04-17 15:04:39'
            ],
            [
                'id' => 3,
                'group' => 'general',
                'key' => 'contact_email',
                'value' => 'support@medinovapharma.com',
                'type' => 'string',
                'created_at' => '2026-04-17 15:04:39',
                'updated_at' => '2026-04-17 15:04:39'
            ],
            [
                'id' => 4,
                'group' => 'general',
                'key' => 'contact_phone',
                'value' => '+91 98765 43210',
                'type' => 'string',
                'created_at' => '2026-04-17 15:04:39',
                'updated_at' => '2026-04-17 15:04:39'
            ],
            [
                'id' => 7,
                'group' => 'tax',
                'key' => 'pricing.tax_rate',
                'value' => '18',
                'type' => 'integer',
                'created_at' => '2026-04-17 15:04:39',
                'updated_at' => '2026-04-29 19:08:30'
            ],
            [
                'id' => 8,
                'group' => 'general',
                'key' => 'currency',
                'value' => 'INR',
                'type' => 'string',
                'created_at' => '2026-04-17 15:04:39',
                'updated_at' => '2026-04-17 15:04:39'
            ],
            [
                'id' => 9,
                'group' => 'general',
                'key' => 'currency_symbol',
                'value' => '₹',
                'type' => 'string',
                'created_at' => '2026-04-17 15:04:39',
                'updated_at' => '2026-04-17 15:04:39'
            ],
            [
                'id' => 10,
                'group' => 'seo',
                'key' => 'meta_title',
                'value' => 'MediNova Pharma - Online Pharmacy',
                'type' => 'string',
                'created_at' => '2026-04-17 15:04:39',
                'updated_at' => '2026-04-17 15:04:39'
            ],
            [
                'id' => 11,
                'group' => 'seo',
                'key' => 'meta_description',
                'value' => 'Buy medicines online safely.',
                'type' => 'string',
                'created_at' => '2026-04-17 15:04:39',
                'updated_at' => '2026-04-17 15:04:39'
            ],
            [
                'id' => 17,
                'group' => 'payment',
                'key' => 'payment.razorpay_enabled',
                'value' => '',
                'type' => 'boolean',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-29 19:08:30'
            ],
            [
                'id' => 18,
                'group' => 'payment',
                'key' => 'payment.stripe_enabled',
                'value' => '',
                'type' => 'boolean',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-29 19:08:30'
            ],
            [
                'id' => 19,
                'group' => 'payment',
                'key' => 'payment.paypal_enabled',
                'value' => '',
                'type' => 'boolean',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-29 19:08:30'
            ],
            [
                'id' => 20,
                'group' => 'payment',
                'key' => 'payment.cod_enabled',
                'value' => '',
                'type' => 'boolean',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-29 19:08:30'
            ],
            [
                'id' => 21,
                'group' => 'payment',
                'key' => 'payment.wallet_enabled',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-29 19:08:30'
            ],
            [
                'id' => 22,
                'group' => 'payment',
                'key' => 'razorpay_key',
                'value' => '',
                'type' => 'string',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-18 07:24:47'
            ],
            [
                'id' => 23,
                'group' => 'payment',
                'key' => 'razorpay_secret',
                'value' => '',
                'type' => 'string',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-18 07:24:47'
            ],
            [
                'id' => 24,
                'group' => 'payment',
                'key' => 'stripe_key',
                'value' => '',
                'type' => 'string',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-18 07:24:47'
            ],
            [
                'id' => 25,
                'group' => 'payment',
                'key' => 'stripe_secret',
                'value' => '',
                'type' => 'string',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-18 07:24:47'
            ],
            [
                'id' => 26,
                'group' => 'payment',
                'key' => 'stripe_webhook_secret',
                'value' => '',
                'type' => 'string',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-18 07:24:47'
            ],
            [
                'id' => 27,
                'group' => 'payment',
                'key' => 'paypal_mode',
                'value' => 'sandbox',
                'type' => 'string',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-18 07:24:47'
            ],
            [
                'id' => 28,
                'group' => 'payment',
                'key' => 'paypal_client_id',
                'value' => '',
                'type' => 'string',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-18 07:24:47'
            ],
            [
                'id' => 29,
                'group' => 'payment',
                'key' => 'paypal_client_secret',
                'value' => '',
                'type' => 'string',
                'created_at' => '2026-04-18 07:24:47',
                'updated_at' => '2026-04-18 07:24:47'
            ],
            [
                'id' => 30,
                'group' => 'payment',
                'key' => 'wallet.external_enabled',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => '2026-04-26 15:31:29',
                'updated_at' => '2026-04-29 19:08:30'
            ],
            [
                'id' => 31,
                'group' => 'payment',
                'key' => 'wallet.purchase_url',
                'value' => 'https://example.com/',
                'type' => 'string',
                'created_at' => '2026-04-26 15:31:29',
                'updated_at' => '2026-04-29 19:08:30'
            ],
            [
                'id' => 32,
                'group' => 'payment',
                'key' => 'wallet.shared_secret',
                'value' => '',
                'type' => 'string',
                'created_at' => '2026-04-26 15:31:29',
                'updated_at' => '2026-04-29 19:08:30'
            ],
            [
                'id' => 33,
                'group' => 'payment',
                'key' => 'wallet.tutorial_video',
                'value' => 'https://www.youtube.com/watch?v=MyLvlbL85h8',
                'type' => 'string',
                'created_at' => '2026-04-26 15:31:29',
                'updated_at' => '2026-04-29 19:08:30'
            ],
            [
                'id' => 34,
                'group' => 'homepage',
                'key' => 'home.show_feature_strip',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 35,
                'group' => 'homepage',
                'key' => 'home.show_categories',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 36,
                'group' => 'homepage',
                'key' => 'home.show_flash_sale',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 37,
                'group' => 'homepage',
                'key' => 'home.show_promo_banners',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 38,
                'group' => 'homepage',
                'key' => 'home.show_featured',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 39,
                'group' => 'homepage',
                'key' => 'home.show_prescription_cta',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 40,
                'group' => 'homepage',
                'key' => 'home.show_new_arrivals',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 41,
                'group' => 'homepage',
                'key' => 'home.show_best_sellers',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 42,
                'group' => 'homepage',
                'key' => 'home.show_faq',
                'value' => '1',
                'type' => 'boolean',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 43,
                'group' => 'homepage',
                'key' => 'home.hero_title',
                'value' => 'Your Health, Our Priority',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 44,
                'group' => 'homepage',
                'key' => 'home.hero_subtitle',
                'value' => 'Genuine medicines, vitamins and wellness essentials delivered safely to your doorstep.',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 45,
                'group' => 'homepage',
                'key' => 'home.hero_button_text',
                'value' => 'Shop Now',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 46,
                'group' => 'homepage',
                'key' => 'home.hero_button_url',
                'value' => '/products',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 47,
                'group' => 'homepage',
                'key' => 'home.feature_1',
                'value' => '100% Genuine Medicines',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:24',
                'updated_at' => '2026-04-30 20:56:24'
            ],
            [
                'id' => 48,
                'group' => 'homepage',
                'key' => 'home.feature_2',
                'value' => 'Customer Satisfaction',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:25',
                'updated_at' => '2026-04-30 20:56:25'
            ],
            [
                'id' => 49,
                'group' => 'homepage',
                'key' => 'home.feature_3',
                'value' => 'Trusted Pharmacy',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:25',
                'updated_at' => '2026-04-30 20:56:25'
            ],
            [
                'id' => 50,
                'group' => 'homepage',
                'key' => 'home.feature_4',
                'value' => 'Fast Home Delivery',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:25',
                'updated_at' => '2026-04-30 20:56:25'
            ],
            [
                'id' => 51,
                'group' => 'homepage',
                'key' => 'home.feature_5',
                'value' => 'Expert Pharmacist Support',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:25',
                'updated_at' => '2026-04-30 20:56:25'
            ],
            [
                'id' => 52,
                'group' => 'homepage',
                'key' => 'home.category_ids',
                'value' => '["29","28","27","30","31","32"]',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:25',
                'updated_at' => '2026-05-01 20:23:43'
            ],
            [
                'id' => 53,
                'group' => 'homepage',
                'key' => 'home.category_count',
                'value' => '6',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:25',
                'updated_at' => '2026-04-30 20:56:57'
            ],
            [
                'id' => 54,
                'group' => 'homepage',
                'key' => 'home.rx_cta_title',
                'value' => 'Upload your prescription, we\'ll do the rest',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:25',
                'updated_at' => '2026-04-30 20:56:25'
            ],
            [
                'id' => 55,
                'group' => 'homepage',
                'key' => 'home.rx_cta_subtitle',
                'value' => 'Quick verification by certified pharmacists.',
                'type' => 'string',
                'created_at' => '2026-04-30 20:56:25',
                'updated_at' => '2026-04-30 20:56:25'
            ],
            [
                'id' => 56,
                'group' => 'homepage',
                'key' => 'home.promo_banners',
                'value' => '[]',
                'type' => 'string',
                'created_at' => '2026-05-01 20:01:28',
                'updated_at' => '2026-05-01 20:01:28'
            ],
            [
                'id' => 57,
                'group' => 'homepage',
                'key' => 'home.promo_left_banner',
                'value' => '{"title":"","subtitle":"","url":"","image":""}',
                'type' => 'string',
                'created_at' => '2026-05-01 20:01:28',
                'updated_at' => '2026-05-01 20:01:28'
            ],
            [
                'id' => 58,
                'group' => 'homepage',
                'key' => 'home.promo_right_banner',
                'value' => '{"title":"","subtitle":"","url":"","image":""}',
                'type' => 'string',
                'created_at' => '2026-05-01 20:01:28',
                'updated_at' => '2026-05-01 20:01:28'
            ],
            [
                'id' => 59,
                'group' => 'homepage',
                'key' => 'home.faqs',
                'value' => '[{"question":"Are all medicines on MediNova Pharma 100% genuine?","answer":"Yes \\u2014 every product is sourced directly from licensed manufacturers and authorised distributors. We operate as a registered pharmacy and provide an authenticity guarantee on every order, with batch-level traceability."},{"question":"How do I order prescription medicines?","answer":"Simply upload a clear photo or PDF of your prescription at checkout or from your dashboard. Our licensed pharmacists verify it within 2\\u20134 hours during business hours, and dispatch your order as soon as it is approved."},{"question":"What is the delivery timeline?","answer":"Metro cities: 1\\u20132 business days. Tier 2 cities: 2\\u20134 business days. Express 4-hour delivery is available in select areas for an additional fee. You\'ll receive SMS & email tracking updates once shipped."},{"question":"What is your return policy?","answer":"Sealed, unopened OTC products can be returned within 7 days of delivery. Prescription medicines, opened products, cold-chain items, and personal-care products are non-returnable for safety and hygiene reasons."},{"question":"Do you accept cash on delivery?","answer":"Yes \\u2014 COD is available on orders up to $5,000 in most serviceable pincodes. We also accept UPI, credit\\/debit cards, net banking, and MediNova Wallet. All transactions are secured with 256-bit SSL encryption."},{"question":"Are your products cruelty-free and safe?","answer":"All medicines are manufactured per the Indian Pharmacopoeia and stored under controlled temperature. Personal-care and wellness brands on our platform explicitly state their cruelty-free\\/vegan certifications on the product page."}]',
                'type' => 'string',
                'created_at' => '2026-05-01 20:01:28',
                'updated_at' => '2026-05-01 20:01:28'
            ],
            [
                'id' => 60,
                'group' => 'menu',
                'key' => 'menu.mega_menu_image',
                'value' => 'menu/sWspWNpFFJYlhHKq26PdejEtcu7tPmb5JV8BzB48.png',
                'type' => 'string',
                'created_at' => '2026-05-01 21:38:12',
                'updated_at' => '2026-05-01 21:58:56'
            ],
            [
                'id' => 61,
                'group' => 'menu',
                'key' => 'menu.mega_menu_tagline',
                'value' => '',
                'type' => 'string',
                'created_at' => '2026-05-01 22:07:06',
                'updated_at' => '2026-05-01 22:07:06'
            ],
            [
                'id' => 62,
                'group' => 'menu',
                'key' => 'menu.mega_menu_title',
                'value' => '',
                'type' => 'string',
                'created_at' => '2026-05-01 22:07:06',
                'updated_at' => '2026-05-01 22:07:06'
            ],
            [
                'id' => 63,
                'group' => 'menu',
                'key' => 'menu.mega_menu_button_text',
                'value' => 'Shop Now',
                'type' => 'string',
                'created_at' => '2026-05-01 22:07:06',
                'updated_at' => '2026-05-01 22:07:06'
            ],
            [
                'id' => 64,
                'group' => 'contact',
                'key' => 'contact.breadcrumb_title',
                'value' => 'Contact Us',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 19:41:22'
            ],
            [
                'id' => 65,
                'group' => 'contact',
                'key' => 'contact.breadcrumb_image',
                'value' => 'contact/tXb4BlBL1IveBF6ajIN5fY1YCxtijUqfSL49FEVE.jpg',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 19:58:33'
            ],
            [
                'id' => 66,
                'group' => 'contact',
                'key' => 'contact.form_heading',
                'value' => 'GET IN TOUCH',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 19:41:22'
            ],
            [
                'id' => 67,
                'group' => 'contact',
                'key' => 'contact.form_subtitle',
                'value' => 'Have a question or need assistance? Fill out the form below and our team will get back to you as soon as possible — usually within 2 hours.',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 19:41:22'
            ],
            [
                'id' => 68,
                'group' => 'contact',
                'key' => 'contact.info_heading',
                'value' => 'CONTACT INFORMATION',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 19:41:22'
            ],
            [
                'id' => 69,
                'group' => 'contact',
                'key' => 'contact.info_subtitle',
                'value' => 'For immediate assistance, reach us directly:',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 19:41:22'
            ],
            [
                'id' => 70,
                'group' => 'footer',
                'key' => 'contact.address',
                'value' => 'Jariptaka, Nagpur, Maharashtra',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 21:22:48'
            ],
            [
                'id' => 71,
                'group' => 'footer',
                'key' => 'contact.phone',
                'value' => '+91 000000 00000',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 21:22:47'
            ],
            [
                'id' => 72,
                'group' => 'footer',
                'key' => 'contact.email',
                'value' => 'support@medinovapharma.com',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 21:22:47'
            ],
            [
                'id' => 73,
                'group' => 'footer',
                'key' => 'contact.hours',
                'value' => 'Mon-Sun: 8 AM - 11 PM',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 21:22:48'
            ],
            [
                'id' => 74,
                'group' => 'contact',
                'key' => 'contact.map_url',
                'value' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d241317.11281381823!2d72.7104273614083!3d19.082502457710602!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7c6306644edc1%3A0x5da4ed8f8d648c69!2sMumbai%2C%20Maharashtra!5e0!3m2!1sen!2sin!4v1700000000000',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 19:41:22'
            ],
            [
                'id' => 75,
                'group' => 'contact',
                'key' => 'contact.label_email',
                'value' => 'Customer Support',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 19:41:22'
            ],
            [
                'id' => 76,
                'group' => 'contact',
                'key' => 'contact.label_phone',
                'value' => 'Phone (24/7)',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 19:41:22'
            ],
            [
                'id' => 77,
                'group' => 'contact',
                'key' => 'contact.label_address',
                'value' => 'Head Office',
                'type' => 'string',
                'created_at' => '2026-05-02 19:41:22',
                'updated_at' => '2026-05-02 19:41:22'
            ],
            [
                'id' => 78,
                'group' => 'checkout',
                'key' => 'checkout.breadcrumb_title',
                'value' => 'Complete Your Order',
                'type' => 'string',
                'created_at' => '2026-05-02 21:11:58',
                'updated_at' => '2026-05-02 21:11:58'
            ],
            [
                'id' => 79,
                'group' => 'checkout',
                'key' => 'checkout.breadcrumb_image',
                'value' => 'checkout/9tWxCjQXfnu6fXf2qo8wxhAK4WcULpSVr9cBgyhj.jpg',
                'type' => 'string',
                'created_at' => '2026-05-02 21:11:58',
                'updated_at' => '2026-05-02 21:11:58'
            ],
            [
                'id' => 80,
                'group' => 'footer',
                'key' => 'footer.about',
                'value' => '',
                'type' => 'string',
                'created_at' => '2026-05-02 21:22:47',
                'updated_at' => '2026-05-02 21:22:47'
            ],
            [
                'id' => 81,
                'group' => 'footer',
                'key' => 'footer.copyright',
                'value' => '© 2026 MediNova Pharma. All rights reserved.',
                'type' => 'string',
                'created_at' => '2026-05-02 21:22:47',
                'updated_at' => '2026-05-02 21:22:47'
            ],
            [
                'id' => 82,
                'group' => 'footer',
                'key' => 'footer.shop_links',
                'value' => '[{"label":"All Products","url":"\\/products"},{"label":"New Arrivals","url":"\\/products?sort=newest"},{"label":"Best Sellers","url":"\\/products?sort=popular"},{"label":"On Sale","url":"\\/products?on_sale=1"},{"label":"Upload Prescription","url":"\\/prescriptions"}]',
                'type' => 'string',
                'created_at' => '2026-05-02 21:22:48',
                'updated_at' => '2026-05-02 21:22:48'
            ],
            [
                'id' => 83,
                'group' => 'footer',
                'key' => 'footer.support_links',
                'value' => '[{"label":"Contact Us","url":"\\/contact"},{"label":"About Us","url":"\\/page\\/about-us"},{"label":"Shipping","url":"\\/page\\/shipping-policy"},{"label":"Returns","url":"\\/page\\/return-refund-policy"},{"label":"Track Order","url":"\\/orders"}]',
                'type' => 'string',
                'created_at' => '2026-05-02 21:22:48',
                'updated_at' => '2026-05-02 21:22:48'
            ],
            [
                'id' => 84,
                'group' => 'footer',
                'key' => 'footer.legal_links',
                'value' => '[{"label":"Privacy Policy","url":"\\/page\\/privacy-policy"},{"label":"Terms & Conditions","url":"\\/page\\/terms-conditions"},{"label":"Shipping Policy","url":"\\/page\\/shipping-policy"},{"label":"Refund Policy","url":"\\/page\\/return-refund-policy"}]',
                'type' => 'string',
                'created_at' => '2026-05-02 21:22:48',
                'updated_at' => '2026-05-02 21:22:48'
            ]
        ];

        foreach ($rows as $row) {
            DB::table('settings')->insert($row);
        }
    }
}
