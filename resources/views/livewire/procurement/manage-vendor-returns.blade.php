<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Manajemen QC Assets</h5>
                <p class="text-sm mb-0">Daftar item aset yang gagal proses QC dan memerlukan tindakan lanjut.</p>
            </div>
            <div class="card-body">
                @if (session()->has('message'))
                    <div class="alert alert-success" role="alert">
                        {{ session('message') }}
                    </div>
                @endif
                 @if (session()->has('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No. Pengajuan</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Item Gagal QC</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Alasan Gagal</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Tindakan Lanjut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($failedItems as $item)
                                <tr wire:key="failed-item-{{ $item->id }}">
                                    <td>
                                        <p class="text-sm font-weight-bold mb-0">{{ $item->assetRequest->request_number ?? 'N/A' }}</p>
                                    </td>
                                    <td>
                                        <p class="text-sm font-weight-bold mb-0">{{ $item->assetSeries->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-muted mb-0">Jumlah: {{ $item->quantity }}</p>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0" title="{{ $item->procurement_notes }}">
                                            {{ Str::limit($item->procurement_notes, 50) }}
                                        </p>
                                    </td>
                                    <td class="text-center">
                                        {{-- Tombol ini sekarang membuka modal untuk input manual --}}
                                        <button wire:click="openUpdateModal({{ $item->id }})" 
                                                class="btn btn-sm btn-warning mb-0"
                                                title="Update data PR/PO/Akunting baru untuk item ini.">
                                            Update Data (Return)
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <p class="text-muted">Tidak ada item yang memerlukan proses return saat ini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $failedItems->links() }}
                </div>
            </div>
        </div>
    </div>
    @if($isUpdateModalOpen && $itemToUpdate)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form wire:submit.prevent="saveUpdatedItemDetails">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Data Pengadaan (Return)</h5>
                        <button type="button" class="btn-close" wire:click="closeUpdateModal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda sedang memproses ulang item <strong>{{ $itemToUpdate->assetSeries->name }}</strong> dari pengajuan <strong>{{ $itemToUpdate->assetRequest->request_number }}</strong>.</p>
                        <p class="small text-muted">Silakan masukkan data PR/Akunting/PO yang baru untuk item pengganti.</p>
                        <hr>
                        <div class="mb-3">
                            <label for="update_pr_number" class="form-label">Nomor PR <span class="text-danger">*</span></label>
                            <input type="text" wire:model.defer="update_pr_number" class="form-control @error('update_pr_number') is-invalid @enderror" id="update_pr_number">
                            @error('update_pr_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="update_gr_number" class="form-label">Nomor GR <span class="text-danger">*</span></label>
                            <input type="text" wire:model.defer="update_gr_number" class="form-control @error('update_gr_number') is-invalid @enderror" id="update_gr_number">
                            @error('update_gr_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="update_po_number" class="form-label">Nomor PO <span class="text-danger">*</span></label>
                            <input type="text" wire:model.defer="update_po_number" class="form-control @error('update_po_number') is-invalid @enderror" id="update_po_number">
                            @error('update_po_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeUpdateModal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            Simpan & Kembalikan ke Antrian QC
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>