<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->delete();

        $rows = [
            [
                'id' => 27,
                'parent_id' => null,
                'name' => 'Fitness & Nutrition',
                'slug' => 'fitness-nutrition',
                'description' => 'Sports nutrition and fitness supplements.',
                'image' => 'categories/01KQKWFTFY630QBPQJDEFXJTH6.png',
                'icon' => '🏃',
                'is_active' => 1,
                'show_in_menu' => 1,
                'sort_order' => 7,
                'meta_title' => null,
                'meta_description' => null,
                'created_at' => '2026-04-17 15:58:00',
                'updated_at' => '2026-05-02 08:23:53'
            ],
            [
                'id' => 28,
                'parent_id' => null,
                'name' => 'Sexual Wellness',
                'slug' => 'sexual-wellness',
                'description' => 'Products for sexual health and wellness.',
                'image' => 'categories/01KQKX0A7CG51JCJ5KF7PQQAP0.jpg',
                'icon' => '❤️',
                'is_active' => 1,
                'show_in_menu' => 1,
                'sort_order' => 8,
                'meta_title' => null,
                'meta_description' => null,
                'created_at' => '2026-04-17 15:58:00',
                'updated_at' => '2026-05-02 08:31:12'
            ],
            [
                'id' => 29,
                'parent_id' => null,
                'name' => 'Abortion Pills ',
                'slug' => 'abortion-pills',
                'description' => null,
                'image' => 'categories/01KQCAPRXBR27T83KMKSBKWV1A.png',
                'icon' => '⚕️',
                'is_active' => 1,
                'show_in_menu' => 1,
                'sort_order' => 0,
                'meta_title' => null,
                'meta_description' => null,
                'created_at' => '2026-04-29 09:54:31',
                'updated_at' => '2026-05-02 08:23:53'
            ],
            [
                'id' => 30,
                'parent_id' => null,
                'name' => 'Pain Killers',
                'slug' => 'pain-killers',
                'description' => null,
                'image' => 'categories/01KQJJ0GJJFGGHK6Z43W5CC3F6.jpg',
                'icon' => '🩹',
                'is_active' => 1,
                'show_in_menu' => 1,
                'sort_order' => 0,
                'meta_title' => null,
                'meta_description' => null,
                'created_at' => '2026-05-01 19:59:50',
                'updated_at' => '2026-05-02 08:23:53'
            ],
            [
                'id' => 31,
                'parent_id' => null,
                'name' => 'Weight Loss',
                'slug' => 'weight-loss',
                'description' => null,
                'image' => 'categories/01KQJJGDE4RTBFMA7RM4ZHQRXE.jpg',
                'icon' => '⚖️',
                'is_active' => 1,
                'show_in_menu' => 1,
                'sort_order' => 0,
                'meta_title' => null,
                'meta_description' => null,
                'created_at' => '2026-05-01 20:08:30',
                'updated_at' => '2026-05-02 08:23:53'
            ],
            [
                'id' => 32,
                'parent_id' => null,
                'name' => 'Anti Cancer ',
                'slug' => 'anti-cancer',
                'description' => null,
                'image' => 'categories/01KQJKBQ9ZZRTFM9MA0DGVMYXC.png',
                'icon' => '🎗️',
                'is_active' => 1,
                'show_in_menu' => 1,
                'sort_order' => 0,
                'meta_title' => null,
                'meta_description' => null,
                'created_at' => '2026-05-01 20:23:25',
                'updated_at' => '2026-05-02 08:23:53'
            ],
            [
                'id' => 33,
                'parent_id' => 30,
                'name' => 'Anti biotic',
                'slug' => 'anti-biotic',
                'description' => null,
                'image' => 'categories/01KQKYS1YV8VHHTH6S0HTTGWQP.jpg',
                'icon' => null,
                'is_active' => 1,
                'show_in_menu' => 1,
                'sort_order' => 0,
                'meta_title' => null,
                'meta_description' => null,
                'created_at' => '2026-05-02 09:02:11',
                'updated_at' => '2026-05-02 09:02:11'
            ]
        ];

        foreach ($rows as $row) {
            DB::table('categories')->insert($row);
        }
    }
}
