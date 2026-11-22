<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use App\Models\User;
class OrganizationalChart extends Component
{
    public $chartData = [];


    /**
     * Method ini dijalankan saat komponen pertama kali dimuat.
     */
    public function mount()
    {
        $this->buildChartData();
        
    }

    /**
     * Mengambil data user dan membangun struktur pohon untuk chart.
     */
    public function buildChartData()
    {
        // Ambil semua pengguna dari database dengan kolom yang relevan
        $users = User::with('position:id,name','department')->get(['id', 'name','department_id', 'manager_id', 'position_id','pp_path']);

        // Kelompokkan pengguna berdasarkan ID atasan mereka
        $usersByManager = $users->groupBy('manager_id');

        // Buat satu node root untuk seluruh organisasi
        // Ini adalah node paling atas yang tidak mewakili user manapun.
        $this->chartData = [
            'id' => 'root-company',
            'data' => [
                
                'name' => 'Struktur Perusahaan',
                'position' => 'PT. Bina Sarana Sukses',
                'department' => 'PT. Bina Sarana Sukses',
                'imageURL' => asset('assets/img/LogoBSS.png') // Placeholder logo
            ],
            'options' => [
                'nodeBGColor' => '#FF0000FF',
                'nodeBGColorHover' => '#495057',
            ],
            // Panggil fungsi rekursif untuk membangun anak-anak dari root
            // (yaitu, semua pengguna yang tidak memiliki atasan / manager_id = null)
            'children' => $this->buildTree(null, $usersByManager)
        ];
    }

    /**
     * Fungsi rekursif untuk membangun data pohon.
     */
    private function buildTree($managerId, $usersByManager)
    {
        $tree = [];
        $subordinates = $usersByManager->get($managerId, []);

        foreach ($subordinates as $user) {
            // Buat node untuk pengguna saat ini
          
            $node = [
                'id' => 'user-' . $user->id, // ID unik untuk setiap node
                'data' => [
                    'id' => $user->id, 
                    'name' => $user->name,
                    'position' => $user->position->name ?? 'Jabatan Belum Ditentukan',
                    'department' => $user->department->code,
                    'imageURL' =>  empty($user->pp_path) ? asset('assets/img/avatars/1.png') : asset('storage/' . $user->pp_path),
                ],
                'options' => [
                    'nodeBGColor' => $this->getRandomBrightColor(),
                    'nodeBGColorHover' => $this->getRandomNodeBGColorHover(),
                    
                ],
                
                // Panggil kembali fungsi ini secara rekursif untuk mencari bawahan dari pengguna ini
                'children' => $this->buildTree($user->id, $usersByManager)
            ];
            $tree[] = $node;
        }
        return $tree;
    }
    /**
     * Menghasilkan warna cerah acak dalam format HEX.
     * Warna cerah dihasilkan dengan menjaga nilai RGB cukup tinggi.
     */
    private function getRandomBrightColor()
    {
        // Nilai minimum untuk komponen warna agar tetap cerah (180-255)
        $min = 180;
        $max = 255;
        $r = random_int($min, $max);
        $g = random_int($min, $max);
        $b = random_int($min, $max);
        return sprintf("#%02X%02X%02X", $r, $g, $b);
    }
    /**
     * Menghasilkan warna hover acak yang sedikit lebih gelap dari warna nodeBGColor.
     * Jika diberikan warna HEX, akan mengurangi kecerahan untuk efek hover.
     * @param string|null $baseColor
     * @return string
     */
    private function getRandomNodeBGColorHover($baseColor = null)
    {
        // Jika tidak ada warna dasar, gunakan warna cerah acak
        if (!$baseColor) {
            $baseColor = $this->getRandomBrightColor();
        }

        // Ubah HEX ke RGB
        $baseColor = ltrim($baseColor, '#');
        $r = hexdec(substr($baseColor, 0, 2));
        $g = hexdec(substr($baseColor, 2, 2));
        $b = hexdec(substr($baseColor, 4, 2));

        // Kurangi kecerahan (misal: -40, minimum 0)
        $factor = 40;
        $r = max(0, $r - $factor);
        $g = max(0, $g - $factor);
        $b = max(0, $b - $factor);

        return sprintf("#%02X%02X%02X", $r, $g, $b);
    }
    // public function mount()
    // {
    //     $this->prepareChartData();
    // }

    // public function prepareChartData()
    // {
    //     $users = User::with('department:id,name,code','position:id,name')->get(['id', 'name', 'department_id', 'manager_id', 'position_id']);

    //     $dataForChart = [];
    //     // Tambahkan header kolom untuk Google Charts
    //     $dataForChart[] = [['v' => 'Nama (ID)', 'f' => 'Nama Jabatan'], 'ID Atasan', 'Tooltip'];

    //     foreach ($users as $user) {
    //         // Format setiap baris sesuai kebutuhan Google Charts
    //         // [ 'v': ID unik, 'f': Teks yang akan ditampilkan di kotak ]
    //         // [ ID Atasan ]
    //         // [ Tooltip saat di-hover ]
    //         $dataForChart[] = [
    //             ['v' => (string)$user->id, 'f' => "<div><strong>{$user->name}</strong></div><div style='color:red; font-style:bold;'>{$user->department->name}</div><div style='color:crimson; font-style:italic;'>{$user->position->name}</div>"],
    //             (string)$user->manager_id,
    //             $user->position->name ?? 'Jabatan'
    //         ];
    //     }

    //     $this->chartData = $dataForChart;
    // }
    public function render()
    {
        return view('livewire.profile.organizational-chart');
    }
}
