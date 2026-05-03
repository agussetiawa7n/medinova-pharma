<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->delete();

        $rows = [
            [
                'id' => 1,
                'name' => 'MediNova Admin',
                'email' => 'admin@medinovapharma.com',
                'phone' => null,
                'avatar' => null,
                'date_of_birth' => null,
                'gender' => null,
                'is_active' => 1,
                'email_verified_at' => null,
                'password' => '$2y$12$GyPfNVw3yk2ScYf0VH1wfOS1zOgUrmNGha5e7aXm6ssU.P/FSKy3C',
                'remember_token' => null,
                'created_at' => '2026-04-17 15:04:08',
                'updated_at' => '2026-04-24 11:21:18'
            ]
        ];

        foreach ($rows as $row) {
            DB::table('users')->insert($row);
        }
    }
}
