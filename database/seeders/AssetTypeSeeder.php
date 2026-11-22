<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AssetType;

class AssetTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data Tipe Aset yang akan dibuat
        $assetTypes = [
            ['name' => 'Access Point', 'code' => 'AP'],
            ['name' => 'CCTV', 'code' => 'CCTV'],
            ['name' => 'Fingerprint Scanner', 'code' => 'FP'],
            ['name' => 'Laptop', 'code' => 'LTP'],
            ['name' => 'Monitor', 'code' => 'MON'],
            ['name' => 'Mini PC', 'code' => 'MPC'],
            ['name' => 'NVR (Network Video Recorder)', 'code' => 'NVR'],
            ['name' => 'PC (Desktop)', 'code' => 'PC'],
            ['name' => 'Printer', 'code' => 'PRN'],
            ['name' => 'Proyektor', 'code' => 'PJT'],
            ['name' => 'Router', 'code' => 'RTR'],
            ['name' => 'Server', 'code' => 'SRV'],
            ['name' => 'Switch Network', 'code' => 'SWN'],
            ['name' => 'POE Switch', 'code' => 'SWP'],
            ['name' => 'VGA Switch', 'code' => 'SWV'],
            ['name' => 'Tablet', 'code' => 'TBL'],
            ['name' => 'UPS (Uninterruptible Power Supply)', 'code' => 'UPS'],
            ['name' => 'Webcam', 'code' => 'WBC'],
            ['name' => 'Radio Rig', 'code' => 'RRG'],
            ['name' => 'Dashcam', 'code' => 'DCM'],
            ['name' => 'Radio HT', 'code' => 'HT'],
            ['name' => 'DVR (Digital Video Recorder)', 'code' => 'DVR'],
            ['name' => 'Scanner', 'code' => 'SCN'],
            ['name' => 'Transmitter & Receiver', 'code' => 'TXRX'],
            ['name' => 'Firewall', 'code' => 'FW'],
            ['name' => 'HDD Dock', 'code' => 'HDDK'],
            ['name' => 'NAS (Network Attached Storage)', 'code' => 'NAS'],
        ];

        // Loop dan buat record menggunakan firstOrCreate untuk mencegah duplikasi
        foreach ($assetTypes as $type) {
            AssetType::firstOrCreate(
                ['code' => $type['code']], // Kunci untuk mencari
                ['name' => $type['name']]  // Nilai tambahan untuk diisi saat create
            );
        }

        $this->command->info('Asset Type seeder executed successfully!');
    }
}
