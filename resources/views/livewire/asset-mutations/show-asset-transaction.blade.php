@php
    $t = $transaction;
    $type = $t->transaction_type;
    $isLost = $type === 'DISPOSED' && str_starts_with((string) $t->notes, '[Kehilangan]');
    $typeLabel = match (true) {
        $isLost => 'Kehilangan aset',
        $type === 'DISPOSED' => 'Disposal aset',
        $type === 'MUTATION' => 'Mutasi aset',
        $type === 'HANDOVER' => 'Serah terima (BAST)',
        $type === 'RETURN' => 'Pengembalian aset',
        default => \Illuminate\Support\Str::title(str_replace('_', ' ', $type)),
    };
    $statusColor = match ($t->status) {
        'Completed' => 'success', 'Rejected', 'Cancelled' => 'danger', 'Delivery' => 'primary', 'Pending Release' => 'info', default => 'warning',
    };
    $fmt = fn ($d, $f = 'D MMM YYYY') => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat($f) : '-';
    $notes = trim(preg_replace('/^\[[^\]]+\]\s*/', '', (string) $t->notes));
    $assetStatusColor = ['Free' => 'success', 'Used' => 'primary', 'Pre-Used' => 'info', 'Repair' => 'warning', 'Pending Mutation' => 'info', 'Delivery' => 'primary', 'Missing' => 'danger', 'Pre Disposed' => 'secondary', 'Disposed' => 'dark'];

    // Tahapan alur untuk mutasi (stepper)
    $mutationSteps = [
        ['Diajukan', true],
        ['Persetujuan', in_array($t->status, ['Pending Release', 'Delivery', 'Completed'])],
        ['Pelepasan site asal', in_array($t->status, ['Delivery', 'Completed'])],
        ['Diterima site tujuan', $t->status === 'Completed'],
    ];
    $backRoute = match ($type) { 'MUTATION' => route('asset.mutasi'), 'DISPOSED' => route('asset.disposed'), default => route('asset.index') };
@endphp

<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div class="min-w-0">
            <a href="{{ $backRoute }}" class="small text-muted"><i class="bx bx-chevron-left"></i>Kembali ke daftar</a>
            <h4 class="mb-1 mt-1 text-break">{{ $t->transaction_number }}</h4>
            <p class="text-muted mb-0">{{ $typeLabel }} · {{ $fmt($t->transaction_date, 'dddd, D MMMM YYYY') }} · dibuat {{ $t->creator->name ?? '-' }}</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-label-{{ $statusColor }} fs-6">{{ $t->status }}</span>
            @if ($type === 'MUTATION')
                <a href="{{ route('asset.mutasi.pdf', $t->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bx bx-printer me-1"></i>Cetak BA mutasi</a>
            @endif
        </div>
    </div>

    @if ($approvalProblem)
        <div class="alert alert-danger d-flex gap-2 mb-4" role="alert">
            <i class="bx bx-error-alt fs-4 flex-shrink-0"></i>
            <div><strong>Approval tidak bisa berjalan.</strong> {{ $approvalProblem }}</div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>
    @endif
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>
    @endif

    {{-- Giliran saya --}}
    @if ($myTurnApprovalId)
        <div class="alert alert-primary d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4" role="alert">
            <div><i class="bx bx-bell me-1"></i><strong>Giliran Anda</strong> menyetujui sebagai {{ $currentApproval->approver_role_name }}.</div>
            <div class="d-flex gap-2">
                <button wire:click="openApprovalModal({{ $myTurnApprovalId }}, 'approve')" wire:loading.attr="disabled" wire:target="openApprovalModal" class="btn btn-sm btn-success"><i class="bx bx-check me-1"></i>Setujui</button>
                <button wire:click="openApprovalModal({{ $myTurnApprovalId }}, 'reject')" wire:loading.attr="disabled" wire:target="openApprovalModal" class="btn btn-sm btn-outline-danger"><i class="bx bx-x me-1"></i>Tolak</button>
            </div>
        </div>
    @endif

    {{-- Stepper mutasi --}}
    @if ($type === 'MUTATION' && !in_array($t->status, ['Rejected', 'Cancelled']))
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 justify-content-between">
                    @foreach ($mutationSteps as $i => [$label, $done])
                        @php $isNext = !$done && ($i === 0 || $mutationSteps[$i - 1][1]); @endphp
                        <div class="d-flex align-items-center gap-2 flex-fill" style="min-width: 150px">
                            <span class="avatar avatar-sm flex-shrink-0">
                                <span class="avatar-initial rounded-circle {{ $done ? 'bg-success' : ($isNext ? 'bg-label-warning' : 'bg-label-secondary') }}">
                                    @if ($done)<i class="bx bx-check"></i>@else{{ $i + 1 }}@endif
                                </span>
                            </span>
                            <span class="small {{ $done ? 'fw-medium' : ($isNext ? 'fw-semibold text-warning' : 'text-muted') }}">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
                @if ($t->status === 'Pending Release')
                    <p class="small text-muted mb-0 mt-3"><i class="bx bx-info-circle"></i> Menunggu site {{ $t->fromJobsite->code ?? 'asal' }} melepas aset (unggah bukti kirim) di <a href="{{ route('asset.mutasi', ['q' => $t->transaction_number]) }}">halaman Mutasi</a>.</p>
                @elseif ($t->status === 'Delivery')
                    <p class="small text-muted mb-0 mt-3"><i class="bx bx-info-circle"></i> Aset dalam pengiriman. Site {{ $t->toJobsite->code ?? 'tujuan' }} perlu konfirmasi terima di <a href="{{ route('asset.mutasi', ['q' => $t->transaction_number]) }}">halaman Mutasi</a>.</p>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-4">
        {{-- Kiri: info transaksi --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header pb-2"><h6 class="mb-0">Informasi</h6></div>
                <div class="card-body pt-2">
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted fw-normal">Jenis</dt><dd class="col-7">{{ $typeLabel }}</dd>
                        <dt class="col-5 text-muted fw-normal">Tanggal</dt><dd class="col-7">{{ $fmt($t->transaction_date) }}</dd>
                        <dt class="col-5 text-muted fw-normal">Dibuat oleh</dt><dd class="col-7">{{ $t->creator->name ?? '-' }}</dd>
                        @if ($type === 'MUTATION')
                            <dt class="col-5 text-muted fw-normal">Dari site</dt>
                            <dd class="col-7">{{ $t->fromJobsite->name ?? '-' }}<small class="d-block text-muted">CC {{ $fromCostCenter->cost_center_number ?? '-' }}</small></dd>
                            <dt class="col-5 text-muted fw-normal">Ke site</dt>
                            <dd class="col-7">{{ $t->toJobsite->name ?? '-' }}<small class="d-block text-muted">CC {{ $destinationCostCenter->cost_center_number ?? '-' }}</small></dd>
                        @elseif (in_array($type, ['HANDOVER', 'RETURN']))
                            <dt class="col-5 text-muted fw-normal">{{ $type === 'HANDOVER' ? 'Pemberi' : 'Dikembalikan oleh' }}</dt><dd class="col-7">{{ $t->fromUser->name ?? '-' }}</dd>
                            <dt class="col-5 text-muted fw-normal">{{ $type === 'HANDOVER' ? 'Penerima' : 'Diinspeksi oleh' }}</dt><dd class="col-7">{{ $t->toUser->name ?? '-' }}</dd>
                        @elseif ($type === 'DISPOSED')
                            <dt class="col-5 text-muted fw-normal">Alasan</dt><dd class="col-7"><span class="badge bg-label-{{ $isLost ? 'danger' : 'secondary' }}">{{ $isLost ? 'Kehilangan' : 'Rusak / lainnya' }}</span></dd>
                            <dt class="col-5 text-muted fw-normal">Pemegang terakhir</dt><dd class="col-7">{{ $t->fromUser->name ?? '-' }}</dd>
                            <dt class="col-5 text-muted fw-normal">Site</dt><dd class="col-7">{{ $t->fromJobsite->name ?? '-' }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header pb-2"><h6 class="mb-0">{{ $isLost ? 'Kronologi' : 'Catatan' }} & dokumen</h6></div>
                <div class="card-body pt-2 small">
                    <p class="mb-3 text-break" style="white-space: pre-line">{{ $notes ?: 'Tidak ada catatan.' }}</p>
                    @if ($t->document_path)
                        <a href="{{ Storage::url($t->document_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bx bx-paperclip me-1"></i>{{ $t->document_name ?: 'Lihat dokumen' }}</a>
                    @else
                        <span class="text-muted">Tidak ada dokumen pendukung.</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Kanan: aset & approval --}}
        <div class="col-lg-8">
            {{-- Aset --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center pb-2">
                    <h6 class="mb-0">Aset dalam transaksi</h6>
                    <span class="badge bg-label-secondary">{{ $t->items->count() }} aset</span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($t->items as $item)
                        @php
                            $a = $item->asset;
                            $meta = is_array($item->metadata) ? $item->metadata : [];
                        @endphp
                        <div class="list-group-item py-3" wire:key="item-{{ $item->id }}">
                            @if ($a)
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                    <div class="min-w-0">
                                        <a href="{{ route('assets.show', $a->asset_number) }}" class="fw-semibold">{{ $a->asset_number }}</a>
                                        <span class="badge bg-label-{{ $assetStatusColor[$a->status] ?? 'secondary' }} ms-1">{{ $a->status }}</span>
                                        <span class="d-block">{{ $a->assetType->name ?? '-' }} · {{ $a->assetBrand->name ?? '-' }} {{ $a->assetSeries->name ?? '' }}</span>
                                    </div>
                                    @if (in_array($type, ['HANDOVER', 'RETURN', 'MUTATION']))
                                        <a href="{{ route('asset.ba.print', ['transaction' => $t->id, 'asset' => $a->id]) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bx bx-printer me-1"></i>Cetak BA</a>
                                    @endif
                                </div>
                                <div class="row g-2 small text-muted mt-1">
                                    <div class="col-6 col-md-3"><span class="d-block">Serial number</span><span class="text-body text-break">{{ $a->serial_number ?: '-' }}</span></div>
                                    <div class="col-6 col-md-3"><span class="d-block">Site sekarang</span><span class="text-body">{{ $a->jobsite->code ?? '-' }}</span></div>
                                    <div class="col-6 col-md-3"><span class="d-block">Pemegang sekarang</span><span class="text-body">{{ $a->holder->name ?? '-' }}</span></div>
                                    <div class="col-6 col-md-3"><span class="d-block">Status sebelum</span><span class="text-body">{{ $meta['previous_status'] ?? '-' }}</span></div>
                                </div>
                                @if ($item->condition_notes)
                                    <p class="small mb-0 mt-2"><span class="text-muted">Kondisi:</span> {{ $item->condition_notes }}</p>
                                @endif
                            @else
                                <span class="text-danger small">Aset #{{ $item->asset_id }} tidak ditemukan.</span>
                            @endif
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Tidak ada aset dalam transaksi ini.</div>
                    @endforelse
                </div>
            </div>

            {{-- Admin: perbaiki approval transaksi yang sudah berjalan --}}
            @if ($canRepair && ($repairPlan || $repairPlanError))
                <div class="card mb-4 border border-{{ $approvalProblem ? 'danger' : 'secondary' }}">
                    <div class="card-header pb-2">
                        <h6 class="mb-0"><i class="bx bx-wrench me-1"></i>Perbaiki approval (admin)</h6>
                        <small class="text-muted">Belum ada tahap yang diputuskan, jadi tahapan bisa dibuat ulang dari workflow yang berlaku sekarang.</small>
                    </div>
                    <div class="card-body">
                        @if ($repairPlanError)
                            <div class="alert alert-danger small mb-0 d-flex gap-2"><i class="bx bx-error-alt fs-5"></i><span>{{ $repairPlanError }}</span></div>
                        @else
                            <p class="small mb-2">Tahapan baru yang akan dibuat:</p>
                            <ol class="small mb-3 ps-3">
                                @foreach ($repairPlan as $p)
                                    <li><strong>{{ $p['user']->name }}</strong> <span class="text-muted">· {{ $p['role'] }}{{ $p['user']->whatsapp ? '' : ' · WA kosong' }}</span></li>
                                @endforeach
                            </ol>
                            <button type="button" class="btn btn-sm btn-primary" wire:click="applyCurrentWorkflow"
                                wire:confirm="Ganti {{ $approvals->count() }} tahap lama dengan {{ count($repairPlan) }} tahap dari workflow saat ini? Approver pertama akan diberi notifikasi."
                                wire:loading.attr="disabled" wire:target="applyCurrentWorkflow">
                                <span wire:loading.remove wire:target="applyCurrentWorkflow"><i class="bx bx-refresh me-1"></i>Terapkan workflow sekarang</span>
                                <span wire:loading wire:target="applyCurrentWorkflow"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Menerapkan...</span>
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Approval --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center pb-2">
                    <h6 class="mb-0">Progres persetujuan</h6>
                    @if ($approvals->isNotEmpty())
                        <small class="text-muted">{{ $approvals->where('status', 'Approved')->count() }}/{{ $approvals->count() }} disetujui</small>
                    @endif
                </div>
                <div class="card-body">
                    @if ($approvals->isEmpty())
                        <p class="text-muted small mb-0">Transaksi ini tidak memiliki tahapan persetujuan.</p>
                    @else
                        <ul class="list-unstyled mb-0 trx-timeline">
                            @foreach ($approvals as $ap)
                                @php
                                    $isCurrent = $currentApproval && $currentApproval->id === $ap->id;
                                    $isAuto = $ap->notes === \App\Support\Workflow::AUTO_NOTE;
                                    [$c, $icon, $label] = match ($ap->status) {
                                        'Approved' => ['success', 'bx-check', $isAuto ? 'Otomatis (pengaju)' : 'Disetujui'],
                                        'Rejected' => ['danger', 'bx-x', 'Ditolak'],
                                        default => $isCurrent ? ['warning', 'bx-time-five', 'Menunggu'] : ['secondary', 'bx-dots-horizontal-rounded', 'Belum giliran'],
                                    };
                                @endphp
                                <li class="trx-timeline-item {{ $isCurrent ? 'is-current' : '' }}">
                                    <span class="trx-timeline-dot bg-label-{{ $c }}"><i class="bx {{ $icon }}"></i></span>
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <div>
                                            <span class="fw-semibold">Tahap {{ $ap->step_order }} · {{ $ap->approver_role_name }}</span>
                                            <span class="d-block small text-muted">{{ $ap->approver->name ?? 'Approver belum ditentukan' }}</span>
                                        </div>
                                        <span class="badge bg-label-{{ $c }} align-self-start">{{ $label }}</span>
                                    </div>
                                    @if ($ap->action_at)
                                        <small class="d-block text-muted mt-1">{{ $fmt($ap->action_at, 'D MMM YYYY, HH:mm') }}{{ $ap->actionBy && $ap->actionBy->id !== $ap->approver_user_id ? ' · oleh ' . $ap->actionBy->name : '' }}</small>
                                    @endif
                                    @if ($ap->notes)
                                        <p class="small border-start border-3 ps-2 mt-2 mb-0" style="white-space: pre-line">{{ $ap->notes }}</p>
                                    @endif

                                    {{-- Admin: ganti approver tahap yang masih menunggu --}}
                                    @if ($canRepair && $ap->status === 'Pending')
                                        @if ($reassignApprovalId === $ap->id)
                                            <div class="border rounded p-2 mt-2 bg-lighter">
                                                <label class="form-label small mb-1" for="reassign_{{ $ap->id }}">Approver pengganti tahap {{ $ap->step_order }}</label>
                                                @if ($reassignUser)
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="form-control">{{ $reassignUser->name }} <small class="text-muted">({{ $reassignUser->nik }})</small></span>
                                                        <button type="button" class="btn btn-outline-secondary" wire:click="$set('reassignUserId', null)">Ganti</button>
                                                    </div>
                                                @else
                                                    <input type="search" id="reassign_{{ $ap->id }}" wire:model.live.debounce.400ms="reassignSearch" class="form-control form-control-sm" placeholder="Ketik nama / NIK (min. 2 huruf)">
                                                    @if ($reassignOptions->isNotEmpty())
                                                        <div class="list-group mt-1 shadow-sm">
                                                            @foreach ($reassignOptions as $u)
                                                                <button type="button" class="list-group-item list-group-item-action py-1 small" wire:click="chooseReassignUser({{ $u->id }})">{{ $u->name }} <span class="text-muted">· {{ $u->nik }} · {{ $u->jobsite->code ?? '-' }}</span></button>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @endif
                                                @error('reassignUserId') <div class="text-danger small">{{ $message }}</div> @enderror
                                                <div class="d-flex gap-2 mt-2">
                                                    <button type="button" class="btn btn-sm btn-primary" wire:click="submitReassign" wire:loading.attr="disabled" wire:target="submitReassign" @disabled(!$reassignUser)>
                                                        <span wire:loading.remove wire:target="submitReassign">Simpan</span>
                                                        <span wire:loading wire:target="submitReassign"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="closeReassign">Batal</button>
                                                </div>
                                            </div>
                                        @else
                                            <button type="button" class="btn btn-sm btn-link px-0 mt-1" wire:click="openReassign({{ $ap->id }})">
                                                <i class="bx bx-user-pin"></i> {{ $ap->approver_user_id ? 'Ganti approver' : 'Tentukan approver' }}
                                            </button>
                                        @endif
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal approval --}}
    @if ($isApprovalModalOpen)
        @php $isApprove = $approval_action === 'approve'; @endphp
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="approvalTitle" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approvalTitle">{{ $isApprove ? 'Setujui' : 'Tolak' }} {{ strtolower($typeLabel) }}</h5>
                        <button type="button" class="btn-close" wire:click="closeApprovalModal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="border rounded p-3 small mb-3">
                            <span class="fw-semibold d-block">{{ $t->transaction_number }}</span>
                            <span class="text-muted">
                                {{ $t->items->count() }} aset
                                @if ($type === 'MUTATION') · {{ $t->fromJobsite->code ?? '-' }} (CC {{ $fromCostCenter->cost_center_number ?? '-' }}) → {{ $t->toJobsite->code ?? '-' }} (CC {{ $destinationCostCenter->cost_center_number ?? '-' }}) @endif
                            </span>
                            @if ($modalApproval)
                                <span class="d-block text-muted">Sebagai: tahap {{ $modalApproval->step_order }} · {{ $modalApproval->approver_role_name }}</span>
                            @endif
                        </div>
                        @if (!$isApprove)
                            <div class="alert alert-warning small py-2">Penolakan menghentikan transaksi. Status aset dikembalikan seperti sebelum diajukan.</div>
                        @endif
                        <label for="approval_notes" class="form-label">Catatan @if (!$isApprove)<span class="text-danger">* (min. 10 karakter)</span>@else<span class="text-muted fw-normal">(opsional)</span>@endif</label>
                        <textarea wire:model="approval_notes" id="approval_notes" rows="4" class="form-control @error('approval_notes') is-invalid @enderror"></textarea>
                        @error('approval_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="modal-footer d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeApprovalModal">Batal</button>
                        <button type="button" wire:click="submitApproval" class="btn btn-{{ $isApprove ? 'success' : 'danger' }}" wire:loading.attr="disabled" wire:target="submitApproval">
                            <span wire:loading.remove wire:target="submitApproval">{{ $isApprove ? 'Setujui' : 'Tolak' }}</span>
                            <span wire:loading wire:target="submitApproval"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        .trx-timeline-item { position: relative; padding: 0 0 1.25rem 2.75rem; }
        .trx-timeline-item::before { content: ''; position: absolute; left: 1rem; top: 2.1rem; bottom: .25rem; width: 2px; background: var(--bs-border-color, #e9ecef); }
        .trx-timeline-item:last-child { padding-bottom: 0; }
        .trx-timeline-item:last-child::before { display: none; }
        .trx-timeline-dot { position: absolute; left: 0; top: 0; width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .trx-timeline-item.is-current .trx-timeline-dot { box-shadow: 0 0 0 4px rgba(255, 171, 0, .2); }
    </style>
</div>
