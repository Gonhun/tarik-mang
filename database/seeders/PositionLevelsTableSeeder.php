<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PositionLevelsTableSeeder extends Seeder
{
    public function run()
    {
        $levels = [
            'Direksi',
            'Wakil Kepala Proyek',
            'Think Tank',
            'Kepala Proyek',
            'Senior Expert - 2',
            'Kepala Departemen',
            'Kepala Divisi',
            'Kepala Bagian',
            'Senior Expert - 1',
            'Kepala Seksi',
            'Expert - 2',
            'Pengawas (Foreman)',
            'Expert - 1',
            'Junior Expert - 2',
            'Senior Staff',
            'Junior Expert - 1',
            'Staff',
            'Junior Officer',
            'Direktur',
        ];

        foreach ($levels as $level) {
            DB::table('position_levels')->insert([
                'name' => $level,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
