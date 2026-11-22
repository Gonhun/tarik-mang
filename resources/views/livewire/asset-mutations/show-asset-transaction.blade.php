<div class="min-vh-100 bg-light">
    <div class="container-fluid py-4">
        <div class="card shadow-lg border-0 rounded-3">
            <div class="card-header bg-gradient-primary text-white border-0 rounded-top">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bx bx-receipt fs-4 me-3"></i>
                        <h5 class="mb-0 fw-bold">Detail Transaksi Aset #{{ $transaction->transaction_number }}</h5>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge fs-6 px-3 py-2 rounded-pill bg-gradient-{{
                            match(strtolower($transaction->status)) {
                                'completed', 'processed' => 'success',
                                'rejected', 'cancelled' => 'danger',
                                'draft' => 'light',
                                'in transit' => 'primary',
                                default => 'info'
                            }
                        }} shadow-sm">
                            <i class="bx bx-{{ 
                                match(strtolower($transaction->status)) {
                                    'completed', 'processed' => 'check-circle',
                                    'rejected', 'cancelled' => 'x-circle',
                                    'draft' => 'edit',
                                    'in transit' => 'transfer',
                                    default => 'info-circle'
                                }
                            }} me-2"></i>
                            {{ ucfirst($transaction->status) }}
                        </span>
                        <a href="{{ route('asset.mutasi.pdf', $transaction->id) }}" target="_blank"
                           class="btn btn-light btn-sm rounded-pill shadow-sm">
                           <i class="bx bxs-printer me-2"></i> Cetak PDF
                        </a>
                    </div>
                </div>
            </div>

            @if (session()->has('message'))
                <div class="alert alert-success alert-dismissible fade show m-3 rounded-3" role="alert">
                    <i class="bx bx-check-circle me-2"></i>{{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show m-3 rounded-3" role="alert">
                    <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card-body p-4">
                {{-- Bagian 1: Informasi Umum Transaksi --}}
                <div class="row g-4 mb-5">
                    <div class="col-lg-6">
                        <div class="info-card h-100">
                            <div class="card-header-custom">
                                <i class="bx bx-info-circle me-2"></i>
                                <h6 class="mb-0 fw-bold">Informasi Transaksi</h6>
                            </div>
                            <div class="card-body-custom">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">
                                            <i class="bx bx-transfer me-2 text-primary"></i>Jenis Transaksi <span class="info-value">{{ Str::title(str_replace('_', ' ', $transaction->transaction_type)) }}</span>
                                        </span>
                                        
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">
                                            <i class="bx bx-calendar me-2 text-success"></i>Tanggal Transaksi <span class="info-value">{{ \Carbon\Carbon::parse($transaction->transaction_date)->isoFormat('D MMMM YYYY') }}</span>
                                        </span>
                                        
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">
                                            <i class="bx bx-user me-2 text-info"></i>Dibuat Oleh <span class="info-value">{{ $transaction->creator->name ?? 'N/A' }}</span>
                                        </span>
                                        
                                    </div>
                                    @if($transaction->transaction_type === 'MUTATION')
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="bx bx-location-plus me-2 text-warning"></i>Dari Jobsite <span class="info-value">{{ $transaction->fromJobsite->name ?? 'N/A' }} ({{ $fromCostCenter->cost_center_number ?? 'N/A' }})</span>
                                            </span>
                                            
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="bx bx-bxs-edit-location me-2 text-danger"></i>Ke Jobsite <span class="info-value">{{ $transaction->toJobsite->name ?? 'N/A' }} ({{ $destinationCostCenter->cost_center_number ?? 'N/A' }})</span>
                                            </span> 
                                            
                                        </div>
                                    @elseif($transaction->transaction_type === 'HANDOVER')
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="bx bx-user-minus me-2 text-warning"></i>{{ $transaction->transaction_type === 'HANDOVER' ? 'Pemberi' : 'Yang Mengembalikan' }}
                                            </span>
                                            <span class="info-value">{{ $transaction->fromUser->name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="bx bx-user-plus me-2 text-success"></i>{{ $transaction->transaction_type === 'HANDOVER' ? 'Penerima' : 'Diterima Oleh' }}
                                            </span>
                                            <span class="info-value">{{ $transaction->toUser->name ?? 'N/A' }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="info-card h-100">
                            <div class="card-header-custom">
                                <i class="bx bx-note me-2"></i>
                                <h6 class="mb-0 fw-bold">Catatan & Dokumen</h6>
                            </div>
                            <div class="card-body-custom">
                                <div class="notes-section">
                                    <div class="notes-header">
                                        <i class="bx bx-message-square-detail me-2 text-primary"></i>
                                        <strong>Catatan Transaksi:</strong>
                                    </div>
                                    <div class="notes-content">
                                        {!! $transaction->notes ? nl2br(e($transaction->notes)) : '<span class="text-muted">Tidak ada catatan</span>' !!}
                                    </div>
                                    <div class="mt-3">
                                        <strong>Dokumen Pendukung:</strong>
                                        @if($transaction->document_path)
                                            <a href="{{ Storage::url($transaction->document_path) }}" target="_blank">
                                                {{ $transaction->document_name ?? 'Lihat Dokumen' }} <i class="fas fa-external-link-alt fa-xs"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">- Tidak ada -</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bagian 2: Item Aset yang Terlibat --}}
                <div class="section-divider mb-3">
                    <i class="bx bx-package me-2"></i>
                    <span>Item Aset yang Terlibat</span>
                </div>
                <div class="table-responsive rounded-3 shadow-sm mb-4">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 60px;">No.</th>
                                <th>Nomor Aset</th>
                                <th>Serial Number</th>
                                <th>Deskripsi Aset</th>
                                <th>Catatan Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transaction->items as $index => $item)
                                <tr>
                                    <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill">{{ $item->asset->asset_number ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded">{{ $item->asset->serial_number ?? 'N/A' }}</code>
                                    </td>
                                    <td>
                                        <div class="asset-description">
                                            <div class="asset-type">{{ $item->asset->assetType->name ?? 'N/A' }}</div>
                                            <div class="asset-brand">{{ $item->asset->assetBrand->name ?? 'N/A' }}</div>
                                            <div class="asset-series">
                                                {{ !empty($item->asset->assetSeries->name) ? $item->asset->assetSeries->name : ($item->asset->assetSeries->materialCode->name ?? '-') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($item->condition_notes)
                                            <span class="condition-notes">{{ $item->condition_notes }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="bx bx-package fs-1 text-muted mb-3"></i>
                                            <p class="text-muted mb-0">Tidak ada item aset yang terlibat dalam transaksi ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Bagian 3: Progres Persetujuan --}}
                <div class="section-divider mb-3">
                    <i class="bx bx-timer me-2"></i>
                    <span>Progres Persetujuan</span>
                </div>
                <div class="approval-timeline">
                    <div class="timeline-container">
                        @foreach ($transaction->approvals->sortByDesc('step_order') as $approval)
                            <div class="timeline-card mb-4 {{ $approval->status == 'Pending' ? 'current-step' : '' }}">
                                <div class="timeline-header">
                                    <div class="status-indicator 
                                        @if ($approval->status == 'Approved') approved
                                        @elseif ($approval->status == 'Rejected') rejected
                                        @elseif ($approval->status == 'Pending') pending
                                        @else waiting @endif
                                    ">
                                        <div class="status-icon">
                                            @if ($approval->status == 'Approved') 
                                                <i class="bx bx-check-circle"></i>
                                            @elseif ($approval->status == 'Rejected') 
                                                <i class="bx bx-x-circle"></i>
                                            @elseif ($approval->status == 'Pending') 
                                                <i class="bx bx-time"></i>
                                            @else 
                                                <i class="bx bx-circle"></i>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="approval-header">
                                            <h6 class="approval-title mb-1">
                                                <i class="bx bx-user-check me-2"></i>
                                                {{ $approval->approver_role_name }}
                                            </h6>
                                            <div class="status-badge">
                                                <span class="badge 
                                                    @if ($approval->status == 'Approved') bg-success
                                                    @elseif ($approval->status == 'Rejected') bg-danger
                                                    @elseif ($approval->status == 'Pending') bg-warning
                                                    @else bg-secondary @endif
                                                ">
                                                    @if ($approval->status == 'Approved') 
                                                        <i class="bx bx-check me-1"></i>Disetujui
                                                    @elseif ($approval->status == 'Rejected') 
                                                        <i class="bx bx-x me-1"></i>Ditolak
                                                    @elseif ($approval->status == 'Pending') 
                                                        <i class="bx bx-hourglass me-1"></i>Menunggu
                                                    @else 
                                                        <i class="bx bx-minus me-1"></i>Belum Dimulai
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        <div class="approval-details">
                                            <div class="approver-info">
                                                <div class="info-item">
                                                    <i class="bx bx-user me-2 text-primary"></i>
                                                    <span class="label">Approver:</span>
                                                    <span class="value">{{ $approval->approver->name ?? 'N/A' }}</span>
                                                </div>
                                                @if($approval->action_at)
                                                    <div class="action-info mt-2">
                                                        <div class="info-item">
                                                            <i class="bx bx-calendar-check me-2 text-success"></i>
                                                            <span class="label">Tanggal Aksi:</span>
                                                            <span class="value">{{ \Carbon\Carbon::parse($approval->action_at)->isoFormat('D MMM YYYY, HH:mm') }}</span>
                                                        </div>
                                                        <div class="info-item">
                                                            <i class="bx bx-user-voice me-2 text-info"></i>
                                                            <span class="label">Aksi Oleh:</span>
                                                            <span class="value">{{ $approval->actionBy->name ?? 'N/A' }}</span>
                                                        </div>
                                                        @if($approval->notes)
                                                            <div class="notes-section mt-2">
                                                                <div class="info-item">
                                                                    <i class="bx bx-message-square-detail me-2 text-warning"></i>
                                                                    <span class="label">Catatan:</span>
                                                                </div>
                                                                <div class="notes-content">
                                                                    {!! nl2br(e($approval->notes)) !!}
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                            @php
                                                // Cek apakah ini giliran approver ini (tidak ada tahap sebelumnya yang pending)
                                                $isHisTurn = !$transaction->approvals()->where('step_order', '<', $approval->step_order)->where('status', 'Pending')->exists();
                                            @endphp
                                            @if ($approval->status == 'Pending' && Auth::id() == $approval->approver_user_id && $isHisTurn && !in_array($transaction->status, ['Rejected', 'Cancelled']))
                                                <div class="action-buttons mt-3">
                                                    <button wire:click="openApprovalModal({{ $approval->id }}, 'approve')" 
                                                            class="btn btn-success btn-sm me-2 action-btn">
                                                        <i class="bx bx-check me-1"></i>
                                                        <span>Setujui</span>
                                                    </button>
                                                    <button wire:click="openApprovalModal({{ $approval->id }}, 'reject')" 
                                                            class="btn btn-danger btn-sm action-btn">
                                                        <i class="bx bx-x me-1"></i>
                                                        <span>Tolak</span>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <style>
                .approval-timeline {
                    position: relative;
                    padding: 20px 0;
                }
                .timeline-container {
                    position: relative;
                }
                .timeline-container::before {
                    content: '';
                    position: absolute;
                    left: 30px;
                    top: 0;
                    bottom: 0;
                    width: 2px;
                    background: linear-gradient(180deg, #e9ecef 0%, #6c757d 50%, #e9ecef 100%);
                }
                .timeline-card {
                    position: relative;
                    margin-left: 60px;
                    background: #fff;
                    border-radius: 12px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    transition: all 0.3s ease;
                    border-left: 4px solid #e9ecef;
                }
                .timeline-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
                }
                .timeline-card.current-step {
                    border-left-color: #ffc107;
                    box-shadow: 0 4px 16px rgba(255, 193, 7, 0.2);
                }
                .timeline-header {
                    display: flex;
                    align-items: flex-start;
                    padding: 20px;
                }
                .status-indicator {
                    position: absolute;
                    left: -45px;
                    top: 20px;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: 3px solid #fff;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                }
                .status-indicator.approved {
                    background: linear-gradient(135deg, #28a745, #20c997);
                }
                .status-indicator.rejected {
                    background: linear-gradient(135deg, #dc3545, #fd7e14);
                }
                .status-indicator.pending {
                    background: linear-gradient(135deg, #ffc107, #ff8c00);
                    animation: pulse 2s infinite;
                }
                .status-indicator.waiting {
                    background: linear-gradient(135deg, #6c757d, #495057);
                }
                .status-icon {
                    color: white;
                    font-size: 14px;
                }
                .timeline-content {
                    flex: 1;
                }
                .approval-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                }
                .approval-title {
                    color: #495057;
                    font-weight: 600;
                    margin: 0;
                }
                .status-badge .badge {
                    font-size: 0.75rem;
                    padding: 0.5rem 0.75rem;
                    border-radius: 20px;
                }
                .approval-details {
                    color: #6c757d;
                }
                .info-item {
                    display: flex;
                    align-items: center;
                    margin-bottom: 8px;
                    font-size: 0.9rem;
                }
                .info-item .label {
                    font-weight: 500;
                    margin-right: 8px;
                    min-width: 80px;
                }
                .info-item .value {
                    color: #495057;
                    font-weight: 400;
                }
                .notes-section {
                    background: #f8f9fa;
                    padding: 12px;
                    border-radius: 8px;
                    border-left: 3px solid #ffc107;
                }
                .notes-content {
                    margin-top: 8px;
                    padding: 8px;
                    background: white;
                    border-radius: 6px;
                    font-style: italic;
                    color: #495057;
                }
                </style>
            </div>
            <div class="card-footer text-end">
                @if ($transaction->transaction_type === 'MUTATION')
                    <a href="{{ route('asset.mutasi') }}" class="btn btn-secondary">Kembali ke Daftar</a>
                @elseif($transaction->transaction_type === 'DISPOSED')
                    <a href="{{ route('asset.disposed') }}" class="btn btn-secondary">Kembali ke Daftar</a>
                @endif
                
            </div>
        </div>

        {{-- Modal untuk Approval --}}
        @if ($isApprovalModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit.prevent="submitApproval">
                        <div class="modal-header">
                            <h5 class="modal-title text-capitalize">{{ $approval_action == 'approve' ? 'Setujui' : 'Tolak' }} Tahapan Persetujuan</h5>
                            <button type="button" class="btn-close" wire:click="closeApprovalModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                @if(isset($approval) && $approval->approver->role_id == 4)
                                    <div class="row">
                                        <dl class="row">
                                            @if($transaction->transaction_type === 'MUTATION')
                                                <dt class="col-sm-4">Dari Jobsite</dt>
                                                <dd class="col-sm-8">{{ $transaction->fromJobsite->name ?? 'N/A' }}</dd>
                                                <dt class="col-sm-4">Cost Center Asal</dt>
                                                <dd class="col-sm-8"><b>{{ $fromCostCenter->cost_center_number ?? 'N/A' }}</b></dd>
                                                <hr>
                                                <dt class="col-sm-4">Ke Jobsite</dt>
                                                <dd class="col-sm-8">{{ $transaction->toJobsite->name ?? 'N/A' }}</dd>
                                                <dt class="col-sm-4">Cost Center Tujuan</dt>
                                                <dd class="col-sm-8"><b>{{ $destinationCostCenter->cost_center_number ?? 'N/A' }}</b></dd>
                                            @endif
                                        </dl>
                                    </div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label for="approval_notes" class="form-label">Catatan @if($approval_action == 'reject') <span class="text-danger">(Wajib jika menolak)</span> @endif</label>
                                <textarea wire:model.defer="approval_notes" class="form-control @error('approval_notes') is-invalid @enderror" id="approval_notes" rows="4"></textarea>
                                @error('approval_notes') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeApprovalModal">Batal</button>
                            <button type="submit" class="btn btn-{{ $approval_action == 'approve' ? 'success' : 'danger' }}">
                                {{ $approval_action == 'approve' ? 'Submit Persetujuan' : 'Submit Penolakan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(255,193,7,0.4); }
        70% { box-shadow: 0 0 0 10px rgba(255,193,7,0); }
        100% { box-shadow: 0 0 0 0 rgba(255,193,7,0); }
    }
</style>
@endpush
