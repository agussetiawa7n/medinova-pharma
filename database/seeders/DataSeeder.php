<?php

use Illuminate\Database\Seeder;

class DataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->call(UsersTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(CategoriesTableSeeder::class);
        $this->call(BrandsTableSeeder::class);
        $this->call(ProductsTableSeeder::class);
        $this->call(BannersTableSeeder::class);
        $this->call(PagesTableSeeder::class);
        $this->call(CouponsTableSeeder::class);
        $this->call(WalletsTableSeeder::class);
        $this->call(OrdersTableSeeder::class);
        $this->call(OrderItemsTableSeeder::class);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
