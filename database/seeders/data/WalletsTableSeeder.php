<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WalletsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('wallets')->delete();

        $rows = [
            [
                'id' => 1,
                'user_id' => 1,
                'balance' => '395.19',
                'currency' => 'MNP',
                'created_at' => '2026-04-17 17:47:30',
                'updated_at' => '2026-05-03 16:28:19'
            ]
        ];

        foreach ($rows as $row) {
            DB::table('wallets')->insert($row);
        }
    }
}
