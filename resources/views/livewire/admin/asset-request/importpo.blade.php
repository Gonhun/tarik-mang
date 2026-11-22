<div>
    <div class="container-fluid py-4">
        <div class="card-header">
            <h5 class="mb-0">Proses Pengadaan - Input Purchase Order (PO)</h5>
            <p class="text-sm mb-0">Halaman ini berisi daftar pengajuan yang siap untuk diinput Nomor PO-nya.</p>
        </div>
        <div class="card-body">
            @if (session()->has('global-message'))
                <div class="alert alert-success" role="alert">
                    {{ session('global-message') }}
                </div>
            @endif
            <div class="col-lg-12 mb-lg-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header pb-4">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-0">Riwayat 5 Pengajuan Terakhir Saya</h6>
                            @if(!empty($myRequests))
                                <button type="button" class="btn btn-sm btn-primary mb-0" wire:click="create()">
                                    Upload Excel
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <div class="table-responsive">
                                <table id="myTable" class="table table-hover align-items-center mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">No. Pengajuan</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Items</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Quantity</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                                <span class="d-flex align-items-center gap-2">
                                                    <span>
                                                        <i class="fas fa-file-signature text-primary me-1"></i> 
                                                        <span class="fw-bold">No. PR</span>
                                                    </span>
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-sm btn-outline-success py-0 px-2 ms-1" 
                                                        style="font-size: 0.75rem; border-radius: 12px;"
                                                        title="Salin semua No. PR ke clipboard"
                                                        onclick="copyPRColumn()"
                                                    >
                                                        <i class="bx bx-copy-alt"></i> Copy
                                                    </button>
                                                </span>
                                            </th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">No Asset</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">PO</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">GR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($myRequests as $request)
                                            @foreach ($request->items as $item)
                                            <tr class="border-bottom">
                                                <td>
                                                    <div class="d-flex px-3 py-1">
                                                        <div class="d-flex flex-column justify-content-center">
                                                            <h6 class="mb-0 text-sm">{{ $request->request_number }}</h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="text-xs font-weight-bold mb-0 px-2">{{ $item->assetSeries->name ?? '-' }}</p>
                                                </td>
                                                <td>
                                                    <span class="badge badge-sm bg-gradient-success px-3 py-2">{{ $item->quantity }}</span>
                                                </td>
                                                <td>
                                                    <p class="text-xs font-weight-bold mb-0 px-2">{{ $item->pr_number }}</p>
                                                </td>
                                                <td>
                                                    <p class="text-xs font-weight-bold mb-0 px-2">{{ $item->accounting_number }}</p>
                                                </td>
                                                <td>
                                                    @if($item->po_number)
                                                        <span class="badge badge-sm bg-gradient-info px-3 py-2">{{ $item->po_number }}</span>
                                                    @else
                                                        <span class="badge badge-sm bg-gradient-secondary px-3 py-2">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item->gr_number)
                                                        <span class="badge badge-sm bg-gradient-success px-3 py-2">{{ $item->gr_number }}</span>
                                                    @else
                                                        <span class="badge badge-sm bg-gradient-secondary px-3 py-2">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="fas fa-inbox fa-2x text-secondary mb-2"></i>
                                                        <p class="text-muted mb-0">Anda belum memiliki pengajuan aset.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                             <div class="mt-2 px-3">
                                {{ $myRequests->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form wire:submit.prevent="uploadExcel">
                        <div class="modal-header">
                            <h5 class="modal-title">Imports Excel Report SAP</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="excelFile" class="form-label">Pilih File Excel</label>
                                <input type="file" class="form-control" id="excelFile" wire:model="excelFile">
                                @error('excelFile') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <div class="alert alert-info">
                                    <small>
                                        <strong>Format Excel yang Diperlukan:</strong><br>
                                        -SAP REPORT
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="uploadExcel">Upload</span>
                                <span wire:loading wire:target="uploadExcel">Matching...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    <!-- Modal Upload Excel -->
</div>
@push('scripts')
<script>
    function copyPRColumn() {
        let table = document.getElementById('myTable');
        let rows = table.querySelectorAll('tbody tr');
        let values = [];
    
        rows.forEach(row => {
            let cell = row.cells[3]; // kolom PR adalah index 0
            if (cell) values.push(cell.innerText.trim());
        });
    
        let textToCopy = values.join('\n');
    
        navigator.clipboard.writeText(textToCopy)
            .then(() => alert('PR column copied!'))
            .catch(err => alert('Failed to copy: ' + err));
    }
    </script>
@endpush