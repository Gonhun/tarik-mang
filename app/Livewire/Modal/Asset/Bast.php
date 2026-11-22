<?php

namespace App\Livewire\Modal\Asset;

use Livewire\Component;
use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\User;
use App\Models\AssetTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Bast extends Component
{
    public $isModalOpen = false;

    // Properti untuk form
    public $giver_user_id;
    public $receiver_user_id;
    public $transaction_date;
    public $notes;

    public $selected_assets = []; // ID aset yang dipilih untuk BAST

    // Data untuk dropdown
    public $allUsers = [];
    public $availableAssets = [];

    protected $listeners = ['openBastModal' => 'loadInitialData'];

    public function mount()
    {
        // Muat data yang tidak sering berubah di sini
        $this->allUsers = User::orderBy('name')->get(['id', 'name', 'nik']);
    }

    /**
     * Dipanggil oleh event untuk membuka modal.
     * Bisa menerima data awal, misal ID aset yang ingin diserahkan.
     */
    public function loadInitialData($initialData = [])
    {
        $this->resetForm();

        // Set 'Pemberi' secara default ke user yang login
        $this->giver_user_id = Auth::id();

        // Jika ada data aset awal yang dikirim, langsung pilih aset tersebut
        if (isset($initialData['asset_id'])) {
            // Pastikan aset tersebut ada di daftar yang tersedia
            //if ($this->availableAssets->pluck('id')->contains($initialData['asset_id'])) {
            $this->selected_assets = [(string) $initialData['asset_id']];

            // Muat semua aset yang 'Free' atau dipegang oleh 'Pemberi'
            $this->loadAvailableAssets();
            //}
        }

        $this->isModalOpen = true;
        $this->dispatch('modal-open');
    }

    /**
     * Muat daftar aset yang bisa diserahterimakan.
     */
    public function loadAvailableAssets()
    {
        $giverId = $this->giver_user_id;
        $this->availableAssets = Asset::query()
            ->where(function ($query) use ($giverId) {
                $query->where('id', '=', $this->selected_assets[0]);
                //->where('status', 'Free') // Aset yang bebas
                //->orWhere('holder_user_id', $giverId); // Atau aset yang dipegang oleh si pemberi
            })
            ->with(['assetSeries:id,name'])
            ->orderBy('asset_number')
            ->get();
        $pengajuan = AssetRequest::find($this->availableAssets->first()->asset_request_item_id);

        $this->receiver_user_id = $pengajuan ? $pengajuan->requester_user_id : null;

    }

    // Lifecycle hook untuk memuat ulang aset yang tersedia jika 'Pemberi' diubah
    public function updatedGiverUserId()
    {
        $this->loadAvailableAssets();
        $this->selected_assets = []; // Reset pilihan aset
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    private function resetForm()
    {
        $this->reset([
            'giver_user_id',
            'receiver_user_id',
            'notes',
            'selected_assets'
        ]);
        $this->transaction_date = now()->format('Y-m-d');
        $this->resetValidation();
    }

    public function submitBast()
    {
        $this->validate([
            'giver_user_id' => 'required|exists:users,id',
            'receiver_user_id' => 'required|exists:users,id|different:giver_user_id',
            'transaction_date' => 'required|date',
            'selected_assets' => 'required|array|min:1',
            'selected_assets.*' => 'exists:assets,id',
            'notes' => 'nullable|string',

        ], [
            'receiver_user_id.required' => 'Penerima wajib dipilih.',
            'receiver_user_id.different' => 'Penerima tidak boleh sama dengan Pemberi.',
            'selected_assets.required' => 'Minimal satu aset harus dipilih.',
        ]);

        DB::transaction(function () {


            // Buat record transaksi utama
            $transaction = AssetTransaction::create([
                'transaction_number' => $this->generateTransactionNumber('BAST'),
                'transaction_type' => 'HANDOVER',
                'transaction_date' => $this->transaction_date,
                'from_user_id' => $this->giver_user_id,
                'to_user_id' => $this->receiver_user_id,
                'notes' => $this->notes,
                'status' => 'Completed', // BAST langsung dianggap selesai
                'created_by_user_id' => Auth::id(),
            ]);
            $transaction->items()->createMany(
                collect($this->selected_assets)->map(fn($id) => ['asset_id' => $id])->toArray()
            );
            
            // Update aset sekaligus
            Asset::whereIn('id', $this->selected_assets)->update([
                'status' => 'Pre-Used',
                'holder_user_id' => $this->receiver_user_id,
                'asset_transaction_id'  => $transaction->id,
            ]);

            // Tambahkan item dan update status aset
            // foreach ($this->selected_assets as $assetId) {
            //     $transaction->items()->create(['asset_id' => $assetId]);
            //     Asset::find($assetId)->update([
            //         'status' => 'Pre-Used',
            //         'holder_user_id' => $this->receiver_user_id,
            //     ]);
            // }
        });
        $this->SendFUWhatApp($this->receiver_user_id);
        session()->flash('message', 'BAST berhasil dibuat dan status aset telah diperbarui.');
        $this->closeModal();
        $this->dispatch('refreshParentComponent'); // Kirim event untuk refresh halaman induk
    }
    public function SendFUWhatApp($userID)
    {
        $user = User::findOrFail($userID);
        $wa = $user->whatsapp;
        $pesan = "Halo $user->name, terdapat aset yang memerlukan proses BAST. Mohon segera lakukan pengecekan dan tindak lanjut Konfirmasi pada sistem AMS.\nhttps://ams.binasaranasukses.co.id/";
        $pesan .= "Yang ada pada menu *Asset Saya*";
        sendMessageWithWhatsApp($wa, $pesan);

        $last5 = substr($wa, -5); // ambil 5 karakter terakhir
        $hidden = str_repeat('•', strlen($wa) - 5); // ganti sisanya dengan •
        $masked = $hidden . $last5;
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => 'Pesan ada BAST Asset Terikirim ke Whastapp ' . $masked . ' 🙏',
        ]);
    }
    protected function generateTransactionNumber($typePrefix = 'TRX')
    {
        $prefix = "{$typePrefix}/" . date('Ym') . "/";
        $lastTransaction = AssetTransaction::where('transaction_number', 'LIKE', $prefix . '%')->orderBy('id', 'desc')->first();
        $nextSequence = 1;
        if ($lastTransaction) {
            $lastSequence = (int) substr($lastTransaction->transaction_number, -4);
            $nextSequence = $lastSequence + 1;
        }
        return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        return view('livewire.modal.asset.bast');
    }
}
