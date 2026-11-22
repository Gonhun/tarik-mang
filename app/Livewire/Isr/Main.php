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
    public function mount()
    {
        $this->Getbilling();
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
        $response = Http::timeout(120)->post(config('services.ainya.isr_billing_url'), [
            'page' => $this->page,
            'invoice_number' => $this->invoice_number,
        ]);
        $result = $response->json();
        $this->data = $result['data'];
        //  dd($this->data);
        
    }
    public function detail($invoice_number)
    {
        $this->invoice_number = $invoice_number;
        $this->isModalOpen = true;
        ini_set('max_execution_time', 300);
        $response = Http::timeout(120)->post(config('services.ainya.isr_invoice_url'), [
            'invoice_number' => $invoice_number,
        ]);
        $result = $response->json();
        $this->detailInv = $result['data'];
        
    }
    public function closeModal()
    {
        $this->detailInv=[];
        $this->invoice_number = null;
        $this->isModalOpen = false;
    }
}
