<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CouponsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('coupons')->delete();

        $rows = [
            [
                'id' => 1,
                'code' => 'FIRST10',
                'description' => '10% off on your first order (max ₹200 discount)',
                'type' => 'percentage',
                'value' => '10.00',
                'min_order_amount' => '299.00',
                'max_discount_amount' => '200.00',
                'usage_limit' => 1000,
                'usage_limit_per_user' => 1,
                'used_count' => 0,
                'is_active' => 1,
                'starts_at' => '2026-04-17 15:58:00',
                'expires_at' => '2027-04-17 15:58:00',
                'created_at' => '2026-04-17 15:58:00',
                'updated_at' => '2026-04-17 15:58:00'
            ],
            [
                'id' => 2,
                'code' => 'SAVE50',
                'description' => 'Flat ₹50 off on orders above ₹499',
                'type' => 'fixed',
                'value' => '50.00',
                'min_order_amount' => '499.00',
                'max_discount_amount' => '50.00',
                'usage_limit' => 5000,
                'usage_limit_per_user' => 3,
                'used_count' => 0,
                'is_active' => 1,
                'starts_at' => '2026-04-17 15:58:00',
                'expires_at' => '2026-10-17 15:58:00',
                'created_at' => '2026-04-17 15:58:00',
                'updated_at' => '2026-04-17 15:58:00'
            ],
            [
                'id' => 3,
                'code' => 'HEALTH20',
                'description' => '20% off on vitamins and supplements (max ₹300)',
                'type' => 'percentage',
                'value' => '20.00',
                'min_order_amount' => '599.00',
                'max_discount_amount' => '300.00',
                'usage_limit' => 2000,
                'usage_limit_per_user' => 2,
                'used_count' => 0,
                'is_active' => 1,
                'starts_at' => '2026-04-17 15:58:00',
                'expires_at' => '2026-07-17 15:58:00',
                'created_at' => '2026-04-17 15:58:00',
                'updated_at' => '2026-04-17 15:58:00'
            ],
            [
                'id' => 4,
                'code' => 'MEDIFREE',
                'description' => 'Free shipping on orders above ₹199 (₹50 shipping discount)',
                'type' => 'fixed',
                'value' => '50.00',
                'min_order_amount' => '199.00',
                'max_discount_amount' => '50.00',
                'usage_limit' => 99999,
                'usage_limit_per_user' => 10,
                'used_count' => 0,
                'is_active' => 1,
                'starts_at' => '2026-04-17 15:58:48',
                'expires_at' => '2027-04-17 15:58:48',
                'created_at' => '2026-04-17 15:58:48',
                'updated_at' => '2026-04-17 15:58:48'
            ]
        ];

        foreach ($rows as $row) {
            DB::table('coupons')->insert($row);
        }
    }
}
