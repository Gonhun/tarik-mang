<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detail Pengajuan Aset #{{ $assetRequest->request_number }}</h5>
                    <span class="badge bg-gradient-{{ 
                        match(strtolower($assetRequest->status)) {
                            'approved', 'completed' => 'success',
                            'rejected', 'cancelled' => 'danger',
                            'draft' => 'light',
                            default => 'info' 
                        }
                    }}">{{ $assetRequest->status }}</span>
                </div>
                 @if (session()->has('message'))<div class="alert alert-success mt-2">{{ session('message') }}</div>@endif
                 @if (session()->has('error'))<div class="alert alert-danger mt-2">{{ session('error') }}</div>@endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informasi Pengajuan</h6>
                        <p><strong>Tgl. Pengajuan:</strong> {{ \Carbon\Carbon::parse($assetRequest->request_date)->isoFormat('D MMM YYYY') }}</p>
                        <p><strong>Tgl. Dibutuhkan:</strong> {{ $assetRequest->needed_date ? \Carbon\Carbon::parse($assetRequest->needed_date)->isoFormat('D MMM YYYY') : '-' }}</p>
                        <p><strong>Diajukan Oleh:</strong> {{ $assetRequest->requester->name ?? 'N/A' }} (NIK: {{ $assetRequest->requester->nik ?? 'N/A' }})</p>
                        <p><strong>Tujuan Pengajuan:</strong><br>{{ nl2br(e($assetRequest->purpose)) }}</p>
                        @if($assetRequest->remarks)
                        <p><strong>Catatan Tambahan dari Pemohon:</strong><br>{{ nl2br(e($assetRequest->remarks)) }}</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6>Dokumen Pendukung</h6>
                        @if($assetRequest->requester->so_path)
                            <p><strong>Struktur Organisasi:</strong> 
                                <a href="{{ asset('storage/'.$assetRequest->requester->so_path) }}" target="_blank">
                                    {{ $assetRequest->requester->so_path ? 'Lihat Dokumen' : ''}} <i class="bx bx-link-alt"></i>
                                </a>
                            </p>
                        @else
                            <p><strong>Struktur Organisasi:</strong> <span class="text-muted">- Tidak ada -</span></p>
                        @endif
                        @if($assetRequest->requester->jd_path)
                            <p><strong>JobDesk Lain:</strong> 
                               
                                <a href="{{ $assetRequest->requester->jd_path }}" target="_blank">
                                    {{ $assetRequest->requester->jd_path ? 'Lihat Dokumen' : '' }} <i class="bx bx-link-alt"></i>
                                </a>
                            </p>
                        @else
                            <p><strong>JobDesk Lain:</strong> <span class="text-muted">- Tidak ada -</span></p>
                        @endif
                    </div>
                </div>

                <hr>
                <h6>Item Aset yang Diajukan</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Kode Material</th>
                                <th>Nama Material</th>
                                <th>Deskripsi Item</th>
                                <th class="text-end">Jumlah</th>
                                {{-- <th class="text-end">Estimasi Biaya</th> --}}
                                {{-- <th>Catatan Item</th> --}}
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assetRequest->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->materialCode->code ?? 'N/A' }}</td>
                                    <td>{{ $item->materialCode->name ?? 'N/A' }}</td>
                                    <td>{{ $item->item_description ?? '-' }}</td>
                                    <td class="text-end">{{ $item->quantity }} {{ $item->materialCode->unit_of_measurement ?? '' }}</td>
                                    {{-- <td class="text-end">{{ $item->estimated_cost ? number_format($item->estimated_cost, 0, ',', '.') : '-' }}</td> --}}
                                    {{-- <td>{{ $item->remarks ?? '-' }}</td> --}}
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">Tidak ada item aset dalam pengajuan ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <hr>
                <h6>Progres Persetujuan ("Request Sampai Mana")</h6>
                <div class="timeline">
                    @foreach ($approvalStages as $stepKey => $stage)
                        @php
                            $picRelationshipName = $stage['status_prefix'] . 'UserPic';
                            $picUser = $assetRequest->{$picRelationshipName};
                            $approvedByRelationshipName = $stage['status_prefix'] . 'ApprovedBy';
                            $approvedByUser = $assetRequest->{$approvedByRelationshipName};
                            $approvalDate = $assetRequest->{$stage['status_prefix'].'_approval_at'};
                            $approvalNotes = $assetRequest->{$stage['status_prefix'].'_approval_notes'};
                            $expectedPendingStatus = $stage['pending_status'] ?? ('Pending ' . $stage['label'] . ' Approval');
                        @endphp

                        <div class="timeline-item">
                            <div class="timeline-marker">

                                @if ($approvalDate)
                                    @if ($rejectorStageKey === $stepKey)
                                        {{-- Ini adalah tahap di mana penolakan terjadi --}}
                                        <i class="bx bxs-checkbox-minus text-danger"></i>
                                    @else
                                    <i class="bx bx-check-circle text-success"></i>
                                    @endif
                                @elseif ($assetRequest->status == $expectedPendingStatus)
                                    <i class="bx bx-clock text-warning"></i>
                                @else
                                    <i class="bx bx-check-circle text-muted"></i>
                                @endif
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h6 class="mb-0">{{ $stage['label'] }} </h6>
                                    @if ($approvalDate)
                                        @if ($rejectorStageKey === $stepKey)
                                            <span class="badge bg-danger">
                                                Ditolak
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                Disetujui
                                            </span>
                                        @endif
                                    @elseif ($assetRequest->status == $expectedPendingStatus)
                                        <span class="badge bg-warning text-dark">Menunggu</span>
                                    @endif
                                </div>

                                <div class="timeline-body">
                                    <div class="mb-2">
                                        <small class="text-muted">PIC:</small>
                                        @if ($assetRequest->{$stage['pic_field']})
                                            <span>{{ $picUser->name ?? 'Nama PIC Tidak Tersedia' }}</span>
                                        @else
                                            <span class="text-muted">- Tidak Ditentukan -</span>
                                        @endif
                                    </div>

                                    @if ($approvalDate)
                                    
                                        <div class="mb-2">
                                            <small class="text-muted">Disetujui oleh: </small>
                                            <span>{{ $approvedByUser->name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Tanggal:</small>
                                            <span>{{ \Carbon\Carbon::parse($approvalDate)->isoFormat('D MMM YYYY, HH:mm') }}</span>
                                        </div>
                                        @if($approvalNotes)
                                            <div class="mb-0">
                                                <small class="text-muted">Catatan:</small>
                                                <p class="mb-0 fst-italic small">{{ nl2br(e($approvalNotes)) }}</p>
                                            </div>
                                        @endif
                                    @elseif ($assetRequest->status == $expectedPendingStatus && $this->canApproveStep($stepKey))
                                        <div class="mt-2">
                                            <button wire:click="openApprovalModal('{{ $stepKey }}', 'approve')" class="btn btn-sm btn-success me-2">
                                                <i class="fas fa-check me-1"></i> Setujui
                                            </button>
                                            <button wire:click="openApprovalModal('{{ $stepKey }}', 'reject')" class="btn btn-sm btn-danger">
                                                <i class="fas fa-times me-1"></i> Tolak
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-muted small">
                                            @php
                                                $stageKeys = array_keys($approvalStages);
                                                $currentStageIndex = array_search($stepKey, $stageKeys);
                                                $currentRequestStatusIndex = array_search($assetRequest->status, array_column($approvalStages, 'pending_status'));
                                                $isPassed = ($currentRequestStatusIndex !== false && $currentStageIndex < $currentRequestStatusIndex) || 
                                                           in_array($assetRequest->status, ['Approved', 'Completed']);
                                            @endphp
                                            {{ $isPassed && !$approvalDate ? '- Dilewati / Disetujui (Implisit) -' : '- Belum Sampai Tahap Ini -' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <style>
                    .timeline {
                        position: relative;
                        padding: 20px 0;
                    }
                    .timeline::before {
                        content: '';
                        position: absolute;
                        top: 0;
                        left: 15px;
                        height: 100%;
                        width: 2px;
                        background: #e9ecef;
                    }
                    .timeline-item {
                        position: relative;
                        margin-bottom: 30px;
                        padding-left: 40px;
                    }
                    .timeline-marker {
                        position: absolute;
                        left: 0;
                        width: 30px;
                        height: 30px;
                        border-radius: 50%;
                        background: #fff;
                        text-align: center;
                        line-height: 30px;
                    }
                    .timeline-content {
                        background: #fff;
                        padding: 15px;
                        border-radius: 4px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    }
                    .timeline-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 10px;
                    }
                    .timeline-body {
                        font-size: 0.9rem;
                    }
                </style>

            </div>
            <div class="card-footer text-end">
                <a href="{{ route('asset-requests.dashboard')}}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>

        {{-- Modal untuk Approval --}}
        @if ($isApprovalModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit.prevent="submitApproval">
                        <div class="modal-header">
                            <h5 class="modal-title text-capitalize">{{ $approval_action == 'approve' ? 'Setujui' : 'Tolak' }} Pengajuan (Tahap: {{ $approvalStages[$currentApprovalStepKey]['label'] ?? '' }})</h5>
                            <button type="button" class="btn-close" wire:click="closeApprovalModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if ($currentApprovalStepKey === 'it_site' && $approval_action === 'approve')
                            <div class="mb-3">
                                <label class="form-label">Pilih Tipe Pemenuhan Aset:</label>
                                <div class="form-check">
                                    <input wire:model.live="it_fulfillment_type" class="form-check-input" type="radio" value="new" id="fulfill_new">
                                    <label class="form-check-label" for="fulfill_new">Beli Baru (Lanjutkan alur approval)</label>
                                </div>
                                <div class="form-check">
                                    <input wire:model.live="it_fulfillment_type" class="form-check-input" type="radio" value="stock" id="fulfill_stock">
                                    <label class="form-check-label" for="fulfill_stock">Ambil dari Stok</label>
                                </div>
                            </div>
                    
                            {{-- Tampilkan dropdown aset stok JIKA "Ambil dari Stok" dipilih --}}
                            @if ($it_fulfillment_type === 'stock')
                                <div class="mb-3 p-3 border rounded bg-light">
                                    <div wire:loading wire:target="updatedItFulfillmentType">
                                        <span class="spinner-border spinner-border-sm"></span> Memuat daftar stok...
                                    </div>
                                    
                                    <div wire:loading.remove wire:target="updatedItFulfillmentType">
                                        <label for="it_selected_stock_asset_id" class="form-label">Pilih Aset dari Stok <span class="text-danger">*</span></label>
                                        <select wire:model.defer="it_selected_stock_asset_id" id="it_selected_stock_asset_id" class="form-select @error('it_selected_stock_asset_id') is-invalid @enderror">
                                            <option value="">-- Pilih Nomor Aset --</option>
                                            @forelse($available_stock_assets as $stockAsset)
                                                <option value="{{ $stockAsset->id }}">
                                                    {{ $stockAsset->asset_number }} - {{ $stockAsset->name }} (Lokasi: {{ $stockAsset->jobsite->name ?? 'N/A' }})
                                                </option>
                                            @empty
                                                 <option value="" disabled>Tidak ada stok yang cocok ditemukan.</option>
                                            @endforelse
                                        </select>
                                        @error('it_selected_stock_asset_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endif
                        @endif
                    
                        {{-- Textarea untuk catatan (selalu tampil) --}}
                        <div class="mb-0">
                            <label for="approval_notes" class="form-label">Catatan (Wajib jika menolak):</label>
                            <textarea wire:model.defer="approval_notes" class="form-control" id="approval_notes" rows="3"></textarea>
                            @error('approval_notes') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeApprovalModal">Batal</button>
                            <button type="submit" class="btn btn-{{ $approval_action == 'approve' ? 'success' : 'danger' }}">
                                {{ $approval_action == 'approve' ? 'Setujui Pengajuan' : 'Tolak Pengajuan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>