<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua departemen yang ada dan petakan kodenya ke ID
        // Ini lebih efisien daripada melakukan query di dalam loop
        $departmentMap = Department::pluck('id', 'code');

        // Daftar Jabatan yang akan dibuat
        // Format: ['department_code', 'name']
        $positionsData = [
            // --- Accounting & Tax (ATA) ---
            ['department_code' => 'ATA', 'name' => 'Staff Accounting'],
            ['department_code' => 'ATA', 'name' => 'Staff Tax'],
            ['department_code' => 'ATA', 'name' => 'Talenta Kepala Bagian Accounting & Tax'],
            ['department_code' => 'ATA', 'name' => 'Kepala Seksi Tax'],
            ['department_code' => 'ATA', 'name' => 'Kepala Seksi Accounting & Tax'],

            // --- Busdev (BSDV) ---
            ['department_code' => 'BSDV', 'name' => 'Staff Busdev'],
            ['department_code' => 'BSDV', 'name' => 'Kepala Seksi Project Management & Commersial'],
            ['department_code' => 'BSDV', 'name' => 'Talenta Kepala Bagian Technical Busdev'],
            ['department_code' => 'BSDV', 'name' => 'Kepala Bagian Busdev'],
            ['department_code' => 'BSDV', 'name' => 'Kepala Seksi Busdev'],
            ['department_code' => 'BSDV', 'name' => 'Direktorat (DC-FAT-IA-LGL)'],

            // --- Civil Project Management (CPM) ---
            ['department_code' => 'CPM', 'name' => 'Staff Civil'],
            ['department_code' => 'CPM', 'name' => 'Staff Civil Engineering'],
            ['department_code' => 'CPM', 'name' => 'Civil Enggineering'],
            ['department_code' => 'CPM', 'name' => 'Talenta Kepala Seksi Building Engineering'],
            
            // --- Data Center (DC) ---
            ['department_code' => 'DC', 'name' => 'Staff Cost Control'],
            ['department_code' => 'DC', 'name' => 'Staff Data Administrasi'],
            ['department_code' => 'DC', 'name' => 'Talenta Kepala Bagian Data Center'],
            ['department_code' => 'DC', 'name' => 'Talenta Pengawas Data Administrasi'],
            ['department_code' => 'DC', 'name' => 'Talenta Kepala Seksi Cost Control'],

            // --- Engineering (ENG) ---
            ['department_code' => 'ENG', 'name' => 'Talenta Kepala Bagian Engineering'],
            ['department_code' => 'ENG', 'name' => 'Pit control'],
            ['department_code' => 'ENG', 'name' => 'Kepala Seksi Trainer'],
            ['department_code' => 'ENG', 'name' => 'Expert Geologi'],
            ['department_code' => 'ENG', 'name' => 'Jr. Drill & Blasting Engineering'],
            ['department_code' => 'ENG', 'name' => 'Staff Dewatering'],
            ['department_code' => 'ENG', 'name' => 'Staff MIG'],
            ['department_code' => 'ENG', 'name' => 'Production Control'],
            ['department_code' => 'ENG', 'name' => 'Assisten Surveyor'],
            ['department_code' => 'ENG', 'name' => 'Talenta Production Control'],
            ['department_code' => 'ENG', 'name' => 'Mine Plan Engineering'],
            ['department_code' => 'ENG', 'name' => 'Surveyor'],
            ['department_code' => 'ENG', 'name' => 'Talenta Assisten Surveyor'],
            ['department_code' => 'ENG', 'name' => 'Talenta Kepala Seksi Engineering'],
            ['department_code' => 'ENG', 'name' => 'Staff Geotech'],
            ['department_code' => 'ENG', 'name' => 'Mine Infra & Geotech'],
            ['department_code' => 'ENG', 'name' => 'Kepala Seksi Engineering'],
            ['department_code' => 'ENG', 'name' => 'Talenta Surveyor'],
            ['department_code' => 'ENG', 'name' => 'Talenta Kepala Seksi Mine & Geotech'],
            ['department_code' => 'ENG', 'name' => 'Staff Mine Plan'],
            ['department_code' => 'ENG', 'name' => 'Geotechnical Engineering'],
            ['department_code' => 'ENG', 'name' => 'Talenta Kepala Departemen Engineering'],
            ['department_code' => 'ENG', 'name' => 'Kepala Seksi Dispatch Monitoring & Control'],
            ['department_code' => 'ENG', 'name' => 'Talenta Mine Plan Engineering'],
            ['department_code' => 'ENG', 'name' => 'Talenta Kepala Bagian Long Term'],
            ['department_code' => 'ENG', 'name' => 'Kepala Bagian Engineering'],
            ['department_code' => 'ENG', 'name' => 'Kepala Seksi Drainage & Dewatering'],
            ['department_code' => 'ENG', 'name' => 'Pengawas Mine Plan Engineering'],

            // --- Finance (FINANCE) ---
            ['department_code' => 'FINANCE', 'name' => 'Staff Account Payable'],
            ['department_code' => 'FINANCE', 'name' => 'Staff Finance'],
            ['department_code' => 'FINANCE', 'name' => 'Staff Treasury'],
            ['department_code' => 'FINANCE', 'name' => 'Kepala Bagian Finance'],
            ['department_code' => 'FINANCE', 'name' => 'Kepala Seksi Finance'],
            ['department_code' => 'FINANCE', 'name' => 'Talenta Kepala Departemen Finance'],

            // --- General Service (GS) ---
            ['department_code' => 'GS', 'name' => 'Kepala Bagian GS'],
            ['department_code' => 'GS', 'name' => 'Chief Security'],
            ['department_code' => 'GS', 'name' => 'GS Eksternal'],
            ['department_code' => 'GS', 'name' => 'GS Officer'],
            ['department_code' => 'GS', 'name' => 'Staff GS'],
            ['department_code' => 'GS', 'name' => 'Pengawas GS'],
            ['department_code' => 'GS', 'name' => 'Talenta Kepala Bagian GS'],
            ['department_code' => 'GS', 'name' => 'Talenta Staff GS'],
            ['department_code' => 'GS', 'name' => 'Talenta Kepala Seksi GS'],

            // --- Internal Audit (IA) ---
            ['department_code' => 'IA', 'name' => 'Kepala Seksi IA'],
            ['department_code' => 'IA', 'name' => 'Staff IA'],
            ['department_code' => 'IA', 'name' => 'Talenta People, System & Quality Assurance'],
            ['department_code' => 'IA', 'name' => 'Talenta Kepala Seksi Audit Internal Management Sys'],
            ['department_code' => 'IA', 'name' => 'Kepala Bagian IA'],

            // --- Intelectual Capital (IC) ---
            ['department_code' => 'IC', 'name' => 'Kepala Bagian IC'],
            ['department_code' => 'IC', 'name' => 'Kepala Departemen IC'],
            ['department_code' => 'IC', 'name' => 'Kepala Seksi GS'],
            ['department_code' => 'IC', 'name' => 'Kepala Seksi IC'],
            ['department_code' => 'IC', 'name' => 'Staff IC'],
            ['department_code' => 'IC', 'name' => 'Staff PSDM'],
            ['department_code' => 'IC', 'name' => 'Talenta Kepala Bagian IC'],
            ['department_code' => 'IC', 'name' => 'Talenta Kepala Bagian Innovation & Development'],
            ['department_code' => 'IC', 'name' => 'Talenta Kepala Bagian Personalia dan C&B'],
            ['department_code' => 'IC', 'name' => 'Talenta Kepala Seksi IC'],
            ['department_code' => 'IC', 'name' => 'Talenta Staff IC'],

            // --- IT ---
            ['department_code' => 'IT', 'name' => 'Staff IT'],
            ['department_code' => 'IT', 'name' => 'Staff Basis & Implementor'],
            ['department_code' => 'IT', 'name' => 'Programmer Officer'],
            ['department_code' => 'IT', 'name' => 'Staff Hardware & Application'],
            ['department_code' => 'IT', 'name' => 'Kepala Seksi System Development'],
            ['department_code' => 'IT', 'name' => 'IT Application Developer Plant'],
            ['department_code' => 'IT', 'name' => 'Kepala Seksi IT'],
            ['department_code' => 'IT', 'name' => 'Staff Hardware & Implementor'],
            ['department_code' => 'IT', 'name' => 'Hardware & Application Officer'],
            
            // --- Legal (LGL) ---
            ['department_code' => 'LGL', 'name' => 'Staff Legal'],
            ['department_code' => 'LGL', 'name' => 'Kepala Seksi Legal'],
            ['department_code' => 'LGL', 'name' => 'Koordinator Legal'],

            // --- Logistik (LOG) ---
            ['department_code' => 'LOG', 'name' => 'Kepala Seksi Warehouse'],
            ['department_code' => 'LOG', 'name' => 'Kepala Bagian Logistik'],
            ['department_code' => 'LOG', 'name' => 'Kepala Seksi FOGC'],
            ['department_code' => 'LOG', 'name' => 'Pengawas Logistik'],
            ['department_code' => 'LOG', 'name' => 'People & System Dev Section Head HO'],
            ['department_code' => 'LOG', 'name' => 'Kepala Seksi Inventory'],
            ['department_code' => 'LOG', 'name' => 'Staff Inventory'],
            ['department_code' => 'LOG', 'name' => 'Staff Warehouse'],
            ['department_code' => 'LOG', 'name' => 'Staff FOGC'],
            ['department_code' => 'LOG', 'name' => 'Talenta Staff Warehouse'],
            ['department_code' => 'LOG', 'name' => 'Staff Catalogue'],
            ['department_code' => 'LOG', 'name' => 'Talenta Staff FOGC'],
            ['department_code' => 'LOG', 'name' => 'Talenta Staff Inventory'],
            ['department_code' => 'LOG', 'name' => 'Staff People & System Development Logistic'],
            ['department_code' => 'LOG', 'name' => 'Staff Data Prosesing'],

            // --- Organization Development (OD) ---
            ['department_code' => 'OD', 'name' => 'Staff OD'],
            ['department_code' => 'OD', 'name' => 'Staff IA'],
            ['department_code' => 'OD', 'name' => 'Talenta Kepala Seksi IA'],
            ['department_code' => 'OD', 'name' => 'Talenta Kepala Seksi OD'],
            ['department_code' => 'OD', 'name' => 'Talenta Kepala Seksi Logistik'],
            ['department_code' => 'OD', 'name' => 'Talenta Kepala Department IA'],
            ['department_code' => 'OD', 'name' => 'Kepala Bagian OD'],
            
            // --- Operation (OPS) ---
            ['department_code' => 'OPS', 'name' => 'Project Manager'],
            ['department_code' => 'OPS', 'name' => 'Deputy PM'],
            ['department_code' => 'OPS', 'name' => 'Staff Operation'],
            ['department_code' => 'OPS', 'name' => 'Manager Cluster'],
            ['department_code' => 'OPS', 'name' => 'Talenta Project Manager'],
            ['department_code' => 'OPS', 'name' => 'Sekretariat BOM'],
            ['department_code' => 'OPS', 'name' => 'Operation Manager'],
            ['department_code' => 'OPS', 'name' => 'Direktorat (IT-OD-IC-GS-COMDEV)'],
            ['department_code' => 'OPS', 'name' => 'Direktorat (ENG-PROD-BSDV-SHE-LOG-SM)'],
            ['department_code' => 'OPS', 'name' => 'Direktorat (PROD-OPS)'],
            ['department_code' => 'OPS', 'name' => 'Direktur Utama'],
            
            // --- Plant (PLANT) ---
            ['department_code' => 'PLANT', 'name' => 'Pengawas Comex'],
            ['department_code' => 'PLANT', 'name' => 'Planner'],
            ['department_code' => 'PLANT', 'name' => 'Pengawas Track A2B'],
            ['department_code' => 'PLANT', 'name' => 'Kepala Seksi Plant'],
            ['department_code' => 'PLANT', 'name' => 'Pengawas Wheel DT'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Pengawas Track A2B'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Kepala Seksi Plant'],
            ['department_code' => 'PLANT', 'name' => 'Kepala Seksi Planner'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Pengawas Tyre'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Pengawas Support'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Pengawas Welder'],
            ['department_code' => 'PLANT', 'name' => 'Pengawas Plant'],
            ['department_code' => 'PLANT', 'name' => 'Pengawas Tyre'],
            ['department_code' => 'PLANT', 'name' => 'Kepala Bagian Plant'],
            ['department_code' => 'PLANT', 'name' => 'Pengawas Welder'],
            ['department_code' => 'PLANT', 'name' => 'Pengawas Wheel A2B'],
            ['department_code' => 'PLANT', 'name' => 'Pengawas Support'],
            ['department_code' => 'PLANT', 'name' => 'Kepala Seksi Comex'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Kepala Departemen Plant'],
            ['department_code' => 'PLANT', 'name' => 'Pengawas RM A2B'],
            ['department_code' => 'PLANT', 'name' => 'Pengawas RM DT'],
            ['department_code' => 'PLANT', 'name' => 'Pengawas Mekanik Support'],
            ['department_code' => 'PLANT', 'name' => 'Pengawas Elektrik'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Pengawas Elektrik'],
            ['department_code' => 'PLANT', 'name' => 'Kepala Seksi RM Mining'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Kepala Seksi Planner'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Kepala Seksi Wheel A2B'],
            ['department_code' => 'PLANT', 'name' => 'Plant Engineer'],
            ['department_code' => 'PLANT', 'name' => 'Trainer'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Tyre Analys'],
            ['department_code' => 'PLANT', 'name' => 'Tyre Analys'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Plant Engineer'],
            ['department_code' => 'PLANT', 'name' => 'Instruktur Plant'],
            ['department_code' => 'PLANT', 'name' => 'Specialist Comex'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Trainer Plant'],
            ['department_code' => 'PLANT', 'name' => 'Advisor'],
            ['department_code' => 'PLANT', 'name' => 'Tehnikal Advisor'],
            ['department_code' => 'PLANT', 'name' => 'Strategic Plant'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Trainer Junior'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Kepala Bagian System Development'],
            ['department_code' => 'PLANT', 'name' => 'Supervisor Planner'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Planner'],
            ['department_code' => 'PLANT', 'name' => 'Junior Planner'],
            ['department_code' => 'PLANT', 'name' => 'FGDP Staff Plant'],
            ['department_code' => 'PLANT', 'name' => 'Talenta Staff Planner'],
            ['department_code' => 'PLANT', 'name' => 'People Development Plant'],
            ['department_code' => 'PLANT', 'name' => 'Plant Equipment Mgt & Adm Officer'],
            ['department_code' => 'PLANT', 'name' => 'Staff Hybrid'],
            ['department_code' => 'PLANT', 'name' => 'Plant Scheduller'],
            ['department_code' => 'PLANT', 'name' => 'Leader Big Wheel'],
            ['department_code' => 'PLANT', 'name' => 'Cluster Plan Sumatera'],
            ['department_code' => 'PLANT', 'name' => 'Planner HO'],

            // --- Produksi (PROD) ---
            ['department_code' => 'PROD', 'name' => 'Pengawas Produksi Mining'],
            ['department_code' => 'PROD', 'name' => 'Talenta Pengawas Produksi Mining'],
            ['department_code' => 'PROD', 'name' => 'Kepala Bagian Produksi Hauling'],
            ['department_code' => 'PROD', 'name' => 'Pengawas Drill & Blasting Engineering'],
            ['department_code' => 'PROD', 'name' => 'Pengawas Produksi Hauling'],
            ['department_code' => 'PROD', 'name' => 'Drill & Blasting Engineering'],
            ['department_code' => 'PROD', 'name' => 'Talenta Kepala Seksi Produksi Mining'],
            ['department_code' => 'PROD', 'name' => 'Kepala Seksi Produksi Mining'],
            ['department_code' => 'PROD', 'name' => 'Talenta Pengawas Produksi Hauling'],
            ['department_code' => 'PROD', 'name' => 'Kepala Seksi Drill & Blasting Engineering'],
            ['department_code' => 'PROD', 'name' => 'Talenta Kepala Bagian Produksi'],
            ['department_code' => 'PROD', 'name' => 'Kepala Bagian Produksi Mining'],
            ['department_code' => 'PROD', 'name' => 'Kepala Seksi Drill Blast Tech'],
            ['department_code' => 'PROD', 'name' => 'Kepala Seksi Operation Technical Analisis'],
            ['department_code' => 'PROD', 'name' => 'Talenta Pengawas Produksi'],
            ['department_code' => 'PROD', 'name' => 'Talenta Kepala Seksi Trainer'],
            ['department_code' => 'PROD', 'name' => 'Talenta Trainer'],
            ['department_code' => 'PROD', 'name' => 'Staff Trainer'],
            ['department_code' => 'PROD', 'name' => 'Operation Control'],
            ['department_code' => 'PROD', 'name' => 'Kepala Seksi Road & Maintenance'],
            ['department_code' => 'PROD', 'name' => 'Staff Operation'],
            ['department_code' => 'PROD', 'name' => 'Talenta Operation Control'],
            ['department_code' => 'PROD', 'name' => 'Operation Technical Officer'],
            ['department_code' => 'PROD', 'name' => 'Talenta Leading Hand Produksi'],

            // --- SHE ---
            ['department_code' => 'SHE', 'name' => 'Talenta Kepala Seksi SHE'],
            ['department_code' => 'SHE', 'name' => 'Dokter'],
            ['department_code' => 'SHE', 'name' => 'SHE Officer'],
            ['department_code' => 'SHE', 'name' => 'Paramedis'],
            ['department_code' => 'SHE', 'name' => 'Kepala Seksi SHE'],
            ['department_code' => 'SHE', 'name' => 'Staff Enviro'],
            ['department_code' => 'SHE', 'name' => 'SHE System, Training and Reporting'],
            ['department_code' => 'SHE', 'name' => 'Safety Officer'],
            ['department_code' => 'SHE', 'name' => 'Staff System & Training'],
            ['department_code' => 'SHE', 'name' => 'Talenta Staff Paramedic'],
            ['department_code' => 'SHE', 'name' => 'Safety Patroll Senior'],
            ['department_code' => 'SHE', 'name' => 'Talenta SHE Operation'],
            ['department_code' => 'SHE', 'name' => 'Safety Patrol Junior'],
            ['department_code' => 'SHE', 'name' => 'Talenta SHE Officer'],

            // --- Supply Management (SM) ---
            ['department_code' => 'SM', 'name' => 'Talenta Kepala Seksi Purchasing'],
            ['department_code' => 'SM', 'name' => 'Staff Purchasing'],
            ['department_code' => 'SM', 'name' => 'Staff Procurement'],
            ['department_code' => 'SM', 'name' => 'Staff Asset Procurement'],
            ['department_code' => 'SM', 'name' => 'Purchasing Officer'],
            ['department_code' => 'SM', 'name' => 'Talenta Kepala Departemen SM'],

            // --- Lain-lain ---
            ['department_code' => 'PMO', 'name' => 'Kepala Bagian'],
            ['department_code' => 'PRC', 'name' => 'Kepala Bagian'],
            ['department_code' => 'QAQC', 'name' => 'Kepala Bagian'],
            ['department_code' => 'QHSE', 'name' => 'Kepala Bagian'],
            ['department_code' => 'TAX', 'name' => 'Kepala Bagian'],
            ['department_code' => 'PGA', 'name' => 'Kepala Bagian'],
            ['department_code' => 'TRN', 'name' => 'Kepala Bagian'],
            ['department_code' => 'SI', 'name' => 'Program Development Training Center'],
        ];

        // Gunakan Set untuk memastikan kombinasi unik
        $uniquePositions = [];
        foreach ($positionsData as $position) {
            $key = $position['department_code'] . '::' . $position['name'];
            if (!isset($uniquePositions[$key])) {
                $uniquePositions[$key] = $position;
            }
        }

        foreach ($uniquePositions as $position) {
            $departmentId = $departmentMap[$position['department_code']] ?? null;

            if($position['department_code'] === 'IT'){
                Position::updateOrCreate(
                    [
                        'name' => 'Administrator IT',
                        'department_id' => $departmentId
                    ],
                    
                );
            }
            // Buat kode jabatan yang unik
            Position::updateOrCreate(
                [
                    'name' => $position['name'],
                    'department_id' => $departmentId
                ],
                
            );
        }
        
        $this->command->info('Position seeder executed successfully!');
    }
}
