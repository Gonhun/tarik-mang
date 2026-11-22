<div wire:init="Getbilling">
    @if ($errorMessage)
      <div class="container-fluid pt-4"><div class="alert alert-danger d-flex gap-2 mb-0"><i class="bx bx-error-circle fs-5"></i><div>{{ $errorMessage }}</div>
        <button type="button" class="btn btn-sm btn-outline-danger ms-auto" wire:click="Getbilling">Coba lagi</button></div></div>
    @elseif (!$loaded)
      <div class="container-fluid pt-4"><div class="alert alert-secondary d-flex gap-2 mb-0 align-items-center"><span class="spinner-border spinner-border-sm"></span> Mengambil data billing ISR…</div></div>
    @endif

    <div class="container-fluid py-4">
        <div class="nav-wrapper position-relative end-0">
            <div class="row">
                <div class="tab-content col-12">
                    <div class="tab-pane fade active show card">
                        <div class="card-header pb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">AMS</a></li>
                                      <li class="breadcrumb-item active" aria-current="page">Billing ISR</li>
                                    </ol>
                                  </nav>
                            </div>
                            @if (session()->has('message'))
                                <div class="alert alert-success mt-3" role="alert">
                                    {{ session('message') }}
                                </div>
                            @endif
                            @if (session()->has('error'))
                                <div class="alert alert-danger mt-3" role="alert">
                                    {{ session('error') }}
                                </div>
                            @endif
                        </div>
                        <div class="card-body px-0 pt-0 pb-2">
                            {{-- <div class="row px-2 mb-3">
                                
                            </div> --}}
                        <form wire:submit.prevent="Getbilling">
                            <div class="mb-3 px-3">
                                <div class="row">
                                    <div class="col-sm-9 mb-3 mb-sm-0">
                                        <label for="invoice_number" class="form-label">No Invoice</label>
                                        <input 
                                            type="text" 
                                            class="form-control @error('invoice_number') is-invalid @enderror" 
                                            id="invoice_number" 
                                            name="invoice_number" 
                                            placeholder="Kosongi data untuk melihat semua"
                                            wire:model.defer="invoice_number"
                                        >
                                        @error('invoice_number')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    <div class="col-sm-3">
                                        <label for="length_page" class="form-label">Length</label>
                                        <select wire:model.live="page" id="length_page" name="length_page" class="form-select @error('page_length') is-invalid @enderror" wire:loading.attr="disabled">
                                            <option value="10">10</option>
                                            <option value="20">20</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                        @error('page_length')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="px-3">
                                <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled">
                                    <span wire:loading wire:target="Getbilling,page,detail" class="spinner-border spinner-border-sm me-2"></span>
                                    Submit
                                </button>
                            </div>
                        </form>
                            <div class="table-responsive mt-4">
                                {{-- Loading overlay on table when submitting form --}}
                                {{-- 
                                    Loading overlay hanya akan tampil saat metode GetBilling sedang dipanggil oleh Livewire. 
                                    Jika loading selalu muncul, kemungkinan:
                                     1. wire:target="GetBilling" tidak tepat, atau
                                     2. ada error JS/Livewire, atau
                                     3. komponen terus-menerus refresh/submit ulang.
                                    Berikut contoh loading overlay yang hanya muncul saat GetBilling berjalan:
                                --}}
                                {{-- <div 
                                    wire:loading.delay.longer 
                                    wire:target="Getbilling" 
                                    class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center"
                                    style="z-index:10; background:rgba(255,255,255,.75);top:0;left:0;"
                                >
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div> --}}
                                {{-- Jika masih selalu tampil: pastikan tidak ada error di Livewire/component, cek konsol JS, dan pastikan tidak ada wire:loading tanpa .remove atau proses infinite --}}
                                <table class="table table-striped table-hover align-middle" style="position:relative;">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Invoice ID</th>
                                            <th>No Invoice</th>
                                            <th>Customer</th>
                                            <th>App Name</th>
                                            <th>App Number</th>
                                            <th>Dude Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Contoh data dummy --}}
                                        @forelse($data as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item['invoice_id'] ?? '-' }}</td>
                                            <td>{{ $item['invoice_number'] ?? '-' }}</td>
                                            <td>{{ $item['client_name'] ?? '-' }}</td>
                                            <td>{{ $item['application_name'] ?? '-' }}</td>
                                            <td>{{ $item['application_number'] ?? '-' }}</td>
                                            <td>{{ $item['invoice_due_date'] ?? '-' }}</td>
                                            <td>
                                                @if(isset($item['invoice_total_amount']))
                                                    {{ 'Rp ' . number_format($item['invoice_total_amount'], 0, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ ucfirst(strtolower($item['invoice_status'])) == 'Paid' ? 'bg-success' : 'bg-danger' }}">
                                                    {{ ucfirst(strtolower($item['invoice_status'])) ?? '-' }}
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" wire:click="detail('{{ $item['invoice_id'] }}')">Detail</button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="9" class="text-center">Tidak ada data</td>
                                        </tr>
                                        @endforelse
                                        {{-- Tambahkan baris lain sesuai kebutuhan --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>    
        </div>
    </div>
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-xl"> {{-- Modal lebih besar --}}
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">Detail Invoice ID {{$invoice_number}}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col input-group mb-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1">Invoice ID</span>
                                        <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1" value="{{ $detailInv["invoice_id"] }}" disabled>
                                        <span class="input-group-text" id="basic-addon1">Invoice Number</span>
                                        <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1" value="{{ $detailInv["invoice_number"] }}" disabled>
                                        <span class="input-group-text" id="basic-addon1">Publish Date</span>
                                        <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1" value="{{ $detailInv["invoice_publish_date"] }}" disabled>
                                        <span class="input-group-text" id="basic-addon1">Due Date</span>
                                        <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1" value="{{ $detailInv["invoice_due_date"] }}" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col input-group mb-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1">Rp.</span>
                                        <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1" value="{{ number_format($detailInv["invoice_annual_amount"], 0, ',', '.') }}" disabled>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col input-group mb-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1">Type</span>
                                        <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1" value="{{ $detailInv["invoice_type"] }}" disabled>
                                        <span class="input-group-text" id="basic-addon1">Case</span>
                                        <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1" value="{{ $detailInv["invoice_case_type"] }}" disabled>
                                        <span class="input-group-text" id="basic-addon1">Status</span>
                                        <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1" value="{{ $detailInv["invoice_status"] }}" disabled>
                                        <span class="input-group-text" id="basic-addon1">Description</span>
                                        <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1" value="{{ $detailInv["invoice_description"] }}" disabled>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                @if (!empty($detailInv["station_list"]))
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center">No</th>
                                                    <th>Nama Stasiun</th>
                                                    <th>ID Stasiun</th>
                                                    <th>Alamat</th>
                                                    <th class="text-end">Total BHP (Rp)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($detailInv["station_list"] as $idx => $station)
                                                    <tr>
                                                        <td class="text-center">{{ $idx + 1 }}</td>
                                                        <td>{{ $station['nama_stasiun'] }}</td>
                                                        <td>{{ $station['id_stasiun'] }}</td>
                                                        <td>{{ $station['alamat_stasiun'] ?? '-' }}</td>
                                                        <td class="text-end">{{ number_format($station['total_bhp_stasiun'], 0, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info mb-0">
                                        Tidak ada data stasiun yang terkait dengan invoice ini.
                                    </div>
                                @endif
                            </div>
                            <div class="row my-4">
                                <div class="col d-flex justify-content-center gap-3">
                                    <button type="button" class="btn btn-success"
                                            onclick="window.open('https://ainya.binasaranasukses.co.id/webhook/UpdateISR?inv={{$invoice_number}}', '_blank')"
                                    >
                                        <i class="bx bx-download"></i> Download Invoice
                                    </button>
                                    <button type="button" class="btn btn-primary"
                                        @if(strtolower($detailInv["invoice_status"]) !== "paid")
                                            disabled
                                        @else
                                            onclick="window.open('https://ainya.binasaranasukses.co.id/webhook/DownloadISR?inv={{$invoice_number}}', '_blank')"
                                        @endif
                                    >
                                        <i class="bx bx-download"></i> Download Licens
                                    </button>
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            {{-- <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">Simpan</span>
                                <span wire:loading wire:target="store">Menyimpan...</span>
                            </button> --}}
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
