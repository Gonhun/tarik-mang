<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartemenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data Departemen yang akan dibuat
        $departments = [
            ['code' => 'DIR', 'name' => 'DIREKTUR'],
            ['code' => 'BSDV', 'name' => 'BUSDEV'],
            ['code' => 'CPM', 'name' => 'CIVIL PROJECT MANAGEMENT'],
            ['code' => 'DC', 'name' => 'DATA CENTER'],
            ['code' => 'ENG', 'name' => 'ENGINEERING'],
            ['code' => 'FINANCE', 'name' => 'FINANCE'],
            ['code' => 'GS', 'name' => 'GENERAL SERVICE'],
            ['code' => 'IT', 'name' => 'INFORMATION TECHNOLOGY'],
            ['code' => 'IC', 'name' => 'INTELECTUAL CAPITAL'],
            ['code' => 'IA', 'name' => 'INTERNAL AUDIT'],
            ['code' => 'LGL', 'name' => 'LEGAL'],
            ['code' => 'OPS', 'name' => 'OPERATION'],
            ['code' => 'OD', 'name' => 'ORGANIZATION DEVELOPMENT'],
            ['code' => 'PLANT', 'name' => 'PLANT'],
            ['code' => 'PROD', 'name' => 'PRODUKSI'],
            ['code' => 'SHE', 'name' => 'SHE'],
            ['code' => 'SM', 'name' => 'SUPPLY MANAGEMENT'],
            ['code' => 'SI', 'name' => 'SINERGI INSTITUTE'],
            ['code' => 'LOG', 'name' => 'LOGISTIK'],
            ['code' => 'ATA', 'name' => 'ACCOUNTING AND TAX'],
        ];
        
        // Loop dan buat record menggunakan updateOrCreate untuk mencegah duplikasi
        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']], // Kunci untuk mencari
                ['name' => $department['name']]  // Nilai untuk di-update atau di-create
            );
           
        }

        $this->command->info('Department seeder executed successfully!');
        
    }
}
