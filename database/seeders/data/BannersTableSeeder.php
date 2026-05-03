<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannersTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('banners')->delete();

        $rows = [
            [
                'id' => 1,
                'title' => 'Your Health, Our Priority',
                'subtitle' => 'Shop 50,000+ medicines, vitamins & health products with home delivery. Use code FIRST10 for 10% off your first order.',
                'image' => 'https://placehold.co/1400x500/FFF0F3/FA4E67?text=MediNova+Hero+Banner+1',
                'mobile_image' => null,
                'button_text' => 'Shop Now',
                'button_url' => '/products',
                'badge_text' => '✓ Genuine Medicines',
                'position' => 'hero',
                'is_active' => 1,
                'sort_order' => 1,
                'starts_at' => null,
                'expires_at' => null,
                'created_at' => '2026-04-17 15:58:00',
                'updated_at' => '2026-04-30 19:50:08'
            ],
            [
                'id' => 2,
                'title' => 'Trusted Medicines, Delivered Fast',
                'subtitle' => 'Get medicines at your doorstep within 4 hours. 100% authentic products from licensed pharmacies.',
                'image' => 'https://placehold.co/1400x500/FFF0F3/FF647B?text=MediNova+Hero+Banner+2',
                'mobile_image' => null,
                'button_text' => 'Explore Products',
                'button_url' => '/products',
                'badge_text' => '🚀 Express Delivery',
                'position' => 'hero',
                'is_active' => 1,
                'sort_order' => 2,
                'starts_at' => null,
                'expires_at' => null,
                'created_at' => '2026-04-17 15:58:00',
                'updated_at' => '2026-04-17 15:58:00'
            ],
            [
                'id' => 3,
                'title' => 'Upload Prescription & Order',
                'subtitle' => 'Have a prescription? Upload it easily and get your medicines delivered hassle-free at the best prices.',
                'image' => 'https://placehold.co/1400x500/FFF0F3/FF879A?text=MediNova+Hero+Banner+3',
                'mobile_image' => null,
                'button_text' => 'Upload Prescription',
                'button_url' => '/prescriptions',
                'badge_text' => '🔒 100% Secure',
                'position' => 'hero',
                'is_active' => 1,
                'sort_order' => 3,
                'starts_at' => null,
                'expires_at' => null,
                'created_at' => '2026-04-17 15:58:00',
                'updated_at' => '2026-04-17 15:58:00'
            ],
            [
                'id' => 4,
                'title' => 'Vitamins & Supplements Sale',
                'subtitle' => 'Up to 40% off on all vitamins.',
                'image' => 'https://placehold.co/600x250/FFF0F3/FA4E67?text=Vitamins+Sale',
                'mobile_image' => null,
                'button_text' => 'Shop Vitamins',
                'button_url' => '/products?category=vitamins-supplements',
                'badge_text' => 'UP TO 40% OFF',
                'position' => 'promo',
                'is_active' => 1,
                'sort_order' => 1,
                'starts_at' => null,
                'expires_at' => null,
                'created_at' => '2026-04-17 15:58:00',
                'updated_at' => '2026-04-17 15:58:00'
            ],
            [
                'id' => 5,
                'title' => 'Diabetes Care Bundle',
                'subtitle' => 'Glucometer + 50 strips at special price.',
                'image' => 'https://placehold.co/600x250/FFF0F3/FF647B?text=Diabetes+Bundle',
                'mobile_image' => null,
                'button_text' => 'View Bundle',
                'button_url' => '/products?category=diabetes-care',
                'badge_text' => 'SAVE ₹600',
                'position' => 'promo',
                'is_active' => 1,
                'sort_order' => 2,
                'starts_at' => null,
                'expires_at' => null,
                'created_at' => '2026-04-17 15:58:00',
                'updated_at' => '2026-04-17 15:58:00'
            ],
            [
                'id' => 6,
                'title' => 'New Arrivals in Skincare',
                'subtitle' => 'Explore latest dermatologist-approved products.',
                'image' => 'https://placehold.co/600x250/FFF0F3/FF879A?text=Skincare+New',
                'mobile_image' => null,
                'button_text' => 'Discover Now',
                'button_url' => '/products?category=skin-care',
                'badge_text' => 'NEW ARRIVALS',
                'position' => 'promo',
                'is_active' => 1,
                'sort_order' => 3,
                'starts_at' => null,
                'expires_at' => null,
                'created_at' => '2026-04-17 15:58:00',
                'updated_at' => '2026-04-17 15:58:00'
            ]
        ];

        foreach ($rows as $row) {
            DB::table('banners')->insert($row);
        }
    }
}
