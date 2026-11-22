<?php

namespace App\Livewire\Modal\Asset;

use App\Models\Asset;
use App\Models\User;
use App\Support\AssetLoan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Modal peminjaman sementara: pinjamkan | terima pengembalian | perpanjang.
 */
class Loan extends Component
{
    public $isModalOpen = false;

    #[Locked]
    public $assetId;
    #[Locked]
    public $mode = 'lend'; // lend | return | extend

    // Pinjamkan
    public $borrowerId = null;
    public $borrowerSearch = '';
    public $dueDate;
    public $purpose = '';
    // Kembali
    public $condition = 'baik';
    public $returnNotes = '';

    protected $listeners = ['openLoanModal' => 'open'];

    protected function canManage(): bool
    {
        $u = Auth::user();

        return $u->isItStaff() || $u->can('Peminjaman');
    }

    public function open($data = [])
    {
        if (!$this->canManage()) {
            $this->dispatch('swal:error', ['title' => 'Tidak diizinkan', 'text' => 'Hanya tim IT / pengelola aset yang bisa mengelola peminjaman.']);
            return;
        }
        $asset = Asset::find($data['asset_id'] ?? null);
        $mode = in_array($data['mode'] ?? 'lend', ['lend', 'return', 'extend'], true) ? $data['mode'] : 'lend';

        if (!$asset) return;
        if ($mode === 'lend' && !in_array($asset->status, AssetLoan::LENDABLE, true)) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dipinjamkan', 'text' => "Aset {$asset->asset_number} berstatus {$asset->status}. Hanya aset Free yang bisa dipinjamkan."]);
            return;
        }
        if ($mode !== 'lend' && !AssetLoan::activeLoan($asset)) {
            $this->dispatch('swal:error', ['title' => 'Tidak sedang dipinjam', 'text' => "Aset {$asset->asset_number} tidak tercatat sebagai pinjaman."]);
            return;
        }

        $this->resetValidation();
        $this->reset(['borrowerId', 'borrowerSearch', 'purpose', 'condition', 'returnNotes']);
        $this->assetId = $asset->id;
        $this->mode = $mode;
        $this->dueDate = now()->addDays(7)->format('Y-m-d');
        if ($mode === 'extend') {
            $meta = AssetLoan::meta(AssetLoan::activeLoan($asset), $asset->id);
            $this->dueDate = \Carbon\Carbon::parse($meta['due_date'] ?? now())->addDays(7)->format('Y-m-d');
        }
        $this->isModalOpen = true;
    }

    public function close() { $this->isModalOpen = false; }

    public function chooseBorrower($id)
    {
        $this->borrowerId = (int) $id;
        $this->borrowerSearch = '';
    }

    public function submit()
    {
        abort_unless($this->canManage(), 403);
        $asset = Asset::findOrFail($this->assetId);
        $maxDate = now()->addDays(AssetLoan::MAX_DAYS)->format('Y-m-d');

        try {
            if ($this->mode === 'lend') {
                $this->validate([
                    'borrowerId' => 'required|exists:users,id',
                    'dueDate' => "required|date|after_or_equal:today|before_or_equal:{$maxDate}",
                    'purpose' => 'required|string|min:5|max:255',
                ], [
                    'borrowerId.required' => 'Pilih peminjam.',
                    'dueDate.before_or_equal' => 'Maksimal ' . AssetLoan::MAX_DAYS . ' hari. Untuk pemakaian tetap gunakan Serah terima (BAST).',
                    'purpose.required' => 'Isi keperluan peminjaman.',
                ]);
                $trx = AssetLoan::lend($asset, User::findOrFail($this->borrowerId), $this->dueDate, trim($this->purpose), Auth::user());
                $this->notifyBorrower($asset->fresh(), $trx);
                $msg = "{$asset->asset_number} dipinjamkan ({$trx->transaction_number}) sampai " . \Carbon\Carbon::parse($this->dueDate)->locale('id')->isoFormat('D MMM YYYY') . '.';
            } elseif ($this->mode === 'return') {
                $this->validate([
                    'condition' => 'required|in:baik,rusak',
                    'returnNotes' => ($this->condition === 'rusak' ? 'required|min:5' : 'nullable') . '|string|max:500',
                ], ['returnNotes.required' => 'Jelaskan kerusakannya.']);
                $trx = AssetLoan::giveBack($asset, $this->condition, trim($this->returnNotes) ?: null, Auth::user());
                $msg = "{$asset->asset_number} sudah kembali ({$trx->transaction_number}) dan berstatus " . ($this->condition === 'rusak' ? 'Repair (laporan kerusakan dibuat).' : 'Free.');
            } else {
                $this->validate(['dueDate' => "required|date|after_or_equal:today|before_or_equal:{$maxDate}"]);
                AssetLoan::extend($asset, $this->dueDate);
                $msg = "Pinjaman {$asset->asset_number} diperpanjang sampai " . \Carbon\Carbon::parse($this->dueDate)->locale('id')->isoFormat('D MMM YYYY') . '.';
            }
        } catch (\RuntimeException $e) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => $e->getMessage()]);
            return;
        }

        $this->isModalOpen = false;
        $this->dispatch('refreshAssetList');
        $this->dispatch('refreshLoans');
        $this->dispatch('swal:success', ['title' => 'Berhasil', 'text' => $msg]);
    }

    protected function notifyBorrower(Asset $asset, $trx): void
    {
        $u = User::find($asset->holder_user_id);
        if (!$u || !$u->whatsapp) return;
        $due = \Carbon\Carbon::parse(AssetLoan::meta($trx->load('items'), $asset->id)['due_date'] ?? now())->locale('id')->isoFormat('D MMMM YYYY');
        try {
            sendMessageWithWhatsApp($u->whatsapp, "Halo {$u->name},\n\nAnda meminjam aset {$asset->asset_number} ({$trx->transaction_number}). Mohon dikembalikan ke tim IT paling lambat {$due}. Terima kasih.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('WA peminjaman gagal: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $asset = $this->isModalOpen && $this->assetId ? Asset::with(['assetType:id,name', 'assetSeries:id,name', 'holder:id,name,nik', 'jobsite:id,code'])->find($this->assetId) : null;
        $loan = $asset && $this->mode !== 'lend' ? AssetLoan::activeLoan($asset) : null;
        $options = collect();
        if ($this->isModalOpen && $this->mode === 'lend' && strlen(trim($this->borrowerSearch)) >= 2) {
            $t = '%' . trim($this->borrowerSearch) . '%';
            $options = User::with('jobsite:id,code')->where(fn ($q) => $q->where('name', 'like', $t)->orWhere('nik', 'like', $t))->orderBy('name')->limit(8)->get(['id', 'name', 'nik', 'jobsite_id']);
        }

        return view('livewire.modal.asset.loan', [
            'asset' => $asset,
            'loanMeta' => $loan ? AssetLoan::meta($loan, $asset->id) : [],
            'loan' => $loan,
            'borrower' => $this->borrowerId ? User::find($this->borrowerId, ['id', 'name', 'nik']) : null,
            'borrowerOptions' => $options,
        ]);
    }
}
