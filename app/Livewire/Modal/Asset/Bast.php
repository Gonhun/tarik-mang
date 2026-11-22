<?php

namespace App\Livewire\Modal\Asset;

use App\Models\Asset;
use App\Models\AssetRequestItem;
use App\Models\AssetTransaction;
use App\Models\User;
use App\Support\TransactionNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * BAST (serah terima) satu aset berstatus Free dari user yang login ke penerima.
 * Pemberi, tanggal, dan aset ditentukan server (di tampilan juga terkunci).
 */
class Bast extends Component
{
    public $isModalOpen = false;

    #[Locked]
    public $giver_user_id;
    #[Locked]
    public $transaction_date;
    #[Locked]
    public $assetId;

    public $receiver_user_id;
    public $receiverSearch = '';
    public $notes;

    /** Untuk checkbox di tampilan (selalu berisi aset yang dikunci). */
    public $selected_assets = [];

    public $availableAssets = [];

    protected $listeners = ['openBastModal' => 'loadInitialData'];

    public function mount()
    {
        $this->availableAssets = collect();
    }

    protected function canHandover(): bool
    {
        $user = Auth::user();

        return $user->isItStaff() || $user->can('Master Asset');
    }

    public function loadInitialData($initialData = [])
    {
        $this->resetForm();

        if (!$this->canHandover()) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Anda tidak berhak membuat BAST.']);
            return;
        }

        $asset = Asset::with(['assetSeries:id,name', 'assetRequestItem.assetRequest:id,requester_user_id'])
            ->find($initialData['asset_id'] ?? null);

        if (!$asset || $asset->status !== 'Free') {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Aset tidak ditemukan atau statusnya bukan Free.']);
            return;
        }

        $this->giver_user_id = Auth::id();
        $this->transaction_date = now()->format('Y-m-d');
        $this->assetId = $asset->id;
        $this->selected_assets = [(string) $asset->id];
        $this->availableAssets = collect([$asset]);

        // Penerima default: pemohon pengajuan aset yang menghasilkan aset ini.
        $requesterId = $asset->assetRequestItem?->assetRequest?->requester_user_id;
        $this->receiver_user_id = $requesterId && $requesterId != $this->giver_user_id ? $requesterId : null;

        $this->isModalOpen = true;
        $this->dispatch('modal-open');
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function chooseReceiver($userId)
    {
        $this->receiver_user_id = (int) $userId;
        $this->receiverSearch = '';
    }

    private function resetForm()
    {
        $this->reset(['giver_user_id', 'receiver_user_id', 'receiverSearch', 'notes', 'selected_assets', 'assetId', 'transaction_date']);
        $this->availableAssets = collect();
        $this->resetValidation();
    }

    public function submitBast()
    {
        abort_unless($this->canHandover() && $this->assetId, 403);

        // Validasi hanya field yang perlu (tanpa ikut memproses seluruh state komponen).
        \Illuminate\Support\Facades\Validator::make([
            'giver_user_id' => $this->giver_user_id,
            'receiver_user_id' => $this->receiver_user_id,
            'notes' => $this->notes,
        ], [
            'receiver_user_id' => 'required|exists:users,id|different:giver_user_id',
            'notes' => 'nullable|string|max:1000',
        ], [
            'receiver_user_id.required' => 'Penerima wajib dipilih.',
            'receiver_user_id.different' => 'Penerima tidak boleh sama dengan Pemberi.',
        ])->validate();

        try {
            $result = DB::transaction(function () {
                // Kunci baris aset: cegah dua BAST untuk aset yang sama.
                $asset = Asset::whereKey($this->assetId)->lockForUpdate()->first();
                if (!$asset || $asset->status !== 'Free') {
                    return null;
                }

                $transaction = AssetTransaction::create([
                    'transaction_number' => TransactionNumber::next('BAST'),
                    'transaction_type' => 'HANDOVER',
                    'transaction_date' => $this->transaction_date,
                    'from_user_id' => $this->giver_user_id,
                    'to_user_id' => $this->receiver_user_id,
                    'from_jobsite_id' => $asset->jobsite_id,
                    'to_jobsite_id' => $asset->jobsite_id,
                    'notes' => $this->notes,
                    'status' => 'Completed', // BAST langsung selesai; penerima konfirmasi di "Aset Saya"
                    'created_by_user_id' => Auth::id(),
                ]);
                $transaction->items()->create(['asset_id' => $asset->id]);

                $asset->update([
                    'status' => 'Pre-Used',
                    'holder_user_id' => $this->receiver_user_id,
                    'asset_transaction_id' => $transaction->id,
                ]);

                return [$asset, $transaction];
            });
        } catch (\Throwable $e) {
            Log::error("BAST aset #{$this->assetId} gagal: " . $e->getMessage());
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'BAST gagal disimpan. Coba lagi atau hubungi Administrator.']);
            return;
        }

        if (!$result) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Aset sudah tidak berstatus Free (mungkin sudah diserahkan orang lain).']);
            $this->closeModal();
            $this->dispatch('refreshAssetList');
            return;
        }

        [$asset, $transaction] = $result;
        $waText = $this->notifyReceiver((int) $this->receiver_user_id);

        $this->closeModal();
        $this->dispatch('refreshAssetList');
        $this->dispatch('swal:success', [
            'title' => 'BAST dibuat',
            'text' => "{$transaction->transaction_number}: aset {$asset->asset_number} diserahkan dan menunggu konfirmasi penerima. {$waText}",
        ]);
    }

    /**
     * Kirim WhatsApp ke penerima. Kegagalan tidak membatalkan BAST yang sudah tersimpan.
     */
    protected function notifyReceiver(int $userId): string
    {
        $user = User::find($userId);
        $wa = (string) ($user->whatsapp ?? '');
        if ($wa === '') {
            return 'Penerima belum punya nomor WhatsApp, mohon kabari langsung.';
        }

        try {
            $pesan = "Halo {$user->name}, terdapat aset yang memerlukan proses BAST. Mohon segera lakukan pengecekan dan konfirmasi pada sistem AMS.\n"
                . "https://ams.binasaranasukses.co.id/\nPada menu *Asset Saya*";
            $sent = sendMessageWithWhatsApp($wa, $pesan);
        } catch (\Throwable $e) {
            Log::warning("Notifikasi WA BAST ke user #{$userId} gagal: " . $e->getMessage());
            $sent = false;
        }
        if (!$sent) {
            return 'Notifikasi WhatsApp gagal dikirim, mohon kabari penerima langsung.';
        }

        $masked = str_repeat('•', max(0, strlen($wa) - 4)) . substr($wa, -4);

        return "Notifikasi WhatsApp dikirim ke {$masked}.";
    }

    public function render()
    {
        // Penerima dicari lewat nama/NIK (bukan dropdown 6000+ user) agar modal cepat terbuka.
        $options = collect();
        if ($this->isModalOpen && strlen(trim($this->receiverSearch)) >= 2) {
            $term = '%' . trim($this->receiverSearch) . '%';
            $options = User::with('jobsite:id,code')
                ->where('id', '!=', $this->giver_user_id)
                ->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('nik', 'like', $term))
                ->orderBy('name')->limit(8)->get(['id', 'name', 'nik', 'jobsite_id']);
        }

        return view('livewire.modal.asset.bast', [
            'giver' => $this->isModalOpen && $this->giver_user_id ? User::find($this->giver_user_id, ['id', 'name', 'nik']) : null,
            'receiver' => $this->isModalOpen && $this->receiver_user_id ? User::find($this->receiver_user_id, ['id', 'name', 'nik']) : null,
            'receiverOptions' => $options,
        ]);
    }
}
