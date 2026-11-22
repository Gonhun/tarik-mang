<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Replace;

class Whatsapp extends Component
{
    public $groups = [];
    public $errorMessage = '';

    public $isSendMessageModalOpen = false;
    public $targetGroupId = '';
    public $targetGroupName = '';
    public $messageBody = '';
    /**
     * Properti untuk mengontrol state loading.
     * @var bool
     */
    public $isLoading = true;

    /**
     * Method ini dijalankan saat komponen pertama kali dimuat.
     * Tugasnya adalah memicu pengambilan data.
     */
    public function mount()
    {
        $this->fetchGroupData();
    }

    /**
     * Method untuk mengambil data dari URL webhook.
     */
    public function fetchGroupData()
    {
        $this->isLoading = true;
        $this->errorMessage = '';
        $webhookUrl = 'https://ainya.binasaranasukses.co.id/webhook/e49c33da-5c58-486d-9b7c-5fd35223a3ec';

        try {
            // Menggunakan HTTP Client bawaan Laravel untuk mengambil data
            $response = Http::withoutVerifying()->timeout(60)->get($webhookUrl);

            if ($response->successful()) {
                $data = $response->json();
                // Pastikan data yang diterima adalah array sebelum disimpan
                if (is_array($data)) {
                    $this->groups = $data;
                } else {
                    $this->errorMessage = 'Format data yang diterima dari API tidak valid.';
                }
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
    public function openSendMessageModal($groupId, $groupName)
    {
        $this->targetGroupId = $groupId ;//str_replace('@g.us', '', $groupId);
        $this->targetGroupName = $groupName;
        $this->messageBody = ''; // Kosongkan isi pesan setiap kali modal dibuka
        $this->resetValidation();
        $this->isSendMessageModalOpen = true;
    }

    /**
     * Method BARU untuk menutup modal kirim pesan.
     */
    public function closeSendMessageModal()
    {
        $this->isSendMessageModalOpen = false;
    }

    /**
     * Method BARU untuk mengirim pesan dari modal.
     */
    public function sendMessage()
    {
        $this->validate([
            'messageBody' => 'required|string|min:1',
        ], [
            'messageBody.required' => 'Isi pesan tidak boleh kosong.'
        ]);
        $this->messageBody .= "\n\nDikirim oleh " . Auth::user()->name;
       
        // Panggil helper global untuk mengirim pesan
        // Anda harus sudah memiliki helper ini dari diskusi kita sebelumnya.
        $isSuccess = sendMessageWithWhatsApp($this->targetGroupId, $this->messageBody);

        if ($isSuccess) {
            $this->dispatch('swal:success', [
                'title' => 'Berhasil!',
                'text' => "Pesan berhasil dikirim ke grup '{$this->targetGroupName}'",
            ]);
        } else {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => "Gagal mengirim pesan ke grup '{$this->targetGroupName}'. Cek log untuk detail."]);
        }
        
        $this->closeSendMessageModal();
    }
    public function render()
    {
        
        return view('livewire.admin.whatsapp');
    }
}
