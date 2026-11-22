<?php

namespace App\Livewire\Isr;

use Illuminate\Support\Facades\Http;
use Livewire\Component;

class Main extends Component
{
    public $page=10;
    public $data=[];
    public $detailInv=[];
    public $invoice_number=null;
    public $isModalOpen=false;
    public string $errorMessage = '';
    public bool $loaded = false;

    // Data dimuat setelah halaman tampil (wire:init="Getbilling") agar halaman tidak menunggu API.
    public function mount()
    {
    }

    /** Panggil API ISR; kembalikan 'data' atau null bila gagal (pesan di $errorMessage). */
    protected function callIsr(string $urlKey, array $payload): ?array
    {
        $this->errorMessage = '';
        $url = config('services.ainya.' . $urlKey);
        if (!$url) {
            $this->errorMessage = 'Alamat API ISR belum diatur di .env.';
            return null;
        }
        try {
            $response = Http::connectTimeout(10)->timeout(120)->post($url, $payload);
            if (!$response->successful()) {
                $this->errorMessage = 'Server ISR membalas status ' . $response->status() . '.';
                return null;
            }
            $data = $response->json('data');
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('API ISR gagal: ' . $e->getMessage());
            $this->errorMessage = 'Server ISR tidak dapat dihubungi. Coba lagi beberapa saat.';
            return null;
        }
    }
    public function render()
    {
        return view('livewire.isr.main');
    }
    public function updatedPage()
    {
        $this->Getbilling();
    }
    public function Getbilling()
    {
        ini_set('max_execution_time', 300);
        $this->data = $this->callIsr('isr_billing_url', [
            'page' => $this->page,
            'invoice_number' => $this->invoice_number,
        ]) ?? [];
        $this->loaded = true;

    }
    public function detail($invoice_number)
    {
        $this->invoice_number = $invoice_number;
        $this->isModalOpen = true;
        ini_set('max_execution_time', 300);
        $this->detailInv = $this->callIsr('isr_invoice_url', [
            'invoice_number' => $invoice_number,
        ]) ?? [];

    }
    public function closeModal()
    {
        $this->detailInv=[];
        $this->invoice_number = null;
        $this->isModalOpen = false;
    }
}
