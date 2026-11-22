<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SoftwareManufacturerSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('software_manufacturer')->insert([
            ['name' => 'Microsoft', 'enable' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Adobe',     'enable' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Autodesk',  'enable' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Oracle',    'enable' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
