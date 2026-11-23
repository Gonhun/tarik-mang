@php
    $r = $assetRequest;
    $fmt = fn ($d, $f = 'D MMM YYYY') => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat($f) : '-';
    [$stageLabel, $step] = $stages[$r->status] ?? [$r->status, 1];
    $statusColor = match ($r->status) { 'Completed', 'Close' => 'success', 'Rejected' => 'danger', 'Pending IT Item Validation' => 'info', 'Pending Approval' => 'warning', default => 'primary' };
    $steps = ['Validasi IT', 'Persetujuan', 'Pembelian', 'Pengiriman / QC', 'Selesai'];
    $fulfillLabel = ['NEW_PURCHASE' => 'Beli baru', 'STOCK_LOCAL' => 'Stok di site', 'STOCK_MUTATION' => 'Stok site lain (mutasi)'];
    $canItDecide = in_array($r->status, ['Pending Approval', 'Pending IT Item Validation']) && $isItPic;
@endphp

<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div class="min-w-0">
            <a href="{{ route('asset-requests.dashboard') }}" class="small text-muted"><i class="bx bx-chevron-left"></i>Pengajuan aset</a>
            <h4 class="mb-1 mt-1 text-break">{{ $r->request_number }}</h4>
            <p class="text-muted mb-0">
                {{ $r->requester->name ?? '-' }} · {{ $r->requester->position->name ?? '-' }} · {{ $r->requester->department->code ?? '-' }} · {{ $r->jobsite->code ?? '-' }}
            </p>
            @if ($r->isSubmittedOnBehalf())
                <p class="small text-muted mb-0"><i class="bx bx-user-voice"></i> Diajukan oleh {{ $r->submitter->name ?? '-' }} atas nama {{ $r->requester->name ?? '-' }}</p>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-label-{{ $statusColor }} fs-6">{{ $stageLabel }}</span>
            <a href="{{ route('asset-requests.print', $r->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bx bx-printer me-1"></i>Cetak PDF</a>
        </div>
    </div>

    @if (session()->has('message'))<div class="alert alert-success alert-dismissible" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>@endif
    @if (session()->has('error'))<div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>@endif
    @if ($approvalProblem)
        <div class="alert alert-danger d-flex gap-2" role="alert"><i class="bx bx-error-alt fs-5 flex-shrink-0"></i><div><strong>Approval tidak bisa berjalan.</strong> {{ $approvalProblem }}</div></div>
    @endif

    {{-- Progres tahapan --}}
    <div class="card mb-4">
        <div class="card-body">
            @if ($r->status === 'Rejected')
                <div class="d-flex gap-2 text-danger"><i class="bx bx-x-circle fs-4"></i><div><strong>Pengajuan ditolak.</strong> {{ $r->final_rejection_notes }}</div></div>
            @else
                <div class="d-flex flex-wrap gap-3 justify-content-between">
                    @foreach ($steps as $i => $label)
                        @php $n = $i + 1; $done = $n < $step || $r->status === 'Completed'; $now = $n === $step && $r->status !== 'Completed'; @endphp
                        <div class="d-flex align-items-center gap-2 flex-fill" style="min-width: 130px">
                            <span class="avatar avatar-sm flex-shrink-0">
                                <span class="avatar-initial rounded-circle {{ $done ? 'bg-success' : ($now ? 'bg-label-warning' : 'bg-label-secondary') }}">
                                    @if ($done)<i class="bx bx-check"></i>@else{{ $n }}@endif
                                </span>
                            </span>
                            <span class="small {{ $now ? 'fw-semibold text-warning' : ($done ? 'fw-medium' : 'text-muted') }}">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="small text-muted mb-0 mt-3">
                    <i class="bx bx-info-circle"></i>
                    @switch($r->status)
                        @case('Pending IT Item Validation') Tim IT site sedang mengecek setiap item (stok atau beli baru). {{ $itPendingCount }} item belum divalidasi. @break
                        @case('Pending Approval') Menunggu persetujuan {{ $currentApproval->approver->name ?? $currentApproval->approver_role_name ?? '-' }} (tahap {{ $currentApproval->step_order ?? '-' }}). @break
                        @case('Pending PR & Accounting Number') Disetujui. Admin IT sedang memproses PR & nomor akunting. @break
                        @case('Pending PO') Menunggu nomor PO dari pembelian. @break
                        @case('Pending QC') Barang datang, sedang dicek (QC). @break
                        @case('Pending Asset Mutation') Aset dari stok site lain sedang dikirim. @break
                        @case('Completed') Pengajuan selesai. Aset sudah dipenuhi. @break
                        @default Status: {{ $r->status }}
                    @endswitch
                </p>
            @endif
        </div>
    </div>

    {{-- Giliran saya --}}
    @if ($myTurnApproval)
        <div class="alert alert-primary d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4" role="alert">
            <div>
                <i class="bx bx-bell me-1"></i><strong>Giliran Anda</strong> menyetujui sebagai {{ $myTurnApproval->approver_role_name }}.
                @if (auth()->id() != $myTurnApproval->approver_user_id)<span class="small d-block">Anda bertindak sebagai admin atas nama {{ $myTurnApproval->approver->name ?? '-' }}.</span>@endif
            </div>
            <div class="d-flex gap-2">
                <button wire:click="openOverallApprovalModal({{ $myTurnApproval->id }}, 'approve')" wire:loading.attr="disabled" wire:target="openOverallApprovalModal" class="btn btn-sm btn-success"><i class="bx bx-check me-1"></i>Setujui</button>
                <button wire:click="openOverallApprovalModal({{ $myTurnApproval->id }}, 'reject')" wire:loading.attr="disabled" wire:target="openOverallApprovalModal" class="btn btn-sm btn-outline-danger"><i class="bx bx-x me-1"></i>Tolak</button>
            </div>
        </div>
    @endif
    @if ($canItDecide && $itPendingCount > 0)
        <div class="alert alert-info d-flex gap-2 mb-4" role="alert"><i class="bx bx-check-shield fs-5"></i><div><strong>Validasi IT:</strong> tentukan pemenuhan untuk {{ $itPendingCount }} item di bawah (ambil dari stok atau beli baru).</div></div>
    @endif

    <div class="row g-4">
        {{-- Kiri: info --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header pb-2"><h6 class="mb-0">Informasi pengajuan</h6></div>
                <div class="card-body pt-2">
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted fw-normal">Diajukan</dt><dd class="col-7">{{ $fmt($r->request_date) }}</dd>
                        <dt class="col-5 text-muted fw-normal">Dibutuhkan</dt><dd class="col-7">{{ $fmt($r->needed_date) }}</dd>
                        <dt class="col-5 text-muted fw-normal">Site</dt><dd class="col-7">{{ $r->jobsite->name ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Total unit</dt><dd class="col-7">{{ $r->items->sum('quantity') }}</dd>
                        <dt class="col-12 text-muted fw-normal mt-2">Keperluan</dt>
                        <dd class="col-12 text-break" style="white-space: pre-line">{{ $r->purpose }}</dd>
                        @if ($r->remarks)
                            <dt class="col-12 text-muted fw-normal">Catatan</dt><dd class="col-12 text-break">{{ $r->remarks }}</dd>
                        @endif
                    </dl>
                    @if ($r->ba_damage_path || $r->ba_budget_outside_path)
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            @if ($r->ba_damage_path)
                                <a href="{{ asset('storage/' . $r->ba_damage_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bx bx-paperclip me-1"></i>BA kerusakan</a>
                            @endif
                            @if ($r->ba_budget_outside_path)
                                <a href="{{ asset('storage/' . $r->ba_budget_outside_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bx bx-paperclip me-1"></i>BA di luar budget</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Persetujuan --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center pb-2">
                    <h6 class="mb-0">Persetujuan</h6>
                    @if ($approvalsOrdered->isNotEmpty())
                        <small class="text-muted">{{ $approvalsOrdered->where('status', 'Approved')->count() }}/{{ $approvalsOrdered->count() }}</small>
                    @endif
                </div>
                <div class="card-body">
                    @if ($approvalsOrdered->isEmpty())
                        <p class="small text-muted mb-0">Belum ada tahapan persetujuan.</p>
                    @else
                        <ul class="list-unstyled mb-0 req-timeline">
                            @foreach ($approvalsOrdered as $ap)
                                @php
                                    $isCurrent = $currentApproval && $currentApproval->id === $ap->id;
                                    $isAuto = $ap->notes === \App\Support\Workflow::AUTO_NOTE;
                                    [$c, $icon, $label] = match ($ap->status) {
                                        'Approved' => ['success', 'bx-check', $isAuto ? 'Otomatis (pengaju)' : 'Disetujui'],
                                        'Rejected' => ['danger', 'bx-x', 'Ditolak'],
                                        default => $isCurrent ? ['warning', 'bx-time-five', 'Menunggu'] : ['secondary', 'bx-dots-horizontal-rounded', 'Belum giliran'],
                                    };
                                @endphp
                                <li class="req-timeline-item" wire:key="ap-{{ $ap->id }}">
                                    <span class="req-timeline-dot bg-label-{{ $c }}"><i class="bx {{ $icon }}"></i></span>
                                    <div class="d-flex justify-content-between gap-2">
                                        <div class="min-w-0">
                                            <span class="fw-semibold small d-block">{{ $ap->approver_role_name }}</span>
                                            <span class="small text-muted">{{ $ap->approver->name ?? 'Approver belum ditentukan' }}</span>
                                        </div>
                                        <span class="badge bg-label-{{ $c }} align-self-start">{{ $label }}</span>
                                    </div>
                                    @if ($ap->action_at)
                                        <small class="d-block text-muted">{{ $fmt($ap->action_at, 'D MMM YYYY HH:mm') }}{{ $ap->actionBy && $ap->actionBy->id != $ap->approver_user_id ? ' · oleh ' . $ap->actionBy->name : '' }}</small>
                                    @endif
                                    @if ($ap->notes)
                                        <p class="small border-start border-3 ps-2 mt-1 mb-0" style="white-space: pre-line">{{ $ap->notes }}</p>
                                    @endif
                                    @if ($ap->status === 'Pending')
                                        <div class="d-flex flex-wrap gap-2 mt-1">
                                            @if ($isCurrent && $ap->approver && $ap->approver_user_id != auth()->id())
                                                <button type="button" wire:click="SendWa({{ $ap->id }})" wire:loading.attr="disabled" wire:target="SendWa({{ $ap->id }})" class="btn btn-sm btn-link px-0">
                                                    <span wire:loading.remove wire:target="SendWa({{ $ap->id }})"><i class="bx bx-envelope"></i> Ingatkan via email</span>
                                                    <span wire:loading wire:target="SendWa({{ $ap->id }})"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                                </button>
                                            @endif
                                            @if ($this->isAdmin)
                                                <button type="button" wire:click="openModalUpdatePic({{ $ap->id }})" class="btn btn-sm btn-link px-0"><i class="bx bx-user-pin"></i> Ganti approver</button>
                                            @endif
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Kanan: item --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center pb-2">
                    <h6 class="mb-0">Item yang diajukan</h6>
                    <span class="badge bg-label-secondary">{{ $r->items->count() }} item · {{ $r->items->sum('quantity') }} unit</span>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($r->items as $item)
                        @php
                            $decision = $itemDecisions[$item->id] ?? null;
                            $stock = $availableStockAssets[$item->id] ?? collect();
                            $itColor = match ($item->it_approval_status) { 'Approved' => 'success', 'Rejected' => 'danger', default => 'warning' };
                        @endphp
                        <div class="list-group-item py-3" wire:key="item-approval-{{ $item->id }}">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div class="min-w-0">
                                    <span class="fw-semibold">{{ $loop->iteration }}. {{ $item->assetSeries->name ?? ($item->assetBrand->name ?? $item->assetType->name ?? '-') }}</span>
                                    <span class="badge bg-label-{{ $item->is_standard ? 'success' : 'warning' }} ms-1">{{ $item->is_standard ? 'Standar' : 'Tambahan' }}</span>
                                    <span class="d-block small text-muted">{{ $item->assetType->name ?? '-' }} · {{ $item->assetBrand->name ?? '-' }} · {{ $item->quantity }} unit</span>
                                    @if ($item->item_description)<span class="d-block small">{{ $item->item_description }}</span>@endif
                                </div>
                                <span class="badge bg-label-{{ $itColor }}">IT: {{ $item->it_approval_status === 'Pending' ? 'Menunggu' : ($item->it_approval_status === 'Approved' ? 'Divalidasi' : $item->it_approval_status) }}</span>
                            </div>

                            @if ($canItDecide && $item->it_approval_status === 'Pending' && $decision)
                                {{-- Keputusan IT --}}
                                <div class="border rounded p-3 mt-3 bg-lighter">
                                    <div class="d-flex flex-wrap gap-2 mb-2" role="radiogroup" aria-label="Pemenuhan item {{ $loop->iteration }}">
                                        <input type="radio" class="btn-check" id="ful_new_{{ $item->id }}" value="new" wire:model.live="itemDecisions.{{ $item->id }}.fulfillment_type">
                                        <label class="btn btn-sm btn-outline-primary" for="ful_new_{{ $item->id }}"><i class="bx bx-cart me-1"></i>Beli baru</label>
                                        <input type="radio" class="btn-check" id="ful_stock_{{ $item->id }}" value="stock" wire:model.live="itemDecisions.{{ $item->id }}.fulfillment_type" @disabled(!$decision['has_stock'])>
                                        <label class="btn btn-sm btn-outline-primary" for="ful_stock_{{ $item->id }}"><i class="bx bx-package me-1"></i>Ambil dari stok ({{ $stock->count() }})</label>
                                    </div>

                                    @if ($decision['fulfillment_type'] === 'stock')
                                        <label class="form-label small mb-1" for="stock_{{ $item->id }}">Pilih aset stok <span class="text-danger">*</span></label>
                                        <select id="stock_{{ $item->id }}" wire:model="itemDecisions.{{ $item->id }}.selected_stock_asset_id" class="form-select form-select-sm @error('itemDecisions.'.$item->id.'.selected_stock_asset_id') is-invalid @enderror">
                                            <option value="">-- Pilih --</option>
                                            @foreach ($stock as $sa)
                                                <option value="{{ $sa->id }}">{{ $sa->asset_number }} · {{ $sa->assetSeries->name ?? '-' }} · {{ $sa->jobsite->code ?? '-' }}{{ $sa->jobsite_id == $r->jobsite_id ? ' (site sama)' : ' (perlu mutasi)' }}</option>
                                            @endforeach
                                        </select>
                                        @error('itemDecisions.'.$item->id.'.selected_stock_asset_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @elseif ($decision['has_stock'])
                                        <label class="form-label small mb-1 text-danger" for="why_{{ $item->id }}">Ada stok tersedia — alasan beli baru <span class="text-danger">*</span></label>
                                        <input id="why_{{ $item->id }}" type="text" wire:model="itemDecisions.{{ $item->id }}.notes" class="form-control form-control-sm @error('itemDecisions.'.$item->id.'.notes') is-invalid @enderror" placeholder="Mis. spesifikasi stok tidak sesuai...">
                                        @error('itemDecisions.'.$item->id.'.notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @else
                                        <p class="small text-muted mb-0">Tidak ada stok Free yang cocok, item akan dibeli baru.</p>
                                    @endif

                                    <div class="text-end mt-2">
                                        <button wire:click="saveItemItDecision({{ $item->id }})" wire:loading.attr="disabled" wire:target="saveItemItDecision({{ $item->id }})" class="btn btn-sm btn-primary">
                                            <span wire:loading.remove wire:target="saveItemItDecision({{ $item->id }})">Simpan keputusan</span>
                                            <span wire:loading wire:target="saveItemItDecision({{ $item->id }})"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                        </button>
                                    </div>
                                </div>
                            @elseif ($item->it_approval_status !== 'Pending')
                                <div class="small mt-2">
                                    <span class="text-muted">Pemenuhan:</span> <strong>{{ $fulfillLabel[$item->fulfillment_type] ?? $item->fulfillment_type ?? '-' }}</strong>
                                    @if ($item->assignedAsset)
                                        · aset <a href="{{ route('assets.show', $item->assignedAsset->asset_number) }}">{{ $item->assignedAsset->asset_number }}</a> ({{ $item->assignedAsset->jobsite->code ?? '-' }})
                                    @endif
                                    @if ($item->it_approval_notes)<span class="d-block"><span class="text-muted">Catatan IT:</span> {{ $item->it_approval_notes }}</span>@endif
                                </div>
                            @endif

                            @if ($item->pr_number || $item->po_number || $item->accounting_number)
                                <div class="small text-muted mt-2">PR {{ $item->pr_number ?: '-' }} · Akunting {{ $item->accounting_number ?: '-' }} · PO {{ $item->po_number ?: '-' }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Modal persetujuan --}}
    @if ($isOverallApprovalModalOpen)
        @php $isApprove = $overallApprovalAction === 'approve'; @endphp
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="reqApprovalTitle" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reqApprovalTitle">{{ $isApprove ? 'Setujui' : 'Tolak' }} pengajuan {{ $r->request_number }}</h5>
                        <button type="button" class="btn-close" wire:click="closeOverallApprovalModal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">{{ $r->requester->name ?? '-' }} · {{ $r->items->count() }} item · {{ $r->items->sum('quantity') }} unit</p>
                        @unless ($isApprove)
                            <div class="alert alert-warning small py-2">Penolakan menghentikan seluruh pengajuan dan pemohon diberi tahu.</div>
                        @endunless
                        <label for="overall_approval_notes" class="form-label">Catatan @if (!$isApprove)<span class="text-danger">* (min. 10 karakter)</span>@else<span class="text-muted fw-normal">(opsional)</span>@endif</label>
                        <textarea wire:model="overallApprovalNotes" id="overall_approval_notes" rows="4" class="form-control @error('overallApprovalNotes') is-invalid @enderror"></textarea>
                        @error('overallApprovalNotes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="modal-footer d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeOverallApprovalModal">Batal</button>
                        <button type="button" wire:click="submitOverallApproval" class="btn btn-{{ $isApprove ? 'success' : 'danger' }}" wire:loading.attr="disabled" wire:target="submitOverallApproval">
                            <span wire:loading.remove wire:target="submitOverallApproval">{{ $isApprove ? 'Setujui' : 'Tolak' }}</span>
                            <span wire:loading wire:target="submitOverallApproval"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal ganti approver (admin) --}}
    @if ($this->isUpdatePIcModalOpen)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="picTitle" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="picTitle">Ganti approver</h5>
                        <button type="button" class="btn-close" wire:click="closeModalUpdatePic()" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <label for="siteToUpdate" class="form-label">Site</label>
                        <select wire:model.live="siteToUpdate" id="siteToUpdate" class="form-select mb-3 @error('siteToUpdate') is-invalid @enderror">
                            <option value="">-- Pilih site --</option>
                            @foreach ($site_option as $code => $id)
                                <option value="{{ $id }}">{{ $code }}</option>
                            @endforeach
                        </select>
                        <label for="picToUpdate" class="form-label">Approver baru</label>
                        <div class="position-relative">
                            <select wire:model="picToUpdate" id="picToUpdate" class="form-select @error('picToUpdate') is-invalid @enderror" @disabled(!$siteToUpdate)>
                                <option value="">{{ $siteToUpdate ? '-- Pilih orang --' : 'Pilih site dulu' }}</option>
                                @foreach ($nik_option as $display)
                                    <option value="{{ $display['id'] }}">{{ $display['nik'] . ' - ' . $display['name'] }}</option>
                                @endforeach
                            </select>
                            <span wire:loading wire:target="siteToUpdate" class="spinner-border spinner-border-sm text-primary position-absolute" style="right: 2.5rem; top: .7rem" role="status"></span>
                            @error('picToUpdate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeModalUpdatePic()">Batal</button>
                        <button type="button" wire:click="submitUpdatePic" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submitUpdatePic">
                            <span wire:loading.remove wire:target="submitUpdatePic">Simpan</span>
                            <span wire:loading wire:target="submitUpdatePic"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        .req-timeline-item { position: relative; padding: 0 0 1.1rem 2.5rem; }
        .req-timeline-item::before { content: ''; position: absolute; left: .9rem; top: 2rem; bottom: .2rem; width: 2px; background: var(--bs-border-color, #e9ecef); }
        .req-timeline-item:last-child { padding-bottom: 0; }
        .req-timeline-item:last-child::before { display: none; }
        .req-timeline-dot { position: absolute; left: 0; top: 0; width: 1.85rem; height: 1.85rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    </style>
</div>
