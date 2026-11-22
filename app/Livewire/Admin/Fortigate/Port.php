<?php

namespace App\Livewire\Admin\Fortigate;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Port extends Component
{
    public $isLoading = true;
    public $messageBody = '';
    public $errorMessage = '';
    public $groups = [];

    public function mount()
    {
        // Data API dimuat setelah halaman tampil (wire:init) agar halaman tidak menunggu API.
    }
    function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes) / log(1024)) : 0;
        $pow = min($pow, count($units) - 1);
    
        $bytes /= pow(1024, $pow);
    
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    public function fetchGroupData()
    {
        $this->isLoading = true;
        $this->errorMessage = '';
        $token = config('services.fortigate.token');
        $webhookUrl = rtrim(config('services.fortigate.url'), '/') . '/monitor/system/interface';

        try {
            // Menggunakan HTTP Client bawaan Laravel untuk mengambil data
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->withOptions(['verify' => config('services.fortigate.verify_ssl')])->connectTimeout(5)->timeout(15)->get($webhookUrl);

            if ($response->successful()) {
                $data = $response->json();
              
                
                if (isset($data['results']) && is_array($data['results'])) {
                    foreach ($data['results'] as $key => $port) {
                        if (str_starts_with($key, 'port') && isset($port['name']) && isset($port['alias'])) {
                            $this->groups[] = [
                                'name' => $port['name'],
                                'alias' => $port['alias'],
                                'tx' => $this->formatBytes($port['tx_bytes']),
                                'rx' => $this->formatBytes($port['rx_bytes']),
                            ];
                        }
                    }
                }
               
            
                    // Tampilkan hasil
                    
               
            } else {
                // Jika request tidak berhasil (misal, error 404 atau 500 dari server API)
                $this->errorMessage = 'Gagal mengambil data dari server. Status: ' . $response->status();
                Log::error('Gagal mengambil data dari webhook WA', [
                    'status' => $response->status(), 
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            // Jika terjadi error koneksi atau timeout
            $this->errorMessage = 'Terjadi kesalahan saat mencoba terhubung ke server API.';
            Log::critical('Exception saat mengambil data dari webhook WA: ' . $e->getMessage());
        }

        $this->isLoading = false;
    }
    public function render()
    {
        return view('livewire.admin.fortigate.port');
    }
}
