<?php

namespace App\Livewire\Master\Asset;

use App\Http\Controllers\pdf\AssetBaPrint;
use App\Models\Asset;
use App\Models\AssetBrand;
use App\Models\AssetSeries;
use App\Models\AssetTransaction;
use App\Models\AssetType;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Entity;
use App\Models\Jobsite;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

class AssetDetail extends Component
{
    public Asset $asset;

    #[Url(as: 'tab')]
    public string $tab = 'riwayat';

    /** Label kolom untuk log perubahan. */
    public const FIELD_LABELS = [
        'asset_number' => 'No. aset',
        'old_asset_number' => 'No. aset lama',
        'status' => 'Status',
        'holder_user_id' => 'Pemegang',
        'jobsite_id' => 'Site',
        'department_id' => 'Department',
        'entity_id' => 'Entitas',
        'cost_center_id' => 'Cost center',
        'asset_type_id' => 'Tipe',
        'asset_brand_id' => 'Merek',
        'asset_series_id' => 'Seri',
        'serial_number' => 'Serial number',
        'current_location_detail' => 'Lokasi',
        'pr_number' => 'No. PR',
        'po_number' => 'No. PO',
        'sap_number' => 'No. SAP / akunting',
        'price' => 'Harga',
        'asset_transaction_id' => 'Transaksi serah terima',
        'asset_transaction_return_id' => 'Transaksi pengembalian',
        'asset_request_item_id' => 'Item pengajuan',
        'created_by' => 'Dibuat oleh',
        'deleted_at' => 'Dihapus',
    ];

    public const TRANSACTION_META = [
        'HANDOVER' => ['Serah terima (BAST)', 'bx-user-check', 'primary'],
        'RETURN' => ['Pengembalian', 'bx-undo', 'secondary'],
        'MUTATION' => ['Mutasi antar site', 'bx-transfer', 'info'],
        'DISPOSED' => ['Pengajuan disposal', 'bx-trash', 'danger'],
    ];

    public function mount(Asset $asset)
    {
        abort_unless(Gate::allows('view-asset', $asset), 403, 'Anda tidak punya akses ke aset ini.');
        $this->asset = $asset;
        if (!in_array($this->tab, ['riwayat', 'dokumen', 'log'], true)) {
            $this->tab = 'riwayat';
        }
    }

    public function setTab(string $tab)
    {
        $this->tab = in_array($tab, ['riwayat', 'dokumen', 'log'], true) ? $tab : 'riwayat';
    }

    protected function canManage(): bool
    {
        $user = Auth::user();

        return $user->can('Master Asset') || $user->isItStaff() || $user->hasRole(['Administrator IT', 'Administrator']);
    }

    protected function transactions(): Collection
    {
        return $this->asset->transactions()
            ->withPivot('condition_notes')
            ->with([
                'fromUser:id,name,nik', 'toUser:id,name,nik', 'creator:id,name',
                'fromJobsite:id,code,name', 'toJobsite:id,code,name',
                'approvals' => fn ($q) => $q->with(['approver:id,name', 'actionBy:id,name']),
            ])
            ->orderByDesc('asset_transactions.id')
            ->get();
    }

    protected function buildTimeline(Collection $transactions): Collection
    {
        $asset = $this->asset;
        $events = collect();

        $events->push([
            'date' => $asset->created_at,
            'title' => 'Aset didaftarkan',
            'description' => $asset->assetRequestItem
                ? 'Dari pengajuan ' . ($asset->assetRequestItem->assetRequest->request_number ?? '-')
                    . ' oleh ' . ($asset->assetRequestItem->assetRequest->requester->name ?? '-')
                : 'Didaftarkan manual' . ($asset->creator ? ' oleh ' . $asset->creator->name : ''),
            'icon' => 'bx-plus-circle',
            'color' => 'success',
            'meta' => [],
        ]);

        foreach ($transactions as $trx) {
            [$label, $icon, $color] = self::TRANSACTION_META[$trx->transaction_type] ?? [$trx->transaction_type, 'bx-file', 'secondary'];
            if (str_starts_with((string) $trx->notes, \App\Support\AssetLoan::TAG)) {
                [$label, $icon, $color] = ['Peminjaman sementara', 'bx-time-five', 'warning'];
            } elseif (str_starts_with((string) $trx->notes, \App\Support\AssetLoan::RETURN_TAG)) {
                [$label, $icon, $color] = ['Kembali dari pinjaman', 'bx-undo', 'secondary'];
            }

            $description = match ($trx->transaction_type) {
                'HANDOVER' => 'Dari ' . ($trx->fromUser->name ?? 'gudang') . ' kepada ' . ($trx->toUser->name ?? '-'),
                'RETURN' => 'Dikembalikan oleh ' . ($trx->fromUser->name ?? '-') . ($trx->toUser ? ', diinspeksi ' . $trx->toUser->name : ''),
                'MUTATION' => 'Dari ' . ($trx->fromJobsite->code ?? '-') . ' ke ' . ($trx->toJobsite->code ?? '-'),
                'DISPOSED' => 'Diajukan oleh ' . ($trx->creator->name ?? '-'),
                default => $trx->notes ?? '',
            };

            $events->push([
                'date' => $trx->transaction_date ?? $trx->created_at,
                'title' => $label,
                'description' => $description,
                'icon' => $icon,
                'color' => $trx->status === 'Rejected' ? 'danger' : $color,
                'meta' => array_filter([
                    'No.' => $trx->transaction_number,
                    'Status' => $trx->status,
                    'Kondisi' => $trx->pivot->condition_notes ?? null,
                    'Catatan' => $trx->notes,
                    'Dibuat oleh' => $trx->creator->name ?? null,
                ]),
                'transaction' => $trx,
            ]);
        }

        foreach ($asset->damageReports as $report) {
            $events->push([
                'date' => $report->report_date ?? $report->created_at,
                'title' => 'Laporan kerusakan',
                'description' => $report->damage_description,
                'icon' => 'bx-error',
                'color' => 'warning',
                'meta' => array_filter([
                    'Status' => $report->damage_status,
                    'Pelapor' => $report->reporter->name ?? null,
                    'Penilaian' => $report->assessment_notes,
                ]),
            ]);
            if ($report->repaired_date) {
                $events->push([
                    'date' => $report->repaired_date,
                    'title' => 'Perbaikan selesai',
                    'description' => $report->repair_notes ?: 'Aset selesai diperbaiki.',
                    'icon' => 'bx-wrench',
                    'color' => 'success',
                    'meta' => array_filter([
                        'Biaya' => $report->repair_cost ? 'Rp ' . number_format($report->repair_cost, 0, ',', '.') : null,
                    ]),
                ]);
            }
        }

        return $events->sortByDesc(fn ($e) => (string) $e['date'])->values();
    }

    /**
     * Log perubahan dari audit (owen-it/laravel-auditing): siapa, kapan, kolom apa, dari -> ke.
     * ID relasi diterjemahkan ke nama.
     */
    protected function auditLog(): Collection
    {
        $audits = $this->asset->audits()->with('user')->latest()->get();

        // Kumpulkan semua ID yang perlu diterjemahkan, lalu ambil sekali per tabel.
        $ids = [];
        foreach ($audits as $audit) {
            foreach ([(array) $audit->old_values, (array) $audit->new_values] as $values) {
                foreach ($values as $field => $value) {
                    if ($value !== null && $value !== '' && (str_ends_with($field, '_id') || $field === 'created_by')) {
                        $ids[$field][] = $value;
                    }
                }
            }
        }

        $lookup = function (string $field, array $values) {
            $values = array_values(array_unique(array_filter($values)));
            if (!$values) return collect();

            return match ($field) {
                'holder_user_id', 'created_by' => User::whereIn('id', $values)->pluck('name', 'id'),
                'jobsite_id' => Jobsite::whereIn('id', $values)->pluck('code', 'id'),
                'department_id' => Department::whereIn('id', $values)->pluck('name', 'id'),
                'entity_id' => Entity::whereIn('id', $values)->pluck('name', 'id'),
                'cost_center_id' => CostCenter::whereIn('id', $values)->pluck('cost_center_number', 'id'),
                'asset_type_id' => AssetType::whereIn('id', $values)->pluck('name', 'id'),
                'asset_brand_id' => AssetBrand::whereIn('id', $values)->pluck('name', 'id'),
                'asset_series_id' => AssetSeries::whereIn('id', $values)->pluck('name', 'id'),
                'asset_transaction_id', 'asset_transaction_return_id' => AssetTransaction::withTrashed()->whereIn('id', $values)->pluck('transaction_number', 'id'),
                default => collect(),
            };
        };
        $names = collect($ids)->map(fn ($values, $field) => $lookup($field, $values));

        $format = function (string $field, $value) use ($names) {
            if ($value === null || $value === '') return '—';
            if (isset($names[$field]) && $names[$field]->has($value)) return $names[$field][$value];
            if ($field === 'price' && is_numeric($value)) return 'Rp ' . number_format((float) $value, 0, ',', '.');
            return is_scalar($value) ? (string) $value : json_encode($value);
        };

        $eventLabels = ['created' => 'Dibuat', 'updated' => 'Diubah', 'deleted' => 'Dihapus', 'restored' => 'Dipulihkan'];

        return $audits->map(function ($audit) use ($format, $eventLabels) {
            $old = (array) $audit->old_values;
            $new = (array) $audit->new_values;
            $fields = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
            $fields = array_values(array_filter($fields, fn ($f) => !in_array($f, ['updated_at', 'created_at', 'id'], true)));

            return [
                'date' => $audit->created_at,
                'event' => $eventLabels[$audit->event] ?? $audit->event,
                'event_raw' => $audit->event,
                'user' => $audit->user->name ?? 'Sistem',
                'url' => $audit->url,
                'changes' => collect($fields)->map(fn ($f) => [
                    'label' => self::FIELD_LABELS[$f] ?? $f,
                    'old' => $format($f, $old[$f] ?? null),
                    'new' => $format($f, $new[$f] ?? null),
                ])->all(),
            ];
        })->filter(fn ($row) => count($row['changes']) > 0)->values();
    }

    public function render()
    {
        $this->asset->loadMissing([
            'assetType', 'assetBrand', 'assetSeries', 'jobsite', 'departement', 'entity', 'costCenter',
            'holder.department', 'holder.position', 'creator:id,name',
            'assetRequestItem.assetRequest.requester',
            'damageReports.reporter:id,name',
        ]);

        $canManage = $this->canManage();
        $transactions = $this->transactions();

        $printable = $transactions
            ->filter(fn ($t) => in_array($t->transaction_type, AssetBaPrint::PRINTABLE_TYPES, true))
            ->map(function ($t) use ($canManage) {
                $t->can_print = $canManage || Gate::allows('view-asset-transaction', $t);
                return $t;
            })
            ->values();

        return view('livewire.master.asset.asset-detail', [
            'timeline' => $this->buildTimeline($transactions),
            'printable' => $printable,
            'auditLog' => $canManage && $this->tab === 'log' ? $this->auditLog() : collect(),
            'auditCount' => $canManage ? $this->asset->audits()->count() : 0,
            'canManage' => $canManage,
            'transactionMeta' => self::TRANSACTION_META,
        ]);
    }
}
