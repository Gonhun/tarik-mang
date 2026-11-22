<?php

namespace Database\Seeders;

use App\Models\AssetBrand;
use App\Models\AssetType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MerekAsset extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['AP', 'Unifi'],
            ['AP', 'Tenda'],
            ['CCTV', 'HIKVISION'],
            ['FP', 'Solution'],
            ['LTP', 'ASUS'],
            ['LTP', 'ACER'],
            ['LTP', 'LENOVO'],
            ['LTP', 'MSI'],
            ['LTP', 'DELL'],
            ['MON', 'LG'],
            ['MON', 'AOC'],
            ['MON', 'HP'],
            ['MPC', 'INTEL'],
            ['MPC', 'BEELINK'],
            ['PC', 'GIGABYTE'],
            ['PC', 'ECS'],
            ['PC', 'ASROCK'],
            ['PRN', 'Brother'],
            ['PRN', 'EPSON'],
            ['PRN', 'HiTi'],
            ['RTR', 'MikroTik'],
            ['RTR', 'Cisco'],
            ['SRV', 'Synology'],
            ['SRV', 'SUPERMICRO'],
            ['SWV', 'KVM Switch'],
            ['TBL', 'SAMSUNG'],
            ['TBL', 'Xiaomi'],
            ['UPS', 'APC'],
            ['UPS', 'ICA'],
            ['WBC', 'Logitech'],
            ['RTR', 'StarLink'],
            ['RRG', 'ICOM'],
            ['DCM', 'DASHCAM'],
            ['MON', 'MONITOR LG'], // Akan dinormalisasi ke 'LG'
            ['CCTV', 'HiLook'],
            ['MPC', 'BMAX'],
            ['HT', 'Motorola'],
            ['PC', 'PC DESKTOP'], // Akan dinormalisasi ke 'DESKTOP'
            ['HT', 'KENWOOD'],
            ['TXRX', 'FNIRSI'],
            ['AP', 'Ruijie'],
            ['FW', 'FORTINET'],
            ['LTP', 'APPLE'],
            ['NVR', 'DAHUA'],
            ['PC', 'SIMBADDA'],
            ['PC', 'H61M-K'],
            ['PRN', 'KYOCERA'],
            ['SRV', 'RAINER'],
            ['TXRX', 'HAGIBIS'],
            ['UPS', 'WEARNES'],
            ['HDDK', 'Seagate'],
            ['AP', 'TP-Link'],
            ['MON', 'AKARI'],
            ['SWN', 'LINKSYS'],
            ['UPS', 'Xmeye'],
            ['PC', 'ALSEYE'],
            ['DVR', 'Xemeye'],
            ['PRN', 'ZEBRA'],
            ['SCN', 'HONEYWELL'],
            ['HT', 'ALHUA'], // Mungkin typo → normalisasi jadi 'DAHUA'?
        ];

        // Normalisasi merek khusus (opsional)
        $normalisasi = [
            'MONITOR LG' => 'LG',
            'PC DESKTOP' => 'DESKTOP',
            'ALHUA' => 'DAHUA',
        ];

        foreach ($data as [$code, $name]) {
            $type = AssetType::where('code', $code)->first();

            if (!$type) {
                echo "Kode tipe tidak ditemukan: $code\n";
                continue;
            }

            $normalizedName = $normalisasi[$name] ?? $name;

            AssetBrand::updateOrCreate(
                [
                    'asset_type_id' => $type->id,
                    'name' => strtoupper($normalizedName),
                ],
              
            );
        }

        echo "Seeder asset_merks selesai.\n";
    }
}
