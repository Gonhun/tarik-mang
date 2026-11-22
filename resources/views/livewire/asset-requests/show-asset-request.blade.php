<div>
   
    <div class="container-fluid py-4">
        {{-- CARD UTAMA --}}
        <div class="card">
            <div class="card-header border-bottom">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Detail Pengajuan Aset #{{ $assetRequest->request_number }}</h5>
                        <p class="text-sm mb-0">Diajukan oleh: {{ $assetRequest->requester->name ?? 'N/A' }}</p>
                        <p class="text-sm mb-0">Departement {{ $assetRequest->requester->department->code ?? 'N/A' }} Jabatan {{ $assetRequest->requester->position->name ?? 'N/A' }}</p>
                    </div>
                    <div class="d-flex align-items-center mt-2 mt-md-0">
                        <span class="badge bg-gradient-{{
                            match(strtolower($assetRequest->status)) {
                                'approved', 'completed', 'processing' => 'success',
                                'rejected', 'cancelled' => 'danger',
                                'draft' => 'light',
                                default => 'info'
                            }
                        }} me-3 fs-6">{{ $assetRequest->status }}</span>
                        
                        <a href="{{ route('asset-requests.print', $assetRequest->id) }}" target="_blank"
                           class="btn btn-sm btn-outline-secondary mb-0">
                           <i class="bx bxs-printer me-1"></i> Cetak PDF
                        </a>
                    </div>
                </div>
                 @if (session()->has('message'))<div class="alert alert-success mt-3 py-2 px-3">{{ session('message') }}</div>@endif
                 @if (session()->has('error'))<div class="alert alert-danger mt-3 py-2 px-3">{{ session('error') }}</div>@endif
            </div>
            <div class="card-body">
                {{-- Bagian 1: Informasi Umum Pengajuan --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Informasi Pengajuan</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Tgl. Pengajuan</dt>
                            <dd class="col-sm-8">{{ \Carbon\Carbon::parse($assetRequest->request_date)->isoFormat('D MMMM YYYY') }}</dd>
                            <dt class="col-sm-4">Tgl. Dibutuhkan</dt>
                            <dd class="col-sm-8">{{ $assetRequest->needed_date ? \Carbon\Carbon::parse($assetRequest->needed_date)->isoFormat('D MMMM YYYY') : '-' }}</dd>
                            <dt class="col-sm-4">Jobsite</dt>
                            <dd class="col-sm-8">{{ $assetRequest->jobsite->name ?? 'N/A' }}</dd>
                            <dt class="col-sm-4">Tujuan</dt>
                            <dd class="col-sm-8">{{ nl2br(e($assetRequest->purpose)) }}</dd>
                            <dt class="col-sm-4">Item Qty</dt>
                            <div class="col-sm-8">
                            @foreach(
                                $assetRequest->items
                                    ->groupBy(fn($item) => $item->assetSeries->name)
                                    as $seriesName => $items
                            )
                                <p class="mb-0">{{ $seriesName }} - Jumlah <b>{{ $items->sum('quantity') }}</b></p>
                            @endforeach
                            </div>
                            <dt class="col-sm-4">Total Qty</dt>
                            <dd class="col-sm-8">{{ $assetRequest->items->sum('quantity') }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        {{-- Tampilkan file BA jika ada --}}
                        @if($assetRequest->ba_damage_path)
                            <div class="mb-2">
                                <label class="form-label fw-bold mb-1">File BA Kerusakan:</label>
                                <div>
                                    <a href="{{ asset('storage/' . $assetRequest->ba_damage_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-file"></i> Lihat File
                                    </a>
                                    <span class="text-muted small ms-2">{{ $assetRequest->ba_damage_name ?? basename($assetRequest->ba_damage_path) }}</span>
                                </div>
                            </div>
                        @endif
                        @if($assetRequest->ba_budget_outside_path)
                            <div class="mb-2">
                                <label class="form-label fw-bold mb-1">File BA Diluar Budget:</label>
                                <div>
                                    <a href="{{ asset('storage/' . $assetRequest->ba_budget_outside_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-file"></i> Lihat File
                                    </a>
                                    <span class="text-muted small ms-2">{{ $assetRequest->ba_budget_outside_name ?? basename($assetRequest->ba_budget_outside_path) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Bagian 2: Item Aset & Validasi IT --}}
                <hr>
                <h6 class="mt-3">Item Aset yang Diajukan & Validasi IT</h6>
                @foreach($assetRequest->items as $item)
                <div class="card mb-3" wire:key="item-approval-{{ $item->id }}">
                    <div class="card-header d-flex justify-content-end align-items-center py-2">
                        <div>
                            @if($item->is_standard)
                                <span class="badge bg-success">Item Standar</span>
                            @else
                                <span class="badge bg-warning">Item Non-Standar</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body {{ $item->it_approval_status == 'Pending' ? 'border-start border-4 border-warning' : ($item->it_approval_status == 'Approved' ? 'border-start border-4 border-success' : 'border-start border-4 border-danger') }}">
                        <div class="row border-bottom pb-2 mb-3">
                            <div class="col-md-8">
                                <p class="mb-1"><strong>{{ $loop->iteration }}. {{ $item->assetSeries->name ?? ($item->assetBrand->name ?? $item->assetType->name) }}</strong></p>
                                <p class="mb-0 text-muted small">{{ $item->assetType->name ?? '' }} / {{ $item->assetBrand->name ?? '' }}</p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <p class="mb-0"><strong>Jumlah:</strong> {{ $item->quantity }}</p>
                            </div>
                        </div>

                        {{-- Panel Aksi untuk IT Site --}}
                       
                        @if(in_array($assetRequest->status, ['Pending Approval','Pending IT Item Validation']) && $this->isCurrentUserTheItPic())
                            @if($item->it_approval_status == 'Pending')
                                <div class="row mt-2 gx-2">
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label small fw-bold">Tipe Pemenuhan:</label>
                                        <select wire:model.live="itemDecisions.{{ $item->id }}.fulfillment_type" class="form-select form-select-sm">
                                            <option value="new">Beli Baru</option>
                                            {{-- PERBAIKAN: Opsi stok hanya aktif jika ada stok --}}
                                            <option value="stock" @if(!$itemDecisions[$item->id]['has_stock']) disabled @endif>
                                                Ambil dari Stok ({{ $availableStockAssets[$item->id]->count() }} tersedia)
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-5 mb-2">
                                        <label class="form-label small fw-bold">
                                            @if($itemDecisions[$item->id]['fulfillment_type'] === 'stock')
                                                Aset Stok Tersedia: <span class="text-danger">*</span>
                                            @else
                                                &nbsp;
                                            @endif
                                        </label>
                                        @if($itemDecisions[$item->id]['fulfillment_type'] === 'stock')
                                            <div wire:loading.remove wire:target="itemDecisions.{{ $item->id }}.fulfillment_type">
                                                <select wire:model.defer="itemDecisions.{{ $item->id }}.selected_stock_asset_id" class="form-select form-select-sm @error('itemDecisions.'.$item->id.'.selected_stock_asset_id') is-invalid @enderror">
                                                    <option value="">-- Pilih Aset Stok --</option>
                                                    @foreach($availableStockAssets[$item->id] ?? [] as $stockAsset)
                                                        <option value="{{ $stockAsset->id }}">
                                                            {{ $stockAsset->asset_number }} (Lokasi: {{ $stockAsset->jobsite->name ?? 'N/A' }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('itemDecisions.'.$item->id.'.selected_stock_asset_id')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                        @endif
                                        @if($itemDecisions[$item->id]['fulfillment_type'] === 'new' && $itemDecisions[$item->id]['has_stock'])
                                            {{-- <div class="col-7 mb-2"> --}}
                                                <label class="form-label small fw-bold text-danger">Alasan Pembelian Baru <span class="text-danger">*</span></label>
                                                <input type="text" wire:model.defer="itemDecisions.{{ $item->id }}.notes" class="form-control form-control-sm @error('itemDecisions.'.$item->id.'.notes') is-invalid @enderror" placeholder="Jelaskan mengapa aset stok tidak digunakan...">
                                                @error('itemDecisions.'.$item->id.'.notes')<span class="text-danger small">{{ $message }}</span>@enderror
                                            {{-- </div> --}}
                                        @endif
                                        {{-- <div wire:loading wire:target="itemDecisions.{{ $item->id }}.fulfillment_type">...</div>
                                        <div wire:loading.remove wire:target="itemDecisions.{{ $item->id }}.fulfillment_type">
                                            @if($itemDecisions[$item->id]['fulfillment_type'] === 'stock')
                                            <select wire:model.defer="itemDecisions.{{ $item->id }}.selected_stock_asset_id" class="form-select form-select-sm @error('itemDecisions.'.$item->id.'.selected_stock_asset_id') is-invalid @enderror">
                                                <option value="">-- Pilih Aset Stok --</option>
                                                @forelse($availableStockAssets[$item->id] ?? [] as $stockAsset)
                                                    <option value="{{ $stockAsset->id }}">
                                                        {{ $stockAsset->asset_number }} (Lokasi: {{ $stockAsset->jobsite->name ?? 'N/A' }})
                                                    </option>
                                                @empty
                                                    <option disabled>Tidak ada stok cocok.</option>
                                                @endforelse
                                            </select>
                                            @error('itemDecisions.'.$item->id.'.selected_stock_asset_id')<span class="text-danger small">{{ $message }}</span>@enderror
                                            @endif
                                        </div> --}}
                                       
                                    </div>
                                   
                                    <div class="col-md-4 align-self-end text-end">
                                        <button wire:click="saveItemItDecision({{ $item->id }})" class="btn btn-sm btn-primary">Simpan Keputusan</button>
                                    </div>
                                </div>
                            @else
                                {{-- Tampilan read-only setelah IT memberi keputusan --}}
                                <div class="alert alert-light py-2 px-3 small">
                                    <p class="mb-1"><strong>Keputusan IT:</strong> <span class="fw-bold text-{{ $item->it_approval_status == 'Approved' ? 'success' : 'danger' }}">{{ $item->it_approval_status }}</span></p>
                                    @if($item->it_approval_status == 'Approved')
                                        <p class="mb-1"><strong>Pemenuhan:</strong> {{ Str::title(str_replace('_', ' ', $item->fulfillment_type)) }} - {{ $item->assignedAsset->asset_number ?? 'N/A' }}</p>
                                        @if($item->assignedAsset) <p class="mb-1"><strong>Aset Stok Dipilih:</strong> {{ $item->assignedAsset->asset_number }}</p> @endif
                                    @endif
                                    @if($item->it_approval_notes) <p class="mb-0"><strong>Catatan IT:</strong> {{ $item->it_approval_notes }}</p> @endif
                                </div>
                               
                            @endif
                            @else
                                {{-- Tampilan read-only untuk approver lain --}}
                                <div class="alert alert-secondary py-2 px-3 small">
                                    <p class="mb-1"><strong>Keputusan IT:</strong> <span class="fw-bold">{{ $item->it_approval_status }}</span>
                                        @if($item->it_approval_status == 'Approved') - Pemenuhan: <strong>{{ Str::title(str_replace('_', ' ', $item->fulfillment_type)) }} - {{ $item->assignedAsset->asset_number ?? '' }}</strong> @endif
                                    </p>
                                    @if($item->it_approval_notes)
                                        <div class="alert alert-danger fw-bold mb-0">
                                            <strong>Catatan IT:</strong> {{ $item->it_approval_notes }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                            @if($item->pr_number && $item->po_number)
                            <div class="mt-3 small border-top pt-2">
                                <p class="mb-0"><strong>No. PR:</strong> {{ $item->pr_number }} | <strong>No. Akunting:</strong> {{ $item->accounting_number }} | <strong>No. PO:</strong> {{ $item->po_number }}</p>
                                
                                @if (Auth::user()->isItAdmin())
                                    {{-- Tampilkan tombol atau form HANYA jika pengguna yang login adalah Admin IT --}}
                                    <div class="alert alert-success d-flex justify-content-between align-items-center p-3 mb-4">
                                        <span>
                                            <strong class="d-block">Pengajuan Disetujui!</strong>
                                            Langkah selanjutnya adalah input nomor PR & Akunting oleh Admin IT.
                                        </span>
                                        <button wire:click="openProcurementModal" class="btn btn-sm btn-light mb-0">
                                            <i class="bx bx-edit me-1"></i> Input Data Pengadaan
                                        </button>
                                    </div>
                                @else
                                    {{-- Tampilan untuk user lain --}}
                                    <div class="alert alert-success ">
                                        Pengajuan ini telah disetujui sepenuhnya dan sedang menunggu data pengadaan dari Admin IT.
                                    </div>
                                @endif
                            </div>
                            @endif
                    
                    </div>
                </div>
                @endforeach

                {{-- Tombol Finalisasi IT --}}
                {{-- @if($assetRequest->status == 'Pending IT Item Validation' && $this->isCurrentUserTheItPic() && $assetRequest->items()->where('it_approval_status', 'Pending')->doesntExist())
                <div class="text-end my-3">
                    <button wire:click="finalizeItStage" class="btn btn-success">Selesaikan Validasi IT & Lanjutkan Approval</button>
                </div>
                @endif --}}
            </div>
        </div>

        {{-- Bagian Approval Global oleh approver lain --}}
        @if ($assetRequest->approvals->count() > 0)
        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Progres Persetujuan Keseluruhan</h6></div>
            <div class="card-body">
                <div class="timeline ps-3">
                    @if($item->it_approval_status == 'Pending')
                    <div class="alert alert-warning">
                        <i class="bx bx-alarm-exclamation me-2"></i>
                        <strong>Perhatian!</strong> IT (<strong class="text-danger">{{ $assetRequest->approvals->first()?->approver?->name ?? 'N/A' }}</strong>) belum memilih status stok untuk semua item. Silakan Hubungi IT.
                    </div>
                    @else
                    @foreach ($assetRequest->approvals as $approval)
                        @php
                            static $firstPendingShown = false;
                        @endphp
                        @if ($approval->status == 'Rejected')
                            <div class="timeline-item mb-3">
                                <span class="timeline-point @if($approval->status == 'Approved') @elseif($approval->status == 'Rejected') @elseif($approval->status == 'Pending') @else bg-secondary @endif">
                                    @if ($approval->status == 'Approved') <i class="bx bx-bxs-user-check text-success"></i> @endif
                                    @if ($approval->status == 'Rejected') <i class="bx bxs-comment-x text-danger"></i> @endif
                                    @if ($approval->status == 'Pending') <i class="bx bxs-hourglass text-warning "></i> @endif
                                </span>
                                <div class="timeline-event">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-0">{{ $approval->approver_role_name }}</h6>
                                        <span class="badge @if($approval->status == 'Approved') bg-success @elseif($approval->status == 'Rejected') bg-danger @elseif($approval->status == 'Pending') bg-warning @else bg-secondary @endif">{{ $approval->status }}</span>
                                    </div>
                                    @if (!$firstPendingShown && $approval->status == 'Pending' && $approval->approver->id != Auth::id() && !in_array($approval->approver_role_name,['DIRECTORS','DIRECTORS IT']))
                                        <div class="d-flex justify-content-between mt-2">
                                            <button type="button"
                                                wire:click="SendWa({{$approval->approver}},{{ $assetRequest }})"
                                                wire:loading.attr="disabled"
                                                wire:target="SendWa"
                                                class="btn btn-info btn-sm">
                                                <span wire:loading.remove wire:target="SendWa">
                                                    <i class="bx bxl-gmail"></i> Follow Up Email
                                                </span>
                                                <span wire:loading wire:target="SendWa">
                                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                                    Mengirim...
                                                </span>
                                            </button>
                                        </div>
                                        @php $firstPendingShown = true; @endphp
                                    @endif
                                    
                                    <div class="timeline-body mt-1">
                                        <p class="mb-1"><small class="text-muted">Approver:</small> {{ $approval->approver->name ?? 'N/A' }}</p>
                                        @if($approval->action_at)
                                            <p class="mb-1"><small class="text-muted">Oleh:</small> {{ $approval->actionBy->name ?? 'N/A' }}</small></p>
                                            <p class="mb-1"><small class="text-muted">Tanggal:</small> {{ $approval->action_at->isoFormat('D MMM YY, HH:mm') }}</small></p>
                                            @if($approval->notes) <p class="mb-0"><small class="text-muted">Catatan:</small> <em class="small">{{ nl2br(e($approval->notes)) }}</em></p> @endif
                                        @endif

                                        @if ($this->canApproveOverallStep($approval))
                                            <div class="mt-2 border-top pt-2">
                                                <button wire:click="openOverallApprovalModal({{ $approval->id }}, 'approve')" class="btn btn-sm btn-success">Setujui</button>
                                                <button wire:click="openOverallApprovalModal({{ $approval->id }}, 'reject')" class="btn btn-sm btn-danger">Tolak</button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @break
                        @endif
                        <div class="timeline-item mb-3">
                            <span class="timeline-point @if($approval->status == 'Approved') @elseif($approval->status == 'Rejected') @elseif($approval->status == 'Pending') @else bg-secondary @endif">
                                @if ($approval->status == 'Approved') <i class="bx bx-bxs-user-check text-success"></i> @endif
                                @if ($approval->status == 'Rejected') <i class="bx bxs-comment-x text-danger"></i> @endif
                                @if ($approval->status == 'Pending') <i class="bx bxs-hourglass text-warning "></i> @endif
                            </span>
                            <div class="timeline-event">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-0">{{ $approval->approver_role_name }}</h6>
                                    <span class="badge @if($approval->status == 'Approved') bg-success @elseif($approval->status == 'Rejected') bg-danger @elseif($approval->status == 'Pending') bg-warning @else bg-secondary @endif">{{ $approval->status }}</span>
                                </div>
                               
                                @if (!$firstPendingShown && $approval->status == 'Pending' && $approval->approver->id != Auth::id() && !in_array($approval->approver_role_name,['DIRECTORS','DIRECTORS IT']))
                                    <div class="d-flex justify-content-between mt-2">
                                        <button type="button"
                                            wire:click="SendWa({{$approval->approver}},{{ $assetRequest }})"
                                            wire:loading.attr="disabled"
                                            wire:target="SendWa"
                                            class="btn btn-info btn-sm">
                                            <span wire:loading.remove wire:target="SendWa">
                                                <i class="bx bxl-gmail"></i> Follow Up Email
                                            </span>
                                            <span wire:loading wire:target="SendWa">
                                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                                Mengirim...
                                            </span>
                                        </button>
                                    </div>
                                    @php $firstPendingShown = true; @endphp
                                @endif
                                
                                <div class="timeline-body mt-1">
                                    <p class="mb-1"><small class="text-muted">Approver:</small> {{ $approval->approver->name ?? 'N/A' }}</p>
                                    @if($approval->action_at)
                                        <p class="mb-1"><small class="text-muted">Oleh:</small> {{ $approval->actionBy->name ?? 'N/A' }}</small></p>
                                        <p class="mb-1"><small class="text-muted">Tanggal:</small> {{ $approval->action_at->isoFormat('D MMM YY, HH:mm') }}</small></p>
                                        @if($approval->notes) <p class="mb-0"><small class="text-muted">Catatan:</small> <em class="small">{{ nl2br(e($approval->notes)) }}</em></p> @endif
                                    @endif

                                    <div class="mt-2 border-top pt-2">
                                        @if ($this->isAdmin && $approval->status == 'Pending')
                                            <button wire:click="openModalUpdatePic({{ $approval->id }})" class="btn btn-sm btn-primary">Update PIC</button>
                                        @endif
                                        @if ($this->canApproveOverallStep($approval))
                                                <button wire:click="openOverallApprovalModal({{ $approval->id }}, 'approve')" class="btn btn-sm btn-success">Setujui</button>
                                                <button wire:click="openOverallApprovalModal({{ $approval->id }}, 'reject')" class="btn btn-sm btn-danger">Tolak</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>
        @endif

        <div class="mt-4 text-end">
            <a href="{{ route('asset-requests.dashboard') }}" class="btn btn-secondary">Kembali ke Dashboard</a>
        </div>
    </div>

    {{-- Modal untuk Approval GLOBAL --}}
    @if ($isOverallApprovalModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form wire:submit.prevent="submitOverallApproval">
                    <div class="modal-header">
                        <h5 class="modal-title text-capitalize">{{ $overallApprovalAction == 'approve' ? 'Setujui' : 'Tolak' }} Seluruh Pengajuan</h5>
                        <button type="button" class="btn-close" wire:click="closeOverallApprovalModal"></button>
                    </div>
                    <div class="modal-body">
                        <label for="overall_approval_notes" class="form-label">Catatan @if($overallApprovalAction == 'reject') <span class="text-danger">(Wajib jika menolak)</span> @endif</label>
                        <textarea wire:model.defer="overallApprovalNotes" class="form-control @error('overallApprovalNotes') is-invalid @enderror" id="overall_approval_notes" rows="4"></textarea>
                        @error('overallApprovalNotes') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeOverallApprovalModal">Batal</button>
                        <button type="submit" class="btn btn-{{ $overallApprovalAction == 'approve' ? 'success' : 'danger' }}">
                            Submit {{ $overallApprovalAction == 'approve' ? 'Persetujuan' : 'Penolakan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    
    @if($this->isUpdatePIcModalOpen)
    <div id="updatePicModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form wire:submit.prevent="submitUpdatePic">
                    <div class="modal-header">
                        <h5 class="modal-title">Update PIC IT & Jobsite</h5>
                        <button type="button" class="btn-close" wire:click="closeModalUpdatePic()"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="siteToUpdate" class="form-label">Pilih Jobsite</label>
                            <select wire:model.live="siteToUpdate" id="siteToUpdate" class="form-select @error('siteToUpdate') is-invalid @enderror">
                                <option value="">-- Pilih Jobsite --</option>
                                @foreach($site_option as $code => $id)
                                    <option value="{{ $id }}">{{ $code }}</option>
                                @endforeach
                            </select>
                            @error('siteToUpdate') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        @dump($this->siteToUpdate)
                        <div class="mb-3">
                            <label for="picToUpdate" class="form-label">Pilih PIC IT</label>
                            <div wire:loading.remove wire:target="siteToUpdate">
                                <select wire:model="picToUpdate" id="picToUpdate" class="form-select @error('picToUpdate') is-invalid @enderror">
                                    <option value="">-- Pilih PIC IT --</option>
                                    @foreach($nik_option as $display)
                                        <option value="{{ $display['id'] }}">{{ $display['nik'].' - '.$display['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('picToUpdate') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div wire:loading wire:target="siteToUpdate">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModalUpdatePic()">Batal</button>
                        <button type="submit" class="btn btn-primary">Update PIC</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    {{-- Modal untuk input PR/Akunting bisa ditambahkan di sini --}}
</div>

@push('styles')
<style>
    /* Styling untuk timeline progres approval */
    .timeline { position: relative; border-left: 2px solid #e9ecef; }
    .timeline-item { position: relative; }
    .timeline-item:not(:last-child) { padding-bottom: 1.5rem; }
    .timeline-point { position: absolute; left: -11px; top: 5px; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; }
    .timeline-point i { font-size: 0.8rem; }
    .timeline-event { margin-left: 25px; padding: 0.5rem 1rem; border-radius: .25rem; background: #f8f9fa;}
    .timeline-event h6 { font-weight: 600; }
    .timeline-body { font-size: 0.9rem; color: #6c757d; }
</style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
        
        // Fungsi helper agar tidak mengulang kode (DRY)
        const initSelect2 = () => {
            if ($('#picToUpdate').length) {
                $('#picToUpdate').select2({
                    dropdownParent: $("#updatePicModal"),
                    allowClear: true,
                    placeholder: 'Pilih PIC...'
                }).on('change', function (e) {
                    // Sinkronisasi ke Livewire saat user memilih di Select2
                    @this.set('picToUpdate', $(this).val());
                });
            }
        };

        // 1. Saat Modal Dibuka
        Livewire.on('open-modal', (event) => {
            if (event[0] === true) {
                setTimeout(() => { initSelect2(); }, 150);
            }
        });

        // 2. KUNCI UTAMA: Re-init saat list PIC berubah (dikirim dari PHP)
        Livewire.on('reinit-pic-select', () => {
            console.log('Re-initializing Select2 for PIC...');
            setTimeout(() => { initSelect2(); }, 50);
        });

        // 3. Menangani re-render Livewire secara umum
        Livewire.hook('morph.updated', ({ el, component }) => {
            // Jika elemen yang diupdate adalah pembungkus select2
            if ($('#picToUpdate').length && !$('#picToUpdate').data('select2')) {
                initSelect2();
            }
        });
    });
    Livewire.on('swal:success', event => {
            // If you dispatch like: $this->dispatch('swal:success', ['title' => '...', 'text' => '...'])
            // then 'event' will be the array you passed.
            // If you dispatch like: $this->dispatch('swal:success', 'Title', 'Text')
            // then 'event' will be an array of arguments.

            // Try to support both ways:
            let detail = event;
            if (Array.isArray(event) && event.length === 1 && typeof event[0] === 'object') {
                detail = event[0];
            }
            // Now detail should be an object with 'title' and 'text'
            Swal.fire({
                icon: 'success',
                title: detail.title || (event[0] ?? ''),
                text: detail.text || (event[1] ?? ''),
                timer: 2500,
                showConfirmButton: false,
            });
        });
        Livewire.on('swal:error', event => {
            // If you dispatch like: $this->dispatch('swal:success', ['title' => '...', 'text' => '...'])
            // then 'event' will be the array you passed.
            // If you dispatch like: $this->dispatch('swal:success', 'Title', 'Text')
            // then 'event' will be an array of arguments.

            // Try to support both ways:
            let detail = event;
            if (Array.isArray(event) && event.length === 1 && typeof event[0] === 'object') {
                detail = event[0];
            }
            // Now detail should be an object with 'title' and 'text'
            Swal.fire({
                icon: 'danger',
                title: detail.title || (event[0] ?? ''),
                text: detail.text || (event[1] ?? ''),
                timer: 2500,
                showConfirmButton: true,
            });
        });
    </script>
@endpush