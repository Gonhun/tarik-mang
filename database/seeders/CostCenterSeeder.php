<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CostCenter;
use App\Models\Jobsite;
use App\Models\Department;

class CostCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua jobsite dan departemen yang ada, lalu petakan kodenya ke ID
        // Ini jauh lebih efisien daripada melakukan query di dalam loop
        $jobsiteMap = Jobsite::pluck('id', 'code');
        $departmentMap = Department::pluck('id', 'code');

        // Data Cost Center yang akan dibuat
        $costCenters = [
            ['number' => 'A100000009', 'jobsite_code' => 'JKT', 'department_code' => 'IT'],
            ['number' => 'A200000007', 'jobsite_code' => 'SMD', 'department_code' => 'IT'],
            ['number' => 'A370010110', 'jobsite_code' => 'PLR', 'department_code' => 'IT'],
            ['number' => 'A300010400', 'jobsite_code' => 'MSJ', 'department_code' => 'IT'],
            ['number' => 'A310010400', 'jobsite_code' => 'TDM', 'department_code' => 'IT'],
            ['number' => 'A340010400', 'jobsite_code' => 'AGM', 'department_code' => 'IT'],
            ['number' => 'A350010400', 'jobsite_code' => 'PMSS', 'department_code' => 'IT'],
            ['number' => 'A360010400', 'jobsite_code' => 'MAS', 'department_code' => 'IT'],
            ['number' => 'A420010400', 'jobsite_code' => 'TAJ', 'department_code' => 'IT'],
            ['number' => 'A430010400', 'jobsite_code' => 'MME', 'department_code' => 'IT'],
            ['number' => 'A440010400', 'jobsite_code' => 'MBL', 'department_code' => 'IT'],
            ['number' => 'A510010400', 'jobsite_code' => 'BYN', 'department_code' => 'IT'],
            ['number' => 'A520010400', 'jobsite_code' => 'CDI', 'department_code' => 'IT'],
            ['number' => 'A530010130', 'jobsite_code' => 'MHU', 'department_code' => 'IT'],
            ['number' => 'A540010130', 'jobsite_code' => 'MBLH', 'department_code' => 'IT'],
        ];

        // Loop dan buat record menggunakan updateOrCreate untuk mencegah duplikasi
        foreach ($costCenters as $cc) {
            CostCenter::updateOrCreate(
                ['cost_center_number' => $cc['number']], // Kunci untuk mencari
                [
                    'jobsite_id' => $jobsiteMap[$cc['jobsite_code']] ?? null,
                    'department_id' => $departmentMap[$cc['department_code']] ?? null,
                ]
            );
        }

        $this->command->info('Cost Center seeder executed successfully!');
    }
}