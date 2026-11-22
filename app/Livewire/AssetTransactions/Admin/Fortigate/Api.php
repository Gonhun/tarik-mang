<?php

namespace App\Livewire\Admin\Fortigate;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class Api extends Component
{
    public $isLoading = true;
    public $messageBody = '';
    public $errorMessage = '';
    public $groups = [];
    public $step = 0;
    public function mount()
    {
        $this->step=0;
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
    public function fetchGroupData($value)
    {
        $this->isLoading = true;
        $this->errorMessage = '';
        $token = 'w4wxhNfnj3zfxdp4Gx0gx8pb8xcwfn';
        $webhookUrl = 'https://192.168.4.1:8443/api/v2/'.$value;
        $this->groups = [];
        try {

            // Menggunakan HTTP Client bawaan Laravel untuk mengambil data
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->withoutVerifying()->timeout(60)->get($webhookUrl);

            if ($response->successful()) {
                $data = $response->json();
               
                if (isset($data['directory']) && is_array($data['directory'])) {
                    
                    foreach ($data['directory'] as $key => $port) {
                        if (isset($port['path']) && isset($port['name'])&& isset($port['action']) && isset($port['supported']) ) {
                            $this->groups[] = [
                                'path' => $port['path'],
                                'name' => $port['name'],
                                'action' => $port['action'],
                                'supported' => boolval($port['supported']),
                                'summary' => $port['summary'],
                                'request' => $port['request']
                            ];
                           
                        }
                    }
                }
               
            // dd($this->groups);
                    // Tampilkan hasil
                    
            $this->step=1;
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
        return view('livewire.admin.fortigate.api');
    }
}
