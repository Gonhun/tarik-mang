<div>
    <div class="container-fluid py-4">
        <div class="nav-wrapper position-relative end-0">
            <div class="row">
                <div class="tab-content col-12">
                    <div class="tab-pane fade active show card">
                        <div class="card-header pb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                      <li class="breadcrumb-item"><a href="{{ route('licenses.main') }}">License</a></li>
                                      <li class="breadcrumb-item active" aria-current="page">{{$softwareCollection->name}}</li>
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
                            <div class="accordion" id="licenseAccordion">
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header bg-secondary" id="headingUsers">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsers" aria-expanded="true" aria-controls="collapseUsers">
                                            Detail#
                                        </button>
                                    </h2>
                                    <div id="collapseUsers" class="accordion-collapse collapse show" aria-labelledby="headingUsers" data-bs-parent="#licenseAccordion">
                                        <div class="accordion-body">
                                            <dl class="row pt-2">
                                                <dt class="col-sm-3">Manufacture</dt>
                                                <dd class="col-sm-9">{{ $softwareCollection->softwareManufacturer->name }}</dd>
            
                                                <dt class="col-sm-3">Category</dt>
                                                <dd class="col-sm-9">
                                                    <p>{{ $softwareCollection->softwareCategory->name }}</p>
                                                </dd>
                                                
                                                <dt class="col-sm-3">More Info</dt>
                                                <dd class="col-sm-9">
                                                    <dl class="row mb-0">
                                                        
                                                        <dt class="col-sm-3">Order Number</dt>
                                                        <dd class="col-sm-3">PO Number</dd>
                                                        <dt class="col-sm-3">Purchase Date</dt>
                                                        <dd class="col-sm-3">Purchase Cost</dt>
                                                        <dd class="col-sm-3">{{$softwareCollection->order_number ?? '-'}}</dd>
                                                        <dt class="col-sm-3">{{$softwareCollection->po_number ?? '-'}}</dt>
                                                        <dd class="col-sm-3"> {{ $softwareCollection->purchase_date ? \Carbon\Carbon::parse($softwareCollection->purchase_date)->format('d M Y') : '-' }}</dd>
                                                        <dt class="col-sm-3">
                                                            {{ 'Rp ' . number_format($softwareCollection->purchase_cost ?? 0, 0, ',', '.') }}
                                                        </dt>
                                                    </dl>
                                                </dd>
                                                <dt class="col-sm-3">License Key</dt>
                                                <dd class="col-sm-9"><code>{{ $softwareCollection->product_key }}</code></dd>
            
                                                <dt class="col-sm-3 text-truncate">Expiration Date</dt>
                                                <dd class="col-sm-9">{{ $softwareCollection->expiration_date ? \Carbon\Carbon::parse($softwareCollection->expiration_date)->format('d M Y') : '-' }}</dd>
            
                                                <dt class="col-sm-3">Added By</dt>
                                                <dd class="col-sm-9">{{ $softwareCollection->createdBy->name ? $softwareCollection->createdBy->name : '-' }}</dd>
            
                                                <dt class="col-sm-3">Seats</dt>
                                                <dd class="col-sm-9">
                                                    <dl class="row mb-0">
                                                        <dt class="col-sm-4">Total</dt>
                                                        <dd class="col-sm-4">Used</dd>
                                                        <dt class="col-sm-4">Free</dt>
                                                        <dd class="col-sm-4">{{$softwareCollection->seats ? $softwareCollection->seats :0}}</dd>
                                                        <dt class="col-sm-4">{{$softwareCollection->active_checkout_count ?? 0}}</dt>
                                                        <dd class="col-sm-4"> {{ ($sofware->seats ?? 0) - ($sofware->active_checkout_count ?? 0) }}</dd>
                                                    </dl>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingHistory">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#headingUseed" aria-expanded="false" aria-controls="collapseHistory">
                                            Pengguna License {{$softwareCollection->name}}
                                        </button>
                                    </h2>
                                    <div id="headingUseed" class="accordion-collapse collapse" aria-labelledby="headingUseed" data-bs-parent="#licenseAccordion">
                                        <div class="accordion-body">
                                            @php
                                                $assignments = $softTransCollection->filter(function($item) {
                                                    return $item->transaction_type === 'assign';
                                                });
                                            @endphp

                                            @if($assignments->count())
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>NIK</th>
                                                                <th>User</th>
                                                                <th>Asset</th>
                                                                <th>Assigned At</th>
                                                                <th>Note</th>
                                                                <th>Created By</th>
                                                                <th>Checkout</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($assignments as $idx => $trans)
                                                                <tr>
                                                                    <td>{{ $idx + 1 }}</td>
                                                                    <td>{{ $trans->user->nik ?? '-' }}</td>
                                                                    <td>{{ $trans->user->name ?? '-' }}</td>
                                                                    <td>{{ $trans->asset->asset_number ?? '-' }}</td>
                                                                    <td>{{ $trans->assigned_at ? \Carbon\Carbon::parse($trans->assigned_at)->format('d M Y H:i') : '-' }}</td>
                                                                    <td>{{ $trans->note ?? '-' }}</td>
                                                                    <td>{{ $trans->creator->name ?? '-' }}</td>
                                                                    <td>
                                                                        <button class="btn btn-sm btn-danger" 
                                                                        type="button"
                                                                        wire:click="Checkout({{ $trans->id }})"
                                                                        wire:confirm="Apakah yakin Checkout License ini?"
                                                                        >Checkout</button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                {{-- Pagination --}}
                                                <div>
                                                    {{ $softTransCollection->links() }}
                                                </div>
                                            @else
                                                <strong>Tidak ada data assign license.</strong>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingHistory">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHistory" aria-expanded="false" aria-controls="collapseHistory">
                                            History Return License {{$softwareCollection->name}}
                                        </button>
                                    </h2>
                                    <div id="collapseHistory" class="accordion-collapse collapse" aria-labelledby="headingHistory" data-bs-parent="#licenseAccordion">
                                        <div class="accordion-body">
                                            @php
                                                $assignments = $softTransCollection->filter(function($item) {
                                                    return $item->transaction_type !== 'assign';
                                                });
                                            @endphp

                                            @if($assignments->count())
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>NIK</th>
                                                                <th>User</th>
                                                                <th>Asset</th>
                                                                <th>Assigned At</th>
                                                                <th>Return At</th>
                                                                <th>Note</th>
                                                                <th>Created By</th>
                                                                
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($assignments as $idx => $trans)
                                                                <tr>
                                                                    <td>{{ $idx + 1 }}</td>
                                                                    <td>{{ $trans->user->nik ?? '-' }}</td>
                                                                    <td>{{ $trans->user->name ?? '-' }}</td>
                                                                    <td>{{ $trans->asset->asset_number ?? '-' }}</td>
                                                                    <td>{{ $trans->assigned_at ? \Carbon\Carbon::parse($trans->assigned_at)->format('d M Y H:i') : '-' }}</td>
                                                                    <td>{{ $trans->returned_at ? \Carbon\Carbon::parse($trans->returned_at)->format('d M Y H:i') : '-' }}</td>
                                                                    <td>{{ $trans->note ?? '-' }}</td>
                                                                    <td>{{ $trans->creator->name ?? '-' }}</td>
                                                                   
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                {{-- Pagination --}}
                                                <div>
                                                    {{ $softTransCollection->links() }}
                                                </div>
                                            @else
                                                <strong>Tidak ada data license.</strong>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>    
        </div>
    </div>
</div>
