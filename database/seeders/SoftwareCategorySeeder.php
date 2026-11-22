<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SoftwareCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('software_category')->insert([
            ['name' => 'Operating System', 'enable' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Antivirus',         'enable' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Office Suite',      'enable' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Design & Editing',  'enable' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
