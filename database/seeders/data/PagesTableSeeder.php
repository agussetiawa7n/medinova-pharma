<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PagesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pages')->delete();

        $rows = [
            [
                'id' => 1,
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<h2>About MediNova Pharma</h2><p>MediNova Pharma is India&#039;s most trusted online pharmacy, delivering genuine medicines and healthcare products to your doorstep since 2020. We are committed to making quality healthcare accessible and affordable for everyone.</p><h3>Our Mission</h3><p>To provide every Indian access to authentic, affordable medicines with the convenience of doorstep delivery and expert pharmacist support.</p><h3>Why Choose MediNova?</h3><ul><li><p>✅ 100% Genuine Medicines — sourced directly from licensed manufacturers</p></li><li><p>✅ Licensed Pharmacy — registered with state drug regulatory authorities</p></li><li><p>✅ Expert Pharmacists — available 24/7 for medication queries</p></li><li><p>✅ Fast Delivery — 4-hour express delivery available in major cities</p></li><li><p>✅ Easy Returns — 7-day hassle-free return policy</p></li><li><p>✅ Secure Payments — 256-bit SSL encryption on all transactions</p></li></ul><h3>Our Numbers</h3><ul><li><p>50,000+ products across 500+ categories</p></li><li><p>5 million+ satisfied customers</p></li><li><p>500+ cities served across India</p></li><li><p>24/7 customer support</p></li></ul>',
                'breadcrumb_title' => null,
                'breadcrumb_image' => 'pages/breadcrumbs/01KQN4Z4AT035AB3VW9GSK0T8A.jpg',
                'is_active' => 1,
                'meta_title' => null,
                'meta_description' => 'Learn about MediNova Pharma — your trusted online pharmacy.',
                'created_at' => '2026-04-17 15:58:48',
                'updated_at' => '2026-05-02 20:09:36'
            ],
            [
                'id' => 2,
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<h2>Privacy Policy</h2><p><em>Last updated: January 2025</em></p><p>At MediNova Pharma, we are committed to protecting your privacy and personal information. This policy explains how we collect, use, and safeguard your data.</p><h3>Information We Collect</h3><ul><li><p><strong>Account Information:</strong> Name, email, phone, date of birth</p></li><li><p><strong>Order Information:</strong> Delivery address, payment method (not card numbers)</p></li><li><p><strong>Health Information:</strong> Prescriptions uploaded for medicine orders</p></li><li><p><strong>Usage Data:</strong> Pages visited, products viewed, search queries</p></li></ul><h3>How We Use Your Information</h3><ul><li><p>Processing and delivering your orders</p></li><li><p>Sending order confirmations and delivery updates</p></li><li><p>Providing personalized product recommendations</p></li><li><p>Improving our services and website</p></li><li><p>Complying with legal and regulatory requirements</p></li></ul><h3>Data Security</h3><p>We use industry-standard 256-bit SSL encryption for all data transmission. Your prescription data is stored securely and never shared without consent.</p><h3>Your Rights</h3><p>You have the right to access, update, or delete your personal data at any time. Contact us at privacy@medinovapharma.com for data requests.</p>',
                'breadcrumb_title' => null,
                'breadcrumb_image' => 'pages/breadcrumbs/01KQN69ME25VM1JBE3Q1FDF320.jpg',
                'is_active' => 1,
                'meta_title' => null,
                'meta_description' => 'MediNova Pharma Privacy Policy — how we collect and protect your data.',
                'created_at' => '2026-04-17 15:58:48',
                'updated_at' => '2026-05-02 20:32:49'
            ],
            [
                'id' => 3,
                'title' => 'Terms & Conditions',
                'slug' => 'terms-conditions',
                'content' => '<h2>Terms &amp; Conditions</h2><p><em>Last updated: January 2025</em></p><h3>1. Acceptance of Terms</h3><p>By accessing and using MediNova Pharma, you agree to be bound by these Terms and Conditions. If you do not agree, please do not use our services.</p><h3>2. Prescription Medicines</h3><p>Prescription medicines can only be purchased with a valid prescription from a registered medical practitioner. Submitting false prescriptions is illegal and will result in account termination and legal action.</p><h3>3. Product Authenticity</h3><p>All products sold on MediNova Pharma are sourced from licensed manufacturers and distributors. We guarantee the authenticity of every product sold on our platform.</p><h3>4. Pricing &amp; Payment</h3><p>Prices are inclusive of GST unless stated otherwise. We reserve the right to modify prices without prior notice. Payment must be completed before order processing.</p><h3>5. Returns &amp; Refunds</h3><p>We accept returns within 7 days of delivery for eligible products. Prescription medicines, opened products, and temperature-sensitive items are non-returnable.</p><h3>6. Limitation of Liability</h3><p>MediNova Pharma is not liable for adverse reactions to medicines used without proper medical consultation. Always consult a healthcare professional before starting any medication.</p>',
                'breadcrumb_title' => null,
                'breadcrumb_image' => 'pages/breadcrumbs/01KQN6F5ZDKNDDAZCB8P9QQ7GS.jpg',
                'is_active' => 1,
                'meta_title' => null,
                'meta_description' => 'MediNova Pharma Terms and Conditions of service.',
                'created_at' => '2026-04-17 15:58:48',
                'updated_at' => '2026-05-02 20:35:50'
            ],
            [
                'id' => 4,
                'title' => 'Shipping Policy',
                'slug' => 'shipping-policy',
                'content' => '<h2>Shipping Policy</h2><h3>Delivery Charges</h3><ul><li><p>Orders above ₹499 — FREE delivery</p></li><li><p>Orders below ₹499 — ₹50 delivery charge</p></li><li><p>Express 4-hour delivery — ₹99 (available in select cities)</p></li></ul><h3>Delivery Timeline</h3><ul><li><p><strong>Metro Cities</strong> (Delhi, Mumbai, Bangalore, Chennai, Hyderabad, Kolkata): 1-2 business days</p></li><li><p><strong>Tier 2 Cities:</strong> 2-4 business days</p></li><li><p><strong>Other Locations:</strong> 4-7 business days</p></li></ul><h3>Order Tracking</h3><p>Once your order is shipped, you will receive an SMS and email with the tracking number and courier details. Track your order anytime from your account dashboard.</p><h3>Prescription Orders</h3><p>Orders containing prescription medicines will only be dispatched after our pharmacist verifies your prescription. This typically takes 2-4 hours during business hours.</p>',
                'breadcrumb_title' => null,
                'breadcrumb_image' => 'pages/breadcrumbs/01KQN5JREFD335MB4G1V3KZN5Q.jpg',
                'is_active' => 1,
                'meta_title' => null,
                'meta_description' => 'Learn about MediNova Pharma shipping rates, delivery timelines, and areas served.',
                'created_at' => '2026-04-17 15:58:48',
                'updated_at' => '2026-05-02 20:20:19'
            ],
            [
                'id' => 5,
                'title' => 'Return & Refund Policy',
                'slug' => 'return-refund-policy',
                'content' => '<h2>Return &amp; Refund Policy</h2><h3>Eligible for Return</h3><ul><li><p>Wrong product delivered</p></li><li><p>Damaged or defective product</p></li><li><p>Expired product delivered</p></li><li><p>Sealed/unopened OTC products (within 7 days)</p></li></ul><h3>Not Eligible for Return</h3><ul><li><p>Prescription medicines (once dispensed)</p></li><li><p>Opened or used products</p></li><li><p>Temperature-sensitive products</p></li><li><p>Products without original packaging</p></li><li><p>Products returned after 7 days</p></li></ul><h3>Refund Process</h3><p>Approved refunds are processed within 5-7 business days to the original payment method. For COD orders, refunds are credited to your MediNova Wallet.</p><h3>How to Return</h3><ol><li><p>Go to My Orders in your account</p></li><li><p>Select the order and click &quot;Return/Replace&quot;</p></li><li><p>Choose return reason and upload photos if applicable</p></li><li><p>Schedule a pickup (we&#039;ll arrange free pickup)</p></li></ol>',
                'breadcrumb_title' => null,
                'breadcrumb_image' => 'pages/breadcrumbs/01KQN5S0TTT78HS9PBBMCH4R06.jpg',
                'is_active' => 1,
                'meta_title' => null,
                'meta_description' => 'MediNova Pharma return and refund policy.',
                'created_at' => '2026-04-17 15:58:48',
                'updated_at' => '2026-05-02 20:23:44'
            ]
        ];

        foreach ($rows as $row) {
            DB::table('pages')->insert($row);
        }
    }
}
