<?php

namespace Database\Seeders;

use App\Models\Jobsite;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobsiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data Jobsite yang akan dibuat
        $jobsites = [
            ['code' => 'CMX-PLR', 'name' => 'COMEX PALARAN'],
            ['code' => 'CMX-MSJ', 'name' => 'COMEX MAHAKAM SUMBER JAYA'],
            ['code' => 'CMX-MAS', 'name' => 'COMEX MUARA ALAM SEJAHTERA'],
            ['code' => 'CMX-AGM', 'name' => 'COMEX ANTANG GUNUNG MERATUS'],
            ['code' => 'CORPORATE', 'name' => 'CORPORATE'],
            ['code' => 'AGM', 'name' => 'Antang Gunung Meratus'],
            ['code' => 'TAJ', 'name' => 'Tanjung Alam Jaya'],
            ['code' => 'MME', 'name' => 'Manambang Muara Enim'],
            ['code' => 'MAS', 'name' => 'Muara Alam Sejahtera'],
            ['code' => 'MSJ', 'name' => 'Mahakam Sumber Jaya'],
            ['code' => 'MOBALL', 'name' => 'SITE - MOBILE'],
            ['code' => 'BAYAN', 'name' => 'BAYAN'],
            ['code' => 'MBLH', 'name' => 'MANOOR BULATN LESTARI SITE HAULING'],
            ['code' => 'MBLM', 'name' => 'MANOOR BULATN LESTARI SITE MINING'],
            ['code' => 'MHU', 'name' => 'MULTI HARAPAN UTAMA'],
            ['code' => 'PLR', 'name' => 'WORKSHOP PALARAN'],
            ['code' => 'PKM', 'name' => 'WORKSHOP PASAR KAMIS'],
            ['code' => 'PMSS', 'name' => 'PRIMA MULIA SARANA SEJAHTERA'],
            ['code' => 'SBB', 'name' => 'SANTAN BATUBARA'],
            ['code' => 'SMD', 'name' => 'SAMARINDA'],
            ['code' => 'TDM', 'name' => 'TAMBANG DAMAI'],
            ['code' => 'JKT', 'name' => 'Jakarta'],
            ['code' => 'CDI', 'name' => 'CITRA DAYAK INDAH'],
            ['code' => 'BJM', 'name' => 'BANJARMASIN'],
            ['code' => 'BRAM', 'name' => 'BATURONA ALAM SEMESTA'],
            ['code' => 'BSSR', 'name' => 'BARAMULTI SUKSESARANA'],
            ['code' => 'NEW4', 'name' => 'NEW PROJECTS'],
            ['code' => 'SAS', 'name' => 'SAS'],
            ['code' => 'MBL', 'name' => 'MANOOR BULATN LESTARI'],
            ['code' => 'BYN', 'name' => 'Indoneisa Prima'],
            ['code' => 'ADS', 'name' => 'ASWANA DHANAKIRTI SINERGI'],
        ];

        // Loop dan buat record menggunakan updateOrCreate untuk mencegah duplikasi
        foreach ($jobsites as $jobsite) {
            Jobsite::updateOrCreate(
                ['code' => $jobsite['code']], // Kunci untuk mencari
                ['name' => $jobsite['name']]  // Nilai untuk di-update atau di-create
            );
        }

        $this->command->info('Jobsite seeder executed successfully!');
    }
}
