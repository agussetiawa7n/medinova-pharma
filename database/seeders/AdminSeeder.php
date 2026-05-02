<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        // Create super admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@medinovapharma.com'],
            [
                'name'       => 'MediNova Admin',
                'password'   => Hash::make('Admin@1234'),
                'is_active'  => true,
            ]
        );
        $user->assignRole($superAdmin);

        // Default settings
        $defaults = [
            ['key' => 'site_name',              'value' => 'MediNova Pharma',                  'group' => 'general',  'type' => 'string'],
            ['key' => 'site_tagline',           'value' => 'Your trusted online pharmacy',     'group' => 'general',  'type' => 'string'],
            ['key' => 'contact_email',          'value' => 'support@medinovapharma.com',       'group' => 'general',  'type' => 'string'],
            ['key' => 'contact_phone',          'value' => '+91 000000 00000',                  'group' => 'general',  'type' => 'string'],
            ['key' => 'pricing.delivery_fee',          'value' => '49',                               'group' => 'pricing',  'type' => 'integer'],
            ['key' => 'pricing.free_shipping_threshold', 'value' => '500',                              'group' => 'pricing',  'type' => 'integer'],
            ['key' => 'pricing.tax_rate',                'value' => '18',                               'group' => 'pricing',  'type' => 'integer'],
            ['key' => 'currency',               'value' => 'INR',                              'group' => 'general',  'type' => 'string'],
            ['key' => 'currency_symbol',        'value' => '₹',                               'group' => 'general',  'type' => 'string'],
            ['key' => 'meta_title',             'value' => 'MediNova Pharma - Online Pharmacy','group' => 'seo',      'type' => 'string'],
            ['key' => 'meta_description',       'value' => 'Buy medicines online safely.',     'group' => 'seo',      'type' => 'string'],
            ['key' => 'payment.razorpay_enabled', 'value' => '1',                               'group' => 'payment',  'type' => 'boolean'],
            ['key' => 'payment.stripe_enabled',   'value' => '1',                               'group' => 'payment',  'type' => 'boolean'],
            ['key' => 'payment.paypal_enabled',   'value' => '1',                               'group' => 'payment',  'type' => 'boolean'],
            ['key' => 'payment.cod_enabled',      'value' => '1',                               'group' => 'payment',  'type' => 'boolean'],
            ['key' => 'payment.wallet_enabled',   'value' => '1',                               'group' => 'payment',  'type' => 'boolean'],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Super admin created: admin@medinovapharma.com / Admin@1234');
    }
}
