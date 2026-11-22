<?php

namespace App\Livewire\Modal\Asset;

use App\Models\Asset;
use App\Support\AssetDisposal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pengajuan kehilangan (LOST) atau penghapusan/disposal (DAMAGED / OTHER) satu aset.
 * Keduanya membuat transaksi DISPOSED dengan approval dari workflow (lihat AssetDisposal).
 */
class Disposal extends Component
{
    use WithFileUploads;

    /** Status aset yang boleh dilaporkan hilang. */
    public const LOSABLE = ['Free', 'Used', 'Pre-Used', 'Repair', 'Demage', 'Borrowed'];

    /** Status aset yang boleh diajukan disposal. */
    public const DISPOSABLE = ['Free', 'Repair', 'Demage', 'Scrap'];

    public $isModalOpen = false;

    #[Locked]
    public $assetId;
    #[Locked]
    public $mode = 'LOST'; // LOST | DISPOSE

    public $reason = 'LOST';
    public $incident_date;
    public $incident_location = '';
    public $notes = '';
    public $evidence;

    protected $listeners = ['openDisposalModal' => 'open'];

    protected function isManager(): bool
    {
        $user = Auth::user();

        return $user->isItStaff() || $user->can('Master Asset');
    }

    protected function canSubmit(Asset $asset, string $mode): bool
    {
        if ($mode === 'LOST') {
            return in_array($asset->status, self::LOSABLE, true)
                && ($this->isManager() || (int) $asset->holder_user_id === Auth::id());
        }

        return in_array($asset->status, self::DISPOSABLE, true) && $this->isManager();
    }

    public function open($data = [])
    {
        $mode = ($data['mode'] ?? 'LOST') === 'DISPOSE' ? 'DISPOSE' : 'LOST';
        $asset = Asset::find($data['asset_id'] ?? null);

        if (!$asset || !$this->canSubmit($asset, $mode)) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa diajukan', 'text' => $asset
                ? "Aset {$asset->asset_number} berstatus {$asset->status} atau Anda tidak berhak mengajukannya."
                : 'Aset tidak ditemukan.']);
            return;
        }
        // Workflow belum siap: jangan buka form, beri tahu user.
        $type = $mode === 'LOST' ? 'LOST' : 'DISPOSED';
        if ($problem = \App\Support\Workflow::problem($type, Auth::user(), $asset->jobsite_id, $type === 'LOST' ? ['DISPOSED'] : [])) {
            $this->dispatch('swal:error', ['title' => 'Workflow belum siap', 'text' => $problem]);
            return;
        }
        if ($open = AssetDisposal::openDisposalFor($asset)) {
            $this->dispatch('swal:error', ['title' => 'Sudah diajukan', 'text' => "Aset ini sudah punya pengajuan berjalan: {$open->transaction_number}."]);
            return;
        }

        $this->resetValidation();
        $this->reset(['incident_location', 'notes', 'evidence']);
        $this->assetId = $asset->id;
        $this->mode = $mode;
        $this->reason = $mode === 'LOST' ? 'LOST' : ($asset->status === 'Free' ? 'OTHER' : 'DAMAGED');
        $this->incident_date = now()->format('Y-m-d');
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function submit()
    {
        $asset = Asset::find($this->assetId);
        abort_unless($asset && $this->canSubmit($asset, $this->mode), 403);

        $isLost = $this->mode === 'LOST';
        $this->validate([
            'reason' => $isLost ? 'in:LOST' : 'required|in:DAMAGED,OTHER',
            'incident_date' => $isLost ? 'required|date|before_or_equal:today' : 'nullable',
            'incident_location' => $isLost ? 'required|string|max:255' : 'nullable|string|max:255',
            'notes' => 'required|string|min:10|max:2000',
            'evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'incident_date.required' => 'Tanggal kejadian wajib diisi.',
            'incident_date.before_or_equal' => 'Tanggal kejadian tidak boleh di masa depan.',
            'incident_location.required' => 'Lokasi terakhir / kejadian wajib diisi.',
            'notes.required' => $isLost ? 'Kronologi wajib diisi.' : 'Alasan disposal wajib diisi.',
            'notes.min' => 'Minimal 10 karakter.',
            'evidence.mimes' => 'Lampiran harus PDF atau gambar (JPG/PNG).',
            'evidence.max' => 'Lampiran maksimal 5 MB.',
        ]);

        $notes = $isLost
            ? 'Tanggal kejadian ' . \Carbon\Carbon::parse($this->incident_date)->locale('id')->isoFormat('D MMM YYYY')
                . ', lokasi ' . $this->incident_location . '. Kronologi: ' . trim($this->notes)
            : trim($this->notes);

        $storedPath = null;
        try {
            $transaction = DB::transaction(function () use ($asset, $notes, &$storedPath) {
                $asset = Asset::whereKey($asset->id)->lockForUpdate()->first();

                $transaction = AssetDisposal::create($asset, Auth::user(), $notes, $this->reason);

                if ($this->evidence) {
                    $storedPath = $this->evidence->store("disposal_documents/{$transaction->id}", 'public');
                    $transaction->update([
                        'document_path' => $storedPath,
                        'document_name' => $this->evidence->getClientOriginalName(),
                    ]);
                }

                // Hilang: status Missing (pemegang tetap tercatat sampai disetujui). Lainnya: Pre Disposed.
                $asset->update(['status' => AssetDisposal::pendingStatusFor($this->reason)]);
                AssetDisposal::finishIfAutoApproved($transaction);

                return $transaction->fresh();
            });
        } catch (\RuntimeException $e) {
            if ($storedPath) \Illuminate\Support\Facades\Storage::disk('public')->delete($storedPath);
            $this->addError('notes', $e->getMessage());
            return;
        } catch (\Throwable $e) {
            if ($storedPath) \Illuminate\Support\Facades\Storage::disk('public')->delete($storedPath);
            Log::error("Pengajuan disposal/kehilangan aset #{$asset->id} gagal: " . $e->getMessage());
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Pengajuan gagal disimpan. Coba lagi atau hubungi Administrator.']);
            return;
        }

        $this->closeModal();
        $this->dispatch('refreshAssetList');
        $this->dispatch('refreshMyAssets');
        $done = $transaction->status === 'Completed';
        $this->dispatch('swal:success', [
            'title' => $isLost ? 'Laporan kehilangan dikirim' : 'Pengajuan disposal dikirim',
            'text' => $done
                ? "{$transaction->transaction_number} disetujui otomatis karena Anda approver semua tahap. Aset {$asset->asset_number} kini Disposed."
                : "{$transaction->transaction_number} menunggu persetujuan. Aset {$asset->asset_number} berstatus "
                    . AssetDisposal::pendingStatusFor($this->reason) . ' sampai ada keputusan.',
        ]);
    }

    public function render()
    {
        return view('livewire.modal.asset.disposal', [
            'asset' => $this->isModalOpen && $this->assetId
                ? Asset::with(['assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name', 'holder:id,name,nik', 'jobsite:id,code'])->find($this->assetId)
                : null,
        ]);
    }
}
