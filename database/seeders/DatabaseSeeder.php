<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            DataSeeder::class,
        ]);

        // Assign super_admin role if not already assigned
        $admin = User::where('email', 'admin@medinovapharma.com')->first();
        if ($admin && !$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        // Generate Filament Shield permissions for all resources
        \Artisan::call('shield:generate', ['--all' => true, '--option' => 'all']);

        // Re-assign all permissions to super_admin after generation
        $role = \Spatie\Permission\Models\Role::findByName('super_admin', 'web');
        $role->syncPermissions(\Spatie\Permission\Models\Permission::all());
    }
}
