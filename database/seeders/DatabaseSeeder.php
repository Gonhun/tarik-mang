<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            AssetTypeSeeder::class,
            DepartemenSeeder::class,
            JobsiteSeeder::class,
            EntitiesSeeder::class,
            PositionSeeder::class,
            RolesAndPermissionsSeeder::class,
            CostCenterSeeder::class,
            PositionLevelsTableSeeder::class,
            AssetTypeSeeder::class,
            SoftwareCategorySeeder::class,
            SoftwareManufacturerSeeder::class,
        ]);
        
       
    }
}
