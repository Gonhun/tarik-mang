<?php

namespace App\Livewire\AssetLoans;

use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Support\AssetLoan;
use App\Support\SqlLike;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar peminjaman sementara aset.
 */
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url(as: 'tab')]
    public $tab = 'active'; // active | overdue | history
    public $search = '';
    public $lendCode = '';

    protected $listeners = ['refreshLoans' => '$refresh'];

    public function setTab($tab)
    {
        $this->tab = in_array($tab, ['active', 'overdue', 'history'], true) ? $tab : 'active';
        $this->resetPage();
    }

    public function updatingSearch() { $this->resetPage(); }

    /** Cari aset berdasarkan nomor lalu buka form pinjam. */
    public function startLend()
    {
        $code = \App\Livewire\StockOpname\Session::extractCode((string) $this->lendCode);
        if ($code === '') {
            $this->addError('lendCode', 'Ketik atau scan nomor aset dulu.');
            return;
        }
        [$asset, $matchedBy, $candidates] = \App\Livewire\StockOpname\Session::findAsset($code, null, ['holder:id,name']);
        if ($matchedBy === 'sn_ganda') {
            $this->addError('lendCode', "SN \"{$code}\" dipakai beberapa aset (" . $candidates->pluck('asset_number')->implode(', ') . '). Gunakan No. aset.');
            return;
        }
        if (!$asset) {
            $this->addError('lendCode', "\"{$code}\" tidak cocok dengan No. aset maupun SN mana pun.");
            return;
        }
        // Hanya aset Free yang bisa dipinjamkan: jelaskan langsung di bawah input.
        if (!in_array($asset->status, AssetLoan::LENDABLE, true)) {
            $why = match ($asset->status) {
                'Borrowed' => 'sedang dipinjam' . ($asset->holder ? ' oleh ' . $asset->holder->name : '') . '. Terima pengembaliannya dulu.',
                'Used', 'Pre-Used' => 'sedang dipakai' . ($asset->holder ? ' oleh ' . $asset->holder->name : '') . '. Kembalikan dulu ke stok (Pengembalian) sebelum dipinjamkan.',
                'Repair', 'Demage', 'Pending Inspection' => 'sedang perbaikan / menunggu inspeksi.',
                default => "berstatus {$asset->status}.",
            };
            $this->addError('lendCode', "{$asset->asset_number} {$why} Hanya aset berstatus Free yang bisa dipinjamkan.");
            return;
        }
        $this->lendCode = '';
        $this->resetValidation();
        $this->dispatch('openLoanModal', data: ['asset_id' => $asset->id, 'mode' => 'lend']);
    }

    public function remind($assetId)
    {
        $asset = Asset::with('holder')->find($assetId);
        $loan = $asset ? AssetLoan::activeLoan($asset) : null;
        if (!$loan || !$asset->holder?->whatsapp) {
            $this->dispatch('swal:error', ['title' => 'Tidak terkirim', 'text' => 'Peminjam tidak punya nomor WhatsApp.']);
            return;
        }
        $due = Carbon::parse(AssetLoan::meta($loan, $asset->id)['due_date'] ?? now())->locale('id')->isoFormat('D MMMM YYYY');
        try {
            $sent = sendMessageWithWhatsApp($asset->holder->whatsapp, "Halo {$asset->holder->name},\n\nPengingat: aset pinjaman {$asset->asset_number} ({$loan->transaction_number}) dijadwalkan kembali {$due}. Mohon dikembalikan ke tim IT. Terima kasih.");
        } catch (\Throwable $e) {
            $sent = false;
        }
        $this->dispatch($sent ? 'swal:success' : 'swal:error', ['title' => $sent ? 'Terkirim' : 'Gagal', 'text' => $sent ? "Pengingat dikirim ke {$asset->holder->name}." : 'WhatsApp gagal dikirim.']);
    }

    public function render()
    {
        // Pinjaman aktif (jumlahnya kecil): hitung sisa hari dari metadata di PHP.
        $active = AssetLoan::activeAssetsQuery()
            ->with(['assetType:id,name', 'assetSeries:id,name', 'jobsite:id,code', 'holder:id,name,nik,whatsapp', 'transaction.items', 'transaction.creator:id,name'])
            ->get()
            ->map(function ($a) {
                $meta = AssetLoan::meta($a->transaction, $a->id);
                $due = isset($meta['due_date']) ? Carbon::parse($meta['due_date']) : null;
                $a->loan_due = $due;
                $a->loan_purpose = $meta['purpose'] ?? null;
                $a->loan_days_left = $due ? (int) now()->startOfDay()->diffInDays($due, false) : null;
                return $a;
            })
            ->sortBy(fn ($a) => $a->loan_days_left ?? 9999)
            ->values();

        $overdueCount = $active->filter(fn ($a) => $a->loan_days_left !== null && $a->loan_days_left < 0)->count();
        $term = mb_strtolower(trim($this->search));

        if ($this->tab === 'history') {
            $list = AssetTransaction::where('transaction_type', 'HANDOVER')
                ->where('notes', 'like', SqlLike::prefix(AssetLoan::TAG))
                ->when($term, fn ($q) => $q->where(fn ($w) => $w->where('transaction_number', 'like', "%{$term}%")
                    ->orWhereHas('toUser', fn ($u) => $u->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('items.asset', fn ($a) => $a->where('asset_number', 'like', "%{$term}%"))))
                ->with(['toUser:id,name', 'creator:id,name', 'items.asset:id,asset_number,status,asset_transaction_id'])
                ->orderByDesc('id')
                ->paginate(15);
        } else {
            $list = $active
                ->when($this->tab === 'overdue', fn ($c) => $c->filter(fn ($a) => $a->loan_days_left !== null && $a->loan_days_left < 0))
                ->when($term, fn ($c) => $c->filter(fn ($a) => str_contains(mb_strtolower($a->asset_number . ' ' . ($a->holder->name ?? '') . ' ' . ($a->serial_number ?? '')), $term)))
                ->values();
        }

        return view('livewire.asset-loans.index', [
            'list' => $list,
            'activeCount' => $active->count(),
            'overdueCount' => $overdueCount,
        ]);
    }
}
