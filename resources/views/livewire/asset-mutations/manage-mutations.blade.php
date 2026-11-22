@php
    $listTargets = 'search,statusFilter,scope,gotoPage,nextPage,previousPage';
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat('D MMM YYYY') : '-';
@endphp
<div>
    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="mb-1">Mutasi aset</h4>
                <p class="text-muted mb-0">Pindah aset antar site: persetujuan → pelepasan site asal → penerimaan site tujuan.</p>
            </div>
            <button wire:click="create" wire:loading.attr="disabled" wire:target="create" class="btn btn-primary btn-sm">
                <span wire:loading.remove wire:target="create"><i class="bx bx-plus me-1"></i>Buat permintaan mutasi</span>
                <span wire:loading wire:target="create"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Memeriksa workflow...</span>
            </button>
        </div>

        {{-- Ringkasan status --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" wire:click="$set('statusFilter', '')" class="btn btn-sm {{ $statusFilter === '' ? 'btn-dark' : 'btn-outline-secondary' }}">
                Semua <span class="badge bg-label-secondary ms-1">{{ $statusCounts->sum() }}</span>
            </button>
            @foreach ($statusCounts as $status => $total)
                @php $c = $statusColors[$status] ?? 'secondary'; @endphp
                <button type="button" wire:click="$set('statusFilter', @js($status))" class="btn btn-sm {{ $statusFilter === $status ? 'btn-' . $c : 'btn-outline-' . $c }}">
                    {{ $status }} <span class="badge bg-label-{{ $c }} ms-1">{{ $total }}</span>
                </button>
            @endforeach
        </div>

        <div class="card">
            <div class="card-body border-bottom py-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="No. mutasi, catatan, no. aset, SN..." aria-label="Cari mutasi">
                        </div>
                    </div>
                    <div class="col-8 col-md-4">
                        <select wire:model.live="scope" class="form-select form-select-sm" aria-label="Lingkup">
                            <option value="mine">Site saya (asal atau tujuan)</option>
                            <option value="all">Semua site</option>
                        </select>
                    </div>
                    <div class="col-4 col-md-2 text-end">
                        <span wire:loading wire:target="{{ $listTargets }}" class="spinner-border spinner-border-sm text-primary" role="status" aria-label="Memuat"></span>
                    </div>
                </div>
            </div>

            <div class="position-relative">
                <div wire:loading.flex wire:target="{{ $listTargets }}" class="position-absolute top-0 start-0 w-100 h-100 align-items-start justify-content-center pt-5" style="background: rgba(255,255,255,.65); z-index: 5;">
                    <span class="badge bg-white text-primary shadow-sm px-3 py-2"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Memuat data...</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mutasi</th>
                                <th>Rute</th>
                                <th>Aset</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mutations as $m)
                                @php
                                    $c = $statusColors[$m->status] ?? 'secondary';
                                    $pending = $m->approvals->where('status', 'Pending')->sortBy('step_order')->first();
                                    $done = $m->approvals->where('status', 'Approved')->count();
                                    $canRelease = $m->status === 'Pending Release' && ($isAdmin || $userJobsiteId == $m->from_jobsite_id);
                                    $canReceive = $m->status === 'Delivery' && ($isAdmin || $userJobsiteId == $m->to_jobsite_id);
                                @endphp
                                <tr wire:key="mut-{{ $m->id }}">
                                    <td>
                                        <a href="{{ route('asset.mutasi-status', $m->id) }}" class="fw-semibold text-heading">{{ $m->transaction_number }}</a>
                                        <small class="d-block text-muted">{{ $fmt($m->transaction_date) }} · {{ $m->creator->name ?? '-' }}</small>
                                        @if ($m->notes)
                                            <small class="d-block text-muted text-truncate" style="max-width: 260px" title="{{ $m->notes }}">{{ $m->notes }}</small>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="fw-medium">{{ $m->fromJobsite->code ?? '-' }}</span>
                                        <i class="bx bx-right-arrow-alt text-muted"></i>
                                        <span class="fw-medium">{{ $m->toJobsite->code ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-secondary">{{ $m->items_count }} aset</span>
                                        <small class="d-block text-muted text-truncate" style="max-width: 200px">{{ $m->items->pluck('asset.asset_number')->filter()->take(3)->implode(', ') }}{{ $m->items_count > 3 ? ', ...' : '' }}</small>
                                    </td>
                                    <td style="min-width: 200px">
                                        <span class="badge bg-label-{{ $c }}">{{ $m->status }}</span>
                                        @php
                                            $incomplete = !in_array($m->status, \App\Support\TransactionApprovalRepair::CLOSED_STATUSES)
                                                && ($m->approvals->isEmpty() || $m->approvals->where('status', 'Pending')->whereNull('approver_user_id')->isNotEmpty());
                                        @endphp
                                        {{-- Nama validator per tahap --}}
                                        @if ($m->approvals->isNotEmpty())
                                            <ul class="list-unstyled small mb-0 mt-1">
                                                @foreach ($m->approvals->sortBy('step_order') as $ap)
                                                    @php
                                                        $isNow = $pending && $pending->id === $ap->id && $m->status === 'Pending Approval';
                                                        [$ic, $cl] = match (true) {
                                                            $ap->status === 'Approved' => ['bx-check', 'text-success'],
                                                            $ap->status === 'Rejected' => ['bx-x', 'text-danger'],
                                                            !$ap->approver_user_id => ['bx-error', 'text-danger'],
                                                            $isNow => ['bx-time-five', 'text-warning'],
                                                            default => ['bx-circle', 'text-muted'],
                                                        };
                                                    @endphp
                                                    <li class="{{ $cl }} text-truncate" style="max-width: 240px" title="{{ $ap->approver_role_name }}">
                                                        <i class="bx {{ $ic }}"></i> {{ $ap->approver->name ?? 'Belum ditentukan' }}
                                                        @if ($ap->notes === \App\Support\Workflow::AUTO_NOTE)<span class="text-muted">(otomatis)</span>@endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @elseif ($m->status === 'Pending Approval')
                                            <small class="d-block text-danger"><i class="bx bx-error"></i> Tanpa approver</small>
                                        @endif
                                        @if ($incomplete && $canRepair)
                                            <button type="button" wire:click="repairApproval({{ $m->id }})" wire:confirm="Perbarui approval {{ $m->transaction_number }} dengan workflow mutasi saat ini?" wire:loading.attr="disabled" wire:target="repairApproval({{ $m->id }})" class="btn btn-sm btn-outline-danger mt-1 py-0">
                                                <span wire:loading.remove wire:target="repairApproval({{ $m->id }})"><i class="bx bx-wrench"></i> Perbaiki approval</span>
                                                <span wire:loading wire:target="repairApproval({{ $m->id }})"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                            </button>
                                        @endif
                                        @if ($m->status === 'Pending Release')
                                            <small class="d-block text-muted">Menunggu dikirim {{ $m->fromJobsite->code ?? '' }}</small>
                                        @elseif ($m->status === 'Delivery')
                                            <small class="d-block text-muted">Menunggu diterima {{ $m->toJobsite->code ?? '' }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @if ($canRelease)
                                            <button wire:click="openReleaseModal({{ $m->id }})" wire:loading.attr="disabled" wire:target="openReleaseModal({{ $m->id }})" class="btn btn-sm btn-success">
                                                <span wire:loading.remove wire:target="openReleaseModal({{ $m->id }})"><i class="bx bx-package me-1"></i>Lepas aset</span>
                                                <span wire:loading wire:target="openReleaseModal({{ $m->id }})"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                            </button>
                                        @elseif ($canReceive)
                                            <button wire:click="confirmReceipt({{ $m->id }})" wire:confirm="Konfirmasi {{ $m->items_count }} aset mutasi {{ $m->transaction_number }} sudah diterima di {{ $m->toJobsite->code ?? 'site tujuan' }}?" wire:loading.attr="disabled" wire:target="confirmReceipt({{ $m->id }})" class="btn btn-sm btn-primary">
                                                <span wire:loading.remove wire:target="confirmReceipt({{ $m->id }})"><i class="bx bx-check me-1"></i>Terima aset</span>
                                                <span wire:loading wire:target="confirmReceipt({{ $m->id }})"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                            </button>
                                        @endif
                                        <a href="{{ route('asset.mutasi-status', $m->id) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-5"><i class="bx bx-transfer fs-1 d-block mb-2"></i>Belum ada mutasi yang cocok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-body border-top">{{ $mutations->links() }}</div>
        </div>
    </div>

    {{-- Modal: permintaan mutasi --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="mutationTitle" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="mutationTitle">Permintaan mutasi aset</h5>
                        <button type="button" class="btn-close" wire:click="closeModal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-4">
                                <label for="mut_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" id="mut_date" wire:model="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror">
                                @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6 col-md-4">
                                <label for="mut_from" class="form-label">Dari site <span class="text-danger">*</span></label>
                                <select id="mut_from" wire:model.live="from_jobsite_id" class="form-select @error('from_jobsite_id') is-invalid @enderror">
                                    <option value="">-- Pilih --</option>
                                    @foreach ($allJobsites as $j)
                                        <option value="{{ $j->id }}">{{ $j->code }} · {{ $j->name }}</option>
                                    @endforeach
                                </select>
                                @error('from_jobsite_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6 col-md-4">
                                <label for="mut_to" class="form-label">Ke site <span class="text-danger">*</span></label>
                                <select id="mut_to" wire:model="to_jobsite_id" class="form-select @error('to_jobsite_id') is-invalid @enderror">
                                    <option value="">-- Pilih --</option>
                                    @foreach ($allJobsites as $j)
                                        @if ($j->id != $from_jobsite_id)
                                            <option value="{{ $j->id }}">{{ $j->code }} · {{ $j->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('to_jobsite_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="mut_notes" class="form-label">Alasan mutasi <span class="text-danger">*</span></label>
                            <textarea id="mut_notes" wire:model="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Mis. kebutuhan proyek baru di site tujuan..."></textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <label class="form-label mb-0">Aset yang dimutasi <span class="text-danger">*</span></label>
                            <span class="badge bg-label-primary">{{ $selectedCount }} dipilih</span>
                        </div>
                        @error('selected_assets') <div class="alert alert-danger py-2 small">{{ $message }}</div> @enderror
                        @if ($from_jobsite_id)
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text"><i class="bx bx-search"></i></span>
                                <input type="search" wire:model.live.debounce.400ms="searchAsset" class="form-control" placeholder="Cari no. aset, SN, seri..." aria-label="Cari aset">
                                <span wire:loading wire:target="searchAsset,from_jobsite_id" class="input-group-text"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                            </div>
                            <div class="border rounded" style="max-height: 280px; overflow-y: auto;">
                                @forelse ($availableAssets as $a)
                                    <label class="d-flex align-items-start gap-2 px-3 py-2 border-bottom mb-0" for="mut_asset_{{ $a->id }}" wire:key="mut-asset-{{ $a->id }}">
                                        <input class="form-check-input mt-1" type="checkbox" value="{{ $a->id }}" id="mut_asset_{{ $a->id }}" wire:model.live="selected_assets">
                                        <span class="min-w-0">
                                            <span class="fw-medium">{{ $a->asset_number }}</span>
                                            <span class="badge bg-label-{{ $a->status === 'Free' ? 'success' : 'primary' }} ms-1">{{ $a->status }}</span>
                                            <small class="d-block text-muted text-truncate">{{ $a->assetType->name ?? '-' }} · {{ $a->assetSeries->name ?? '-' }} · SN {{ $a->serial_number ?: '-' }}{{ $a->holder ? ' · dipegang ' . $a->holder->name : '' }}</small>
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-muted text-center small py-4 mb-0">Tidak ada aset Free/Used yang cocok di site ini.</p>
                                @endforelse
                            </div>
                            <small class="text-muted">Menampilkan maks. 100 aset; gunakan pencarian untuk mempersempit.</small>
                        @else
                            <p class="text-muted small border rounded p-3 mb-0">Pilih site asal untuk melihat aset yang bisa dimutasi.</p>
                        @endif
                    </div>
                    <div class="modal-footer d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeModal">Batal</button>
                        <button type="button" wire:click="store" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store">
                            <span wire:loading.remove wire:target="store"><i class="bx bx-send me-1"></i>Ajukan mutasi</span>
                            <span wire:loading wire:target="store"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: pelepasan aset oleh site asal --}}
    @if ($isReleaseModalOpen && $releaseTrx)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="releaseTitle" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="releaseTitle">Lepas aset {{ $releaseTrx->transaction_number }}</h5>
                        <button type="button" class="btn-close" wire:click="closeReleaseModal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">{{ $releaseTrx->items_count }} aset akan berstatus <strong>Delivery</strong> sampai diterima site tujuan. Unggah bukti pengiriman.</p>
                        <label for="releaseDocument" class="form-label">Bukti serah terima <span class="text-danger">*</span></label>
                        <input type="file" id="releaseDocument" wire:model="releaseDocument" accept=".pdf,.jpg,.jpeg,.png" class="form-control @error('releaseDocument') is-invalid @enderror">
                        <div wire:loading wire:target="releaseDocument" class="small text-muted mt-1"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Mengunggah...</div>
                        @error('releaseDocument') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted d-block mt-1">Surat jalan, resi, atau foto. PDF/JPG/PNG maks. 5 MB.</small>
                    </div>
                    <div class="modal-footer d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeReleaseModal">Batal</button>
                        <button type="button" wire:click="submitReleaseApproval" class="btn btn-success" wire:loading.attr="disabled" wire:target="submitReleaseApproval,releaseDocument">
                            <span wire:loading.remove wire:target="submitReleaseApproval"><i class="bx bx-package me-1"></i>Lepas aset</span>
                            <span wire:loading wire:target="submitReleaseApproval"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
