<?php

namespace App\Livewire\Admin\AssetRequest;

use App\Models\AssetRequest;
use App\Models\AssetRequestItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Input No. PR & No. Akunting untuk item "beli baru" dari pengajuan aset yang sudah disetujui.
 *
 * Pola kerja di lapangan: satu PR untuk semua item satu pengajuan, No. akunting berurutan per unit.
 * Jadi: isi PR sekali, isi No. akunting pertama lalu "Isi berurutan", koreksi bila perlu, Simpan semua.
 */
class Administrator extends Component
{
    public const STATUSES = ['Pending PR & Accounting Number', 'Pending PR Number', 'Pending Accounting Number'];

    #[Url(as: 'q')]
    public $search = '';

    #[Url(as: 'id')]
    public $selectedId = null;

    // Form untuk pengajuan yang dipilih
    public $prNumber = '';
    public $startAccounting = '';
    /** [item_id => ['pr' => string, 'acc' => string]] */
    public $rows = [];

    public function mount()
    {
        if ($this->selectedId) {
            $this->select($this->selectedId);
        }
    }

    protected function pendingQuery()
    {
        // Dikelompokkan: syarat item "beli baru" berlaku untuk semua status.
        return AssetRequest::query()
            ->whereIn('status', self::STATUSES)
            ->whereHas('items', fn ($q) => $q->where('fulfillment_type', 'NEW_PURCHASE'));
    }

    public function select($id)
    {
        $request = $this->pendingQuery()->with(['items' => fn ($q) => $q->where('fulfillment_type', 'NEW_PURCHASE')->orderBy('id')])->find($id);
        if (!$request) {
            $this->selectedId = null;
            $this->rows = [];
            return;
        }

        $this->selectedId = $request->id;
        $this->rows = [];
        foreach ($request->items as $item) {
            $this->rows[$item->id] = ['pr' => (string) $item->pr_number, 'acc' => (string) $item->accounting_number];
        }
        // PR yang sudah pernah diisi (mis. sebagian) dipakai sebagai nilai awal.
        $this->prNumber = (string) (collect($this->rows)->pluck('pr')->filter()->first() ?? '');
        $this->startAccounting = '';
        $this->resetValidation();
    }

    /** Terapkan No. PR ke semua unit. */
    public function applyPrToAll()
    {
        $this->validateOnly('prNumber', ['prNumber' => 'required|digits:10'], $this->messagesFor());
        foreach ($this->rows as $id => $row) {
            $this->rows[$id]['pr'] = $this->prNumber;
        }
    }

    /** Isi No. akunting berurutan mulai dari nomor pertama, untuk semua unit. */
    public function fillSequential()
    {
        $this->validateOnly('startAccounting', ['startAccounting' => 'required|digits:12'], $this->messagesFor());
        $n = $this->startAccounting;
        foreach (array_keys($this->rows) as $id) {
            $this->rows[$id]['acc'] = $n;
            $n = self::increment($n);
        }
    }

    /** Tambah 1 pada string angka tanpa kehilangan nol di depan / presisi. */
    protected static function increment(string $digits): string
    {
        // Penjumlahan string (tanpa ekstensi bcmath), aman untuk angka 12 digit.
        $chars = str_split($digits);
        for ($i = count($chars) - 1; $i >= 0; $i--) {
            if ($chars[$i] !== '9') {
                $chars[$i] = (string) ((int) $chars[$i] + 1);
                return implode('', $chars);
            }
            $chars[$i] = '0';
        }

        return '1' . implode('', $chars);
    }

    protected function messagesFor(): array
    {
        return [
            'prNumber.required' => 'Isi No. PR dulu.',
            'prNumber.digits' => 'No. PR harus 10 digit angka.',
            'startAccounting.required' => 'Isi No. akunting pertama dulu.',
            'startAccounting.digits' => 'No. akunting harus 12 digit angka.',
            'rows.*.pr.required' => 'No. PR wajib diisi.',
            'rows.*.pr.digits' => 'No. PR harus 10 digit.',
            'rows.*.acc.required' => 'No. akunting wajib diisi.',
            'rows.*.acc.digits' => 'No. akunting harus 12 digit.',
            'rows.*.acc.distinct' => 'No. akunting tidak boleh sama dengan unit lain.',
        ];
    }

    public function saveAll()
    {
        if (!$this->selectedId || !$this->rows) {
            return;
        }

        // Rapikan spasi yang ikut tersalin.
        foreach ($this->rows as $id => $row) {
            $this->rows[$id] = ['pr' => preg_replace('/\s+/', '', (string) $row['pr']), 'acc' => preg_replace('/\s+/', '', (string) $row['acc'])];
        }

        $this->validate([
            'rows.*.pr' => 'required|digits:10',
            'rows.*.acc' => 'required|digits:12|distinct',
        ], $this->messagesFor());

        // No. akunting tidak boleh sudah dipakai item pengajuan lain.
        $used = AssetRequestItem::whereIn('accounting_number', collect($this->rows)->pluck('acc'))
            ->whereNotIn('id', array_keys($this->rows))
            ->with('assetRequest:id,request_number')
            ->get(['id', 'accounting_number', 'asset_request_id']);
        if ($used->isNotEmpty()) {
            foreach ($this->rows as $id => $row) {
                if ($hit = $used->firstWhere('accounting_number', $row['acc'])) {
                    $this->addError("rows.{$id}.acc", 'Sudah dipakai di ' . ($hit->assetRequest->request_number ?? 'pengajuan lain') . '.');
                }
            }
            return;
        }

        try {
            $request = DB::transaction(function () {
                $request = $this->pendingQuery()->lockForUpdate()->findOrFail($this->selectedId);
                $items = AssetRequestItem::where('asset_request_id', $request->id)
                    ->where('fulfillment_type', 'NEW_PURCHASE')
                    ->whereIn('id', array_keys($this->rows))
                    ->get();

                foreach ($items as $item) {
                    $item->update([
                        'pr_number' => $this->rows[$item->id]['pr'],
                        'accounting_number' => $this->rows[$item->id]['acc'],
                    ]);
                }

                // Semua item beli baru sudah punya PR & akunting -> lanjut ke tahap PO.
                $missing = AssetRequestItem::where('asset_request_id', $request->id)
                    ->where('fulfillment_type', 'NEW_PURCHASE')
                    ->where(fn ($q) => $q->whereNull('pr_number')->orWhere('pr_number', '')->orWhereNull('accounting_number')->orWhere('accounting_number', ''))
                    ->count();
                if ($missing === 0) {
                    $request->update(['status' => 'Pending PO']);
                }

                return $request->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('Simpan PR/akunting gagal: ' . $e->getMessage());
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Data gagal disimpan. Muat ulang halaman lalu coba lagi.']);
            return;
        }

        $this->dispatch('swal:success', [
            'title' => 'Tersimpan',
            'text' => "{$request->request_number}: " . count($this->rows) . ' unit diisi PR & akunting.'
                . ($request->status === 'Pending PO' ? ' Pengajuan lanjut ke tahap PO.' : ''),
        ]);

        // Langsung buka pengajuan berikutnya di antrian.
        $next = $this->pendingQuery()->where('id', '!=', $request->id)->orderBy('request_date')->value('id');
        $next ? $this->select($next) : $this->reset(['selectedId', 'rows', 'prNumber', 'startAccounting']);
    }

    public function render()
    {
        $queue = $this->pendingQuery()
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn ($w) => $w->where('request_number', 'like', $term)
                    ->orWhereHas('requester', fn ($r) => $r->where('name', 'like', $term))
                    ->orWhereHas('jobsite', fn ($j) => $j->where('code', 'like', $term)));
            })
            ->with(['requester:id,name', 'jobsite:id,code'])
            ->withCount([
                'items as units' => fn ($q) => $q->where('fulfillment_type', 'NEW_PURCHASE'),
                'items as filled' => fn ($q) => $q->where('fulfillment_type', 'NEW_PURCHASE')->whereNotNull('pr_number')->where('pr_number', '!=', '')->whereNotNull('accounting_number')->where('accounting_number', '!=', ''),
            ])
            ->orderBy('request_date')
            ->get();

        $selected = null;
        $items = collect();
        if ($this->selectedId) {
            $selected = AssetRequest::with(['requester:id,name,nik', 'jobsite:id,code,name', 'approvals' => fn ($q) => $q->where('status', 'Approved')])
                ->find($this->selectedId);
            $items = AssetRequestItem::where('asset_request_id', $this->selectedId)
                ->where('fulfillment_type', 'NEW_PURCHASE')
                ->with(['assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name'])
                ->orderBy('id')->get();
        }

        return view('livewire.admin.asset-request.administrator', [
            'queue' => $queue,
            'selected' => $selected,
            'items' => $items,
            'approvedAt' => $selected?->approvals->max('action_at'),
        ]);
    }
}
