<div>
    <div class="row pt-3">
        <div class="col-12 mb-3">
            <div class="card shadow-sm border border-light">
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="text-uppercase text-muted fw-bold"
                                    style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                    <th class="ps-4" style="width: 15%">No Pengajuan</th>
                                    <th style="width: 15%">Tgl Pengajuan</th>
                                    {{-- <th style="width: 10%">Status</th> --}}
                                    <th style="width: 45%">Detail Item</th>
                                    <th style="width: 15%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->dataRequestess() as $request)
                                    <tr wire:key="detail-{{ $request->request_no }}">
                                        <td class="ps-4">
                                            <span class="fw-bold text-primary">{{ $request->request_no }}</span>
                                        </td>
                                        <td>
                                            <div class="text-secondary small">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                {{ $request->created_at->format('Y-m-d') }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-2 py-2">
                                                @foreach ($request->items as $item)
                                                    <div class="p-2 border rounded bg-light-subtle">
                                                        <div class="d-flex justify-content-between align-items-start border-bottom pb-1 mb-1">
                                                            <div>
                                                                <div class="fw-bold small text-dark">
                                                                    {{ $item->software_name }}
                                                                </div>
                                                                <div class="text-muted" style="font-size: 0.7rem;">
                                                                    Category:
                                                                    {{ $item->softwareCategory->name }}
                                                                </div>
                                                            </div>
                                                            <div class="text-end">
                                                                <span
                                                                    class="badge bg-white text-dark border fw-normal">{{ $item->quantity }}
                                                                    Unit&nbsp;|&nbsp;
                                                                    <button class="btn btn-secondary text-white btn-sm"
                                                                        wire:click="showMyHistory({{ $item->id }})"
                                                                        title="Lihat riwayat status">
                                                                        <i class="bi bi-clock-history text-lg"></i>
                                                                    </button>&nbsp;|&nbsp;
                                                                    @if(isset($item))
                                                                        @if($item->approvals->where('status', 'Rejected')->first())
                                                                            <span class="badge bg-danger">Rejected</span>
                                                                        @elseif($item->approvals->where('user_id', Auth::id())->where('status', 'Approved')->first())
                                                                            <span class="badge bg-success">Approved</span>
                                                                        @elseif($item->where('status', 'Canceled')->first())
                                                                            <span class="badge bg-secondary">Canceled</span>
                                                                        @else
                                                                            @if ($item->approvals->where('user_id', Auth::id())->where('status', 'Pending')->first())
                                                                                <span class="badge bg-warning">Pending</span>
                                                                            @endif
                                                                        @endif
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center small">
                                                        <div>
                                                            <span class="text-muted">Harga:</span>
                                                            <span class="fw-bold text-dark">Rp
                                                                {{ number_format(($item->price), 0, ',', '.') }}</span>
                                                        </div>
                                                        <div>
                                                            <span class="text-muted">Total:</span>
                                                            <span class="fw-bold text-dark">Rp
                                                                {{ number_format(($item->price * $item->quantity), 0, ',', '.') }}</span>
                                                        </div>
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            <i class="bi bi-tag me-1"></i>No.
                                                            Acc:
                                                            <span class="text-dark">{{ $item->no_accounting ?? '-' }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('licenses.pdf', $request->id) }}" target="_blank"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-custom-class="custom-tooltip"
                                                data-bs-title="Print Berita Acara {{ $request->request_no }}"
                                                class="btn btn-outline-danger btn-sm">
                                                <i class="bx bx-bxs-file-pdf"></i>
                                            </a>
                                            <button wire:click="openMyDetailModal({{ $request->id }})"
                                                class="btn btn-outline-info btn-sm">
                                                <i class="bx bx-search"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <div class="alert alert-warning" role="alert">
                                                <strong>Informasi!</strong> Belum ada pengajuan lisensi dari Anda.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3">
                    {{ $this->dataRequestApproves()->links() }}
                </div>
            </div>
        </div>
    </div>
    
    @if($openModal)
        <div id="bastModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form wire:submit.prevent="submitBast">
                        <div class="modal-header">
                            <h5 class="modal-title">Detail</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            {{--
                            Detail validasi sampai mana dalam proses approval/pengajuan lisensi.
                            Misal: Dokumen, atasan, IT, atau step lain.
                            Contoh di bawah ini asumsikan ada data 'item' yang sedang dilihat detailnya.
                            --}}

                            @if(isset($item_req))
                            {{-- @dd($item_req) --}}
                                <ul class="list-group mb-3">
                                    <li class="list-group-item">
                                        <strong>Nama Lisensi:</strong> {{ $item_req->software_name ?? '-' }}
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Pemohon:</strong> {{ $item_req->request->user->name ?? '-' }}
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Tanggal Pengajuan:</strong>
                                        {{ $item_req->created_at ? $item_req->created_at->format('Y-m-d') : '-' }}
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Status Sekarang:</strong> {{ $item_req->status ?? '-' }}
                                    </li>
                                </ul>
                                <style>
                                    ul.timeline-approval {
                                        list-style: none;
                                        padding-left: 0;
                                        position: relative;
                                    }

                                    ul.timeline-approval:before {
                                        content: "";
                                        background: #e7eaf0;
                                        display: block;
                                        position: absolute;
                                        left: 28px;
                                        width: 2px;
                                        height: 100%;
                                        z-index: 0;
                                    }

                                    ul.timeline-approval>li {
                                        position: relative;
                                        margin: 25px 0 25px 0;
                                        padding-left: 45px;
                                        min-height: 48px;
                                    }

                                    ul.timeline-approval>li:before {
                                        content: "";
                                        position: absolute;
                                        left: 15px;
                                        top: 0;
                                        width: 26px;
                                        height: 26px;
                                        border-radius: 50%;
                                        background-color: #fff;
                                        border: 3px solid #007bff;
                                        z-index: 2;
                                    }

                                    .approver-title {
                                        font-weight: 600;
                                        font-size: 1rem;
                                        margin-bottom: 2px;
                                        color: #23272b;
                                    }

                                    .approver-meta {
                                        font-size: 0.92rem;
                                        color: #495057;
                                        margin-bottom: 3px;
                                    }

                                    .status-badge {
                                        padding: 2px 11px;
                                        border-radius: 12px;
                                        font-size: .90rem;
                                        font-weight: 500;
                                        color: #fff;
                                    }

                                    .status-Approved {
                                        background: #28a745;
                                    }

                                    .status-Rejected {
                                        background: #dc3545;
                                    }

                                    .status-Pending {
                                        background: #ffc107;
                                        color: #23272b;
                                    }

                                    .status-Canceled {
                                        background: #6c757d;
                                    }

                                    .approval-timestamp {
                                        font-size: 0.9rem;
                                        color: #717b88;
                                        float: right;
                                        margin-left: 8px;
                                    }

                                    .approval-remark {
                                        margin-top: 6px;
                                        font-style: italic;
                                        color: #525659;
                                        font-size: 0.97rem;
                                        background: #f7f5f8;
                                        border-radius: 6px;
                                        padding: 8px 12px;
                                    }
                                </style>
                                <div class="container py-4">
                                    <div class="row justify-content-center">
                                        <div class="col-md-8">
                                            <h4 class="mb-4" style="margin-left:.4rem;">Riwayat Proses Persetujuan</h4>
                                            <ul class="timeline-approval">
                                                @foreach($item_req->approvals as $step)
                                                    <li>
                                                        <div class="approver-title">
                                                            {{ $step->approver->name ?? '-' }}
                                                            <span
                                                                class="approver-meta">({{ $step->approver->department->code ?? '-' }})</span>
                                                            <span class="status-badge status-{{ $step['status'] }} float-end">
                                                                {{ ucfirst($step['status']) }}
                                                            </span>
                                                        </div>
                                                        @if(!empty($step->approved_at))
                                                            <span class="approval-timestamp">
                                                                {{ \Carbon\Carbon::parse($step->approved_at)->format('d M Y H:i') }}
                                                            </span>
                                                        @endif
                                                        @if(!empty($step['remarks']))
                                                            <div class="approval-remark">
                                                                <strong>Catatan:</strong> {{ $step['remarks'] }}
                                                            </div>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning">Tidak ada data detail untuk ditampilkan.</div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    
    @if($myDetailModal)
    <div id="detailModal" class="modal fade show d-block" tabindex="-3" style="background-color: rgba(0,0,0,0.5); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="bi bi-info-circle me-2"></i>Detail Pengajuan Lisensi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeMyDetailModal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    @if (isset($this->req))
                        <div class="container-fluid">
                            
                            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                                <div>
                                    <h6 class="text-muted mb-1 small text-uppercase" style="letter-spacing: 1px;">Request Number</h6>
                                    <h4 class="fw-bold text-primary mb-0">{{ $this->req->request_no ?? '-' }}</h4>
                                </div>
                                <div class="text-end">
                                    <span @class([
                                        'badge rounded-pill px-3 py-2',
                                        'bg-success-subtle text-success border border-success' => $this->req->status === 'Approved',
                                        'bg-warning-subtle text-warning border border-warning' => $this->req->status === 'Pending',
                                        'bg-danger-subtle text-danger border border-danger' => $this->req->status === 'Rejected',
                                    ])>
                                        {{ $this->req->status }}
                                    </span>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body">
                                            <small class="text-muted d-block mb-1 text-uppercase font-weight-bold" style="font-size: 0.65rem;">Pemohon</small>
                                            <p class="mb-0 fw-bold text-dark">{{ $this->req->user->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body">
                                            <small class="text-muted d-block mb-1 text-uppercase font-weight-bold" style="font-size: 0.65rem;">Tanggal Pengajuan</small>
                                            <p class="mb-0 fw-bold text-dark">{{ $this->req->created_at?->format('d M Y') ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body">
                                            <small class="text-muted d-block mb-1 text-uppercase font-weight-bold" style="font-size: 0.65rem;">Tanggal Dibutuhkan</small>
                                            <p class="mb-0 fw-bold text-dark">{{ \Carbon\Carbon::parse($this->req->needed_date)->format('d M Y') ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <small class="text-muted d-block mb-1 text-uppercase font-weight-bold" style="font-size: 0.65rem;">Catatan / Purpose</small>
                                            <p class="mb-0 text-dark">{{ $this->req->purpose ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6 class="fw-bold mb-3 d-flex align-items-center">
                                    <i class="bi bi-paperclip me-2 text-primary"></i>Lampiran Dokumen
                                </h6>
                                <div class="card border border-dashed bg-white">
                                    <div class="card-body p-0">
                                        {{-- Cek jika ada relasi attachments --}}
                                        @if($this->req->attachments && $this->req->attachments->count() > 0)
                                            <div class="list-group list-group-flush">
                                                @foreach($this->req->attachments as $file)
                                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-danger-subtle p-2 rounded me-3">
                                                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
                                                            </div>
                                                            <div>
                                                                <p class="mb-0 fw-bold text-dark small">{{ $file->doc_file_name }}</p>
                                                                <small class="text-muted" style="font-size: 0.7rem;">PDF Document</small>
                                                            </div>
                                                        </div>
                                                        <div class="btn-group gap-2">
                                                            <a href="{{ asset('storage/'.$file->doc_file_path) }}" 
                                                               target="_blank" 
                                                               class="btn btn-sm btn-outline-primary rounded">
                                                                <i class="bi bi-eye"></i> View
                                                            </a>
                                                            <a href="{{ asset('storage/'.$file->doc_file_path) }}" 
                                                               download="{{ $file->doc_file_name }}" 
                                                               class="btn btn-sm btn-primary rounded">
                                                                <i class="bi bi-download"></i> Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-4">
                                                <i class="bi bi-file-earmark-x text-muted fs-2 d-block mb-2"></i>
                                                <p class="text-muted small mb-0">Tidak ada lampiran dokumen yang diunggah.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <h6 class="fw-bold mb-3 d-flex align-items-center">
                                    <i class="bi bi-box-seam me-2 text-primary"></i>Detail Software & Lisensi
                                </h6>
                                <div class="d-flex flex-column gap-3">
                                    @forelse($this->req->items as $item)
                                        <div class="card border-0 shadow-sm bg-white rounded-3 overflow-hidden">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-md-7 border-end-md">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <span class="badge bg-primary me-2">{{ $loop->iteration }}</span>
                                                            <h6 class="fw-bold mb-0 text-dark">{{ $item->software_name }}</h6>
                                                        </div>
                                                        <div class="row small g-2">
                                                            <div class="col-6">
                                                                <span class="text-muted d-block">Kategori</span>
                                                                <span class="text-dark fw-medium">{{ $item->softwareCategory->name ?? '-' }}</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <span class="text-muted d-block">Pabrikan</span>
                                                                <span class="text-dark fw-medium">{{ $item->softwareManufacturer->name ?? '-' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-5 ps-md-4 mt-3 mt-md-0">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <small class="text-muted">Kuantitas:</small>
                                                            <small class="fw-bold text-dark">{{ $item->quantity }} Unit</small>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <small class="text-muted">Harga:</small>
                                                            <small class="fw-bold text-primary">Rp {{ number_format(($item->price), 0, ',', '.') }}</small>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <small class="text-muted">Total Harga:</small>
                                                            <small class="fw-bold text-primary">Rp {{ number_format(($item->price * $item->quantity), 0, ',', '.') }}</small>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <small class="text-muted">No. Accounting:</small>
                                                            <small class="fw-medium text-dark">{{ $item->no_accounting ?? '-' }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="alert alert-info border-0 shadow-sm">
                                            <i class="bi bi-info-circle me-2"></i>Tidak ada item lisensi dalam pengajuan ini.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                        </div>
                    @endif
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4 shadow-sm" wire:click="closeMyDetailModal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
</div>



{{-- @forelse ($this->dataRequestess() as $request)
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0 text-primary font-weight-bolder">
        <i class="bx bx-receipt me-1"></i> Request No: {{ $request->request_no }}
    </h6>
    <button class="btn btn-sm btn-outline-primary mb-0" data-bs-toggle="collapse"
        data-bs-target="#collapse-req-{{ $request->request_no }}">
        Detail <i class="bx bx-caret-down ms-1"></i>
    </button>
</div>
<p class="text-sm mb-0 text-secondary">
    <strong>Catatan:</strong> {{ $request->purpose }}
</p>
</div>

<div class="collapse" id="collapse-req-{{ $request->request_no }}">
    <div class="card-footer bg-white p-3 border-top">
        <div class="table-responsive">
            <table class="table table-sm table-borderless mb-0">
                <thead class="bg-light border-radius-sm">
                    <tr>
                        <th class="text-xs font-weight-bolder text-secondary text-uppercase">Nama Software</th>
                        <th class="text-xs font-weight-bolder text-secondary text-uppercase">Kategori</th>
                        <th class="text-xs font-weight-bolder text-secondary text-uppercase">Pabrikan</th>
                        <th class="text-xs font-weight-bolder text-secondary text-uppercase">Jumlah</th>
                        <th class="text-xs font-weight-bolder text-secondary text-uppercase">Status</th>
                        <th class="text-xs font-weight-bolder text-secondary text-uppercase">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($request->items as $item)
                    <tr wire:key="request-item-{{ $item->id }}" class="border-bottom">
                        <td class="text-sm align-middle">
                            <div class="d-flex flex-column">
                                <h6 class="mb-0 text-sm">{{ $item->software_name }}</h6>
                                <span class="text-xs text-secondary">ID: {{ $item->id }}</span>
                                @if($item->remarks)
                                <span class="text-xs text-muted mt-1">Spesifikasi: {{ $item->remarks }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-sm align-middle">{{ $item->softwareCategory->name ?? '-' }}</td>
                        <td class="text-sm align-middle">{{ $item->softwareManufacturer->name ?? '-' }}</td>
                        <td class="text-sm align-middle">{{ $item->quantity ?? '-' }}</td>
                        <td class="text-sm align-middle">
                            @if($item->approvals->where('status', 'Rejected')->first())
                            @php
                            $approval = $item->approvals->where('status', 'Rejected')->first();
                            @endphp
                            <div class="text-danger text-xs">
                                <strong>Rejected by:</strong> {{ $approval && $approval->approver ?
                                $approval->approver->name : '-' }}<br>
                                <strong>Alasan:</strong> {{ $approval ? $approval->remarks : '-' }}
                            </div>
                            @else
                            @php
                            $approval = $item->approvals->where('status', 'Pending')->first();
                            @endphp
                            <div class="d-flex align-items-center">
                                <span
                                    class="badge badge-sm bg-gradient-{{ $item->status == 'Approved' ? 'success' : ($item->status == 'Pending' ? 'warning' : 'secondary') }} me-2">
                                    {{ ucfirst($item->status) ?? '-' }}
                                </span>
                                <span class="text-xs text-secondary">{{ $approval ? $approval->approver->name :
                                    '' }}</span>
                            </div>
                            @endif
                            <button class="btn btn-link text-secondary btn-sm mb-0 px-0 ms-2"
                                wire:click="showStatusHistory({{ $item->id }})" title="Lihat riwayat status">
                                <i class="bi bi-clock-history text-lg"></i>
                            </button>
                        </td>
                        <td class="text-sm align-middle">
                            @if(isset($item))
                            @if($item->approvals->where('status', 'Rejected')->first() || ($item &&
                            $item->approvals->where('status', 'Pending')->count() === 0))
                            <button class="btn btn-outline-danger btn-sm mb-0" disabled>Cancel</button>
                            @else
                            <button class="btn btn-outline-danger btn-sm mb-0">Cancel</button>
                            @endif
                            @endif
                        </td>
                    </tr>
                    <tr class="bg-light border-bottom">
                        <td colspan="6" class="text-xs py-2">
                            <div class="d-flex flex-column">
                                <span class="mb-1"><strong>Spesifikasi:</strong> {{ $item->remarks }}</span>
                                <span>
                                    <strong>Harga per item:</strong> Rp {{ number_format($item->price, 0, ',',
                                    '.') }} <span class="mx-1">|</span>
                                    <strong>Harga Total:</strong> Rp {{ number_format(($item->price *
                                    $item->quantity), 0, ',', '.') }} <span class="mx-1">|</span>
                                    <strong>No Accounting:</strong> {{ $item->no_accounting ?? '-' }}
                                </span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-sm py-3">Tidak ada item pada pengajuan ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</div>
@empty
<div class="col-12">
    <div class="alert alert-warning" role="alert">
        <strong>Informasi!</strong> Belum ada pengajuan lisensi dari Anda.
    </div>
</div>
@endforelse --}}