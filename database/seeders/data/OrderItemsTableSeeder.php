<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderItemsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('order_items')->delete();

        $rows = [
            [
                'id' => 1,
                'order_id' => 1,
                'product_id' => 2,
                'product_variant_id' => null,
                'product_name' => 'Combiflam Ibuprofen + Paracetamol',
                'variant_name' => null,
                'product_sku' => 'COMBI-400',
                'quantity' => 4,
                'unit_price' => '45.00',
                'total' => '180.00',
                'requires_prescription' => 0,
                'created_at' => '2026-04-17 17:47:05',
                'updated_at' => '2026-04-17 17:47:05'
            ],
            [
                'id' => 2,
                'order_id' => 2,
                'product_id' => 10,
                'product_variant_id' => null,
                'product_name' => 'Cetaphil Gentle Skin Cleanser 250ml',
                'variant_name' => null,
                'product_sku' => 'CETAPHIL-250',
                'quantity' => 1,
                'unit_price' => '375.00',
                'total' => '375.00',
                'requires_prescription' => 0,
                'created_at' => '2026-04-27 14:15:43',
                'updated_at' => '2026-04-27 14:15:43'
            ],
            [
                'id' => 6,
                'order_id' => 6,
                'product_id' => 17,
                'product_variant_id' => null,
                'product_name' => 'Pregabalin Capsules 300mg ',
                'variant_name' => null,
                'product_sku' => 'prega-001',
                'quantity' => 1,
                'unit_price' => '6.64',
                'total' => '6.64',
                'requires_prescription' => 0,
                'created_at' => '2026-05-02 19:00:59',
                'updated_at' => '2026-05-02 19:00:59'
            ],
            [
                'id' => 7,
                'order_id' => 7,
                'product_id' => 17,
                'product_variant_id' => null,
                'product_name' => 'Pregabalin Capsules 300mg ',
                'variant_name' => null,
                'product_sku' => 'prega-001',
                'quantity' => 4,
                'unit_price' => '6.64',
                'total' => '26.56',
                'requires_prescription' => 0,
                'created_at' => '2026-05-02 19:11:53',
                'updated_at' => '2026-05-02 19:11:53'
            ],
            [
                'id' => 8,
                'order_id' => 8,
                'product_id' => 3,
                'product_variant_id' => null,
                'product_name' => 'Azithromycin 500mg Tablet',
                'variant_name' => null,
                'product_sku' => 'AZITH-500',
                'quantity' => 1,
                'unit_price' => '85.00',
                'total' => '85.00',
                'requires_prescription' => 1,
                'created_at' => '2026-05-02 19:38:32',
                'updated_at' => '2026-05-02 19:38:32'
            ],
            [
                'id' => 9,
                'order_id' => 9,
                'product_id' => 2,
                'product_variant_id' => null,
                'product_name' => 'Combiflam Ibuprofen + Paracetamol',
                'variant_name' => null,
                'product_sku' => 'COMBI-400',
                'quantity' => 1,
                'unit_price' => '45.00',
                'total' => '45.00',
                'requires_prescription' => 0,
                'created_at' => '2026-05-02 21:05:29',
                'updated_at' => '2026-05-02 21:05:29'
            ],
            [
                'id' => 10,
                'order_id' => 10,
                'product_id' => 2,
                'product_variant_id' => null,
                'product_name' => 'Combiflam Ibuprofen + Paracetamol',
                'variant_name' => null,
                'product_sku' => 'COMBI-400',
                'quantity' => 2,
                'unit_price' => '45.00',
                'total' => '90.00',
                'requires_prescription' => 0,
                'created_at' => '2026-05-03 14:38:03',
                'updated_at' => '2026-05-03 14:38:03'
            ],
            [
                'id' => 11,
                'order_id' => 11,
                'product_id' => 17,
                'product_variant_id' => null,
                'product_name' => 'Pregabalin Capsules 300mg ',
                'variant_name' => null,
                'product_sku' => 'prega-001',
                'quantity' => 1,
                'unit_price' => '6.64',
                'total' => '6.64',
                'requires_prescription' => 0,
                'created_at' => '2026-05-03 16:15:19',
                'updated_at' => '2026-05-03 16:15:19'
            ],
            [
                'id' => 12,
                'order_id' => 12,
                'product_id' => 18,
                'product_variant_id' => null,
                'product_name' => 'Lyrikare 300mg',
                'variant_name' => null,
                'product_sku' => 'lyri-300',
                'quantity' => 1,
                'unit_price' => '5.11',
                'total' => '5.11',
                'requires_prescription' => 0,
                'created_at' => '2026-05-03 16:28:19',
                'updated_at' => '2026-05-03 16:28:19'
            ]
        ];

        foreach ($rows as $row) {
            DB::table('order_items')->insert($row);
        }
    }
}
