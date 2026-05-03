<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('brands')->delete();

        $rows = [
            [
                'id' => 1,
                'name' => 'Sun Pharma',
                'slug' => 'sun-pharma',
                'description' => 'India\'s largest pharmaceutical company.',
                'logo' => null,
                'website' => null,
                'is_active' => 1,
                'sort_order' => 1,
                'created_at' => '2026-04-17 15:57:59',
                'updated_at' => '2026-04-17 15:57:59'
            ],
            [
                'id' => 2,
                'name' => 'Cipla',
                'slug' => 'cipla',
                'description' => 'Affordable medicines across therapeutic areas.',
                'logo' => null,
                'website' => null,
                'is_active' => 1,
                'sort_order' => 2,
                'created_at' => '2026-04-17 15:57:59',
                'updated_at' => '2026-04-17 15:57:59'
            ],
            [
                'id' => 3,
                'name' => 'Dr. Reddy\'s',
                'slug' => 'dr-reddys',
                'description' => 'Global pharmaceutical company from India.',
                'logo' => null,
                'website' => null,
                'is_active' => 1,
                'sort_order' => 3,
                'created_at' => '2026-04-17 15:57:59',
                'updated_at' => '2026-04-17 15:57:59'
            ],
            [
                'id' => 4,
                'name' => 'Abbott India',
                'slug' => 'abbott-india',
                'description' => 'Healthcare innovations for a healthier world.',
                'logo' => null,
                'website' => null,
                'is_active' => 1,
                'sort_order' => 4,
                'created_at' => '2026-04-17 15:57:59',
                'updated_at' => '2026-04-17 15:57:59'
            ],
            [
                'id' => 5,
                'name' => 'Mankind Pharma',
                'slug' => 'mankind-pharma',
                'description' => 'Quality healthcare at affordable prices.',
                'logo' => null,
                'website' => null,
                'is_active' => 1,
                'sort_order' => 5,
                'created_at' => '2026-04-17 15:57:59',
                'updated_at' => '2026-04-17 15:57:59'
            ],
            [
                'id' => 6,
                'name' => 'Himalaya',
                'slug' => 'himalaya',
                'description' => 'Natural wellness products and herbal remedies.',
                'logo' => null,
                'website' => null,
                'is_active' => 1,
                'sort_order' => 6,
                'created_at' => '2026-04-17 15:57:59',
                'updated_at' => '2026-04-17 15:57:59'
            ],
            [
                'id' => 7,
                'name' => 'Dabur',
                'slug' => 'dabur',
                'description' => 'Trusted Ayurvedic & natural health products.',
                'logo' => null,
                'website' => null,
                'is_active' => 1,
                'sort_order' => 7,
                'created_at' => '2026-04-17 15:57:59',
                'updated_at' => '2026-04-17 15:57:59'
            ],
            [
                'id' => 8,
                'name' => 'Pfizer',
                'slug' => 'pfizer',
                'description' => 'Global leader in biopharmaceuticals.',
                'logo' => null,
                'website' => null,
                'is_active' => 1,
                'sort_order' => 8,
                'created_at' => '2026-04-17 15:57:59',
                'updated_at' => '2026-04-17 15:57:59'
            ]
        ];

        foreach ($rows as $row) {
            DB::table('brands')->insert($row);
        }
    }
}
