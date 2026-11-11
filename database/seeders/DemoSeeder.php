<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolePermissionSeeder::class, MasterDataSeeder::class]);

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@wheeldeal.test'],
            [
                'name' => 'WheelDeal Admin',
                'phone' => '+919876543210',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles(['Super Admin']);

        $editor = User::updateOrCreate(
            ['email' => 'editor@wheeldeal.test'],
            [
                'name' => 'Catalog Editor',
                'phone' => '+919876543211',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $editor->syncRoles(['Editor']);

        $this->call(CatalogDemoSeeder::class);
        $this->call(RealVehicleMediaSeeder::class);
        $this->call(RequestDemoSeeder::class);
    }
}
