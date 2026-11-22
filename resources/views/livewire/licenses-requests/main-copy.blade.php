<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-sm-12">
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
                <div class="card">
                    <div class="card-header d-flex justify-content-between pb-0">
                        <h5 class="mb-0">License Requests</h5>
                        <div class="ms-auto align-items-baseline d-flex">
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    {{-- Tombol BARU untuk Ekspor --}}
                                    {{-- <button wire:click="exportExcel" class="btn btn-success btn-sm mb-0 me-2">
                                        <span wire:loading.remove wire:target="exportExcel">
                                            <i class="bx bx-bxs-file-export me-1"></i> Ekspor ke Excel
                                        </span>
                                        <span wire:loading wire:target="exportExcel">
                                            <span class="spinner-border spinner-border-sm" role="status"
                                                aria-hidden="true"></span>
                                            Mengekspor...
                                        </span>
                                    </button> --}}

                                    {{-- Tombol Tambah Aset --}}
                                    <a href="{{ route('requests.license.add') }}" class="btn btn-primary btn-sm mb-3">
                                        <i class="bx bx-bxs-file-plus me-1"></i> Tambah Pengajuan Baru
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <nav>
                            <div class="nav nav-tabs" id="licenseTab" role="tablist">
                                <button @class(['nav-link', 'active' => $activeTab === 'me-tab-pane'])
                                    wire:click.prevent="switchTab('me-tab-pane')" type="button" role="tab">
                                    Outstanding Pengajuan Lisensi anda
                                </button>

                                <button @class(['nav-link', 'active' => $activeTab === 'list-tab-pane'])
                                    wire:click.prevent="switchTab('list-tab-pane')" type="button" role="tab">
                                    Pengajuan Lisensi Outstanding
                                </button>
                            </div>
                        </nav>
                        <div class="tab-content" id="licenseTabContent">
                            <div @class(['tab-pane fade', 'show active' => $activeTab === 'me-tab-pane']) id="me-tab-pane"
                                role="tabpanel">
                                <div class="row pt-3">
                                    @forelse ($this->dataRequestess() as $request)
                                        <div class="col-12 mb-3">
                                            <div class="card shadow-sm border border-light">
                                                <div class="card-body p-3">
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
                                                                            <td class="text-sm align-middle">{{ $item->quantity }}</td>
                                                                            <td class="text-sm align-middle">
                                                                                @if($item->approvals->where('status', 'Rejected')->first())
                                                                                    @php
                                                                                        $approval = $item->approvals->where('status', 'Rejected')->first();
                                                                                    @endphp
                                                                                    <div class="text-danger text-xs">
                                                                                        <strong>Rejected by:</strong> {{ $approval && $approval->approver ? $approval->approver->name : '-' }}<br>
                                                                                        <strong>Alasan:</strong> {{ $approval ? $approval->remarks : '-' }}
                                                                                    </div>
                                                                                @else
                                                                                    @php
                                                                                        $approval = $item->approvals->where('status', 'Pending')->first();
                                                                                    @endphp
                                                                                    <div class="d-flex align-items-center">
                                                                                        <span class="badge badge-sm bg-gradient-{{ $item->status == 'Approved' ? 'success' : ($item->status == 'Pending' ? 'warning' : 'secondary') }} me-2">
                                                                                            {{ ucfirst($item->status) ?? '-' }}
                                                                                        </span>
                                                                                        <span class="text-xs text-secondary">{{ $approval ? $approval->approver->name : '' }}</span>
                                                                                    </div>
                                                                                @endif
                                                                                <button class="btn btn-link text-secondary btn-sm mb-0 px-0 ms-2"
                                                                                    wire:click="showStatusHistory({{ $item->id }})"
                                                                                    title="Lihat riwayat status">
                                                                                    <i class="bi bi-clock-history text-lg"></i>
                                                                                </button>
                                                                            </td>
                                                                            <td class="text-sm align-middle">
                                                                                @if(isset($item))
                                                                                    @if($item->approvals->where('status', 'Rejected')->first() || ($item && $item->approvals->where('status', 'Pending')->count() === 0))
                                                                                        <button class="btn btn-outline-danger btn-sm mb-0" disabled>Cancel</button>
                                                                                    @else
                                                                                        <button class="btn btn-outline-danger btn-sm mb-0">Cancel</button>
                                                                                    @endif
                                                                                @endif
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
                                            <div class="alert alert-info text-white" role="alert">
                                                <strong>Informasi!</strong> Belum ada pengajuan lisensi dari Anda.
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                                <div class="px-4 pt-3">
                                    {{ $this->dataRequestess()->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                            <div @class(['tab-pane fade', 'show active' => $activeTab === 'list-tab-pane'])
                                id="list-tab-pane" role="tabpanel">
                                <div class="d-flex Justify-content-between p-2">
                                    <div class="ms-auto align-items-center">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-auto">
                                                <select id="jobsites" class="form-select form-select-sm mb-2"
                                                    aria-label="Select Jobsite" wire:model="site_filter">
                                                    @foreach ($jobsites as $jobsite)
                                                        <option value="{{ $jobsite->id }}">
                                                            {{ $jobsite->code }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <select id="depts" class="form-select form-select-sm mb-2"
                                                    aria-label="Select Dept" wire:model="dept_filter">
                                                    @foreach ($departements as $depts)
                                                        <option value="{{ $depts->id }}">{{ $depts->code }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <select id="statusess" class="form-select form-select-sm mb-2"
                                                    aria-label="Select Status" wire:model="status_filter">
                                                    <option value="">Select Status</option>
                                                    <option value="Pending">Pending</option>
                                                    <option value="Approved">Approved</option>
                                                    <option value="Rejected">Rejected</option>
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <input type="text" class="form-control form-control-sm mb-2" placeholder="Search by purpose, request number, software name, or category..." wire:model.live="search">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row pt-1">
                                    @forelse ($this->dataRequestApproves() as $request)
                                        <div class="col-12 mb-3">
                                            <div class="card shadow-sm border border-light">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <h6 class="mb-0 text-primary font-weight-bolder">
                                                            <i class="bx bx-receipt me-1"></i> Request No: {{ $request->request_no }}
                                                        </h6>
                                                        <button class="btn btn-sm btn-outline-primary mb-0" type="button" data-bs-toggle="collapse"
                                                            data-bs-target="#collapse-app-{{ $request->request_no }}">
                                                            Detail <i class="bx bx-caret-down ms-1"></i>
                                                        </button>
                                                    </div>
                                                    <p class="text-sm mb-0 text-secondary">
                                                        <strong>Catatan:</strong> {{ $request->purpose }}
                                                    </p>
                                                </div>
                                                
                                                <div class="collapse" id="collapse-app-{{ $request->request_no }}">
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
                                                                                </div>
                                                                            </td>
                                                                            <td class="text-sm align-middle">{{ $item->softwareCategory->name ?? '-' }}</td>
                                                                            <td class="text-sm align-middle">{{ $item->softwareManufacturer->name ?? '-'}}</td>
                                                                            <td class="text-sm align-middle">{{ $item->quantity }}</td>
                                                                            <td class="text-sm align-middle">
                                                                                @if($item->approvals->where('status', 'Rejected')->first())
                                                                                    @php
                                                                                        $approval = $item->approvals->where('status', 'Rejected')->first();
                                                                                    @endphp
                                                                                    <div class="text-danger text-xs">
                                                                                        <strong>Rejected by:</strong> {{ $approval && $approval->approver ? $approval->approver->name : '-' }}<br>
                                                                                        <strong>Alasan:</strong> {{ $approval ? $approval->remarks : '-' }}
                                                                                    </div>
                                                                                @else
                                                                                    @php
                                                                                        $approval = $item->approvals->where('status', 'Pending')->first();
                                                                                    @endphp
                                                                                    <div class="d-flex align-items-center">
                                                                                        <span class="badge badge-sm bg-gradient-{{ $item->status == 'Approved' ? 'success' : ($item->status == 'Pending' ? 'warning' : 'secondary') }} me-2">
                                                                                            {{ ucfirst($item->status) ?? '-' }}
                                                                                        </span>
                                                                                        <span class="text-xs text-secondary">{{ $approval ? $approval->approver->name : '' }}</span>
                                                                                    </div>
                                                                                @endif
                                                                                <button class="btn btn-link text-secondary btn-sm mb-0 px-0 ms-2"
                                                                                    wire:click="showStatusHistory({{ $item->id }})"
                                                                                    title="Lihat riwayat status">
                                                                                    <i class="bi bi-clock-history text-lg"></i>
                                                                             </button>
                                                                            </td>
                                                                            <td class="text-sm align-middle">
                                                                                @if(isset($item))
                                                                                    @if($item->approvals->where('status', 'Rejected')->first() || ($item && $item->approvals->where('status', 'Pending')->count() === 0))
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
                                                                                        <strong>Harga per item:</strong> Rp {{ number_format($item->price, 0, ',', '.') }} <span class="mx-1">|</span>
                                                                                        <strong>Harga Total:</strong> Rp {{ number_format(($item->price * $item->quantity), 0, ',', '.') }} <span class="mx-1">|</span>
                                                                                        <strong>No Accounting:</strong> {{ $item->no_accounting ?? '-' }}
                                                                                    </span>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    @empty
                                                                        <tr>
                                                                            <td colspan="6" class="text-center py-3 text-sm">Tidak ada item pada pengajuan ini.</td>
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
                                            <div class="alert alert-info text-white" role="alert">
                                                <strong>Informasi!</strong> Tidak ada pengajuan lisensi.
                                            </div>
                                        </div>
                                    @endforelse
                                </div>

            <div class="px-4 pt-3">
                {{ $this->dataRequestApproves()->links('pagination::bootstrap-5', ['pageName' => 'list-license']) }}
            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


        {{-- <div class="accordion" id="accordionLicenses">
            <!-- Accordion Item: Pengajuan Lisensi -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingRequest">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseRequest" aria-expanded="true" aria-controls="collapseRequest">
                        Daftar Pengajuan Lisensi
                    </button>
                </h2>
                <div id="collapseRequest" class="accordion-collapse collapse show" aria-labelledby="headingRequest"
                    data-bs-parent="#accordionLicenses">
                    <div class="accordion-body">
                        <!--- Accordion Items -->
                        <div class="accordion-item" wire:ignore>
                            @forelse ($requests as $request)
                            <h2 class="accordion-header" id="headingApproval">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#{{  $request->request_no  }}-collapse" aria-expanded="false"
                                    aria-controls="{{  $request->request_no  }}-collapse">
                                    Request No: {{ $request->request_no }}
                                </button>
                            </h2>
                            <div id="{{  $request->request_no  }}-collapse" class="accordion-collapse collapse"
                                aria-labelledby="headingApproval"
                                data-bs-parent="#{{  $request->request_no  }}-collapse">
                                <div class="accordion-body">
                                    <p><strong>Catatan:</strong> {{ $request->purpose }}</p>
                                </div>
                                <div class="accordion-body">
                                    <table class="table table-sm table-bordered mb-4">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama Software</th>
                                                <th>Kategori</th>
                                                <th>Pabrikan</th>
                                                <th>Jumlah</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($request->items as $item)
                                            <tr wire:key="request-item-{{ $item->id }}">
                                                <td>{{ $item->software_name }} {{$item->id}}
                                                </td>
                                                <td>{{ $item->softwareCategory->name ?? '-' }}
                                                </td>
                                                <td>{{ $item->softwareManufacturer->name ?? '-'
                                                    }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>
                                                    @if($item->approvals->where('status',
                                                    'Rejected')->first())
                                                    @php
                                                    $approval = $item->approvals
                                                    ->where('status', 'Rejected')
                                                    ->first();
                                                    @endphp
                                                    <div class="text-danger">
                                                        <strong>Rejected by:</strong> {{
                                                        $approval &&
                                                        $approval->approver ?
                                                        $approval->approver->name
                                                        : '-'
                                                        }}<br>
                                                        <strong>Alasan:</strong> {{ $approval ?
                                                        $approval->remarks : '-' }}
                                                    </div>
                                                    @else
                                                    @php
                                                    $approval = $item->approvals
                                                    ->where('status', 'Pending')
                                                    ->first();
                                                    @endphp
                                                    <span>
                                                        {{ ucfirst($item->status) ?? '-' }}
                                                        {{ $approval ? $approval->approver->name
                                                        : '' }}
                                                    </span>
                                                    @endif
                                                    <button class="btn btn-link btn-sm p-0"
                                                        wire:click="showStatusHistory({{ $item->id }})"
                                                        title="Lihat riwayat status">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                </td>
                                                <td>
                                                    @if(isset($item))
                                                    @if($item->approvals->where('status',
                                                    'Rejected')->first()
                                                    || $item &&
                                                    $item->approvals->where('status',
                                                    'Pending')->count() === 0)
                                                    <button class="btn btn-danger btn-sm" disabled>Cancel</button>
                                                    @else
                                                    <button class="btn btn-sm btn-danger">Cancel</button>
                                                    @endif
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="6">Spesifikasi : {{ $item->remarks
                                                    }} </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="text-center">Tidak ada
                                                    item pada
                                                    pengajuan ini.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @empty
                            <div class="alert alert-info">Belum ada pengajuan Lisensi yang
                                diajukan.
                            </div>
                            @endforelse
                            <div class="d-flex justify-content-center mt-3">
                                {{ $requests->links() }}
                            </div>
                        </div>
                        <!--- End Accordion Items -->
                    </div>
                </div>
            </div>

            <!-- Accordion Item: Lisensi Perlu Persetujuan -->
            @if ($requestsApprove->count() > 0)
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingApproval">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseApproval" aria-expanded="false" aria-controls="collapseApproval">
                        Lisensi Perlu Persetujuan
                    </button>
                </h2>
                <div id="collapseApproval" class="accordion-collapse collapse" aria-labelledby="headingApproval"
                    data-bs-parent="#accordionLicenses">
                    <div class="accordion-body">
                        <div class="accordion-item" wire:ignore>
                            @forelse ($requestsApprove as $request)
                            <h2 class="accordion-header" id="headingApproval">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#Action-{{  $request->request_no  }}-collapse" aria-expanded="false"
                                    aria-controls="Action-{{  $request->request_no  }}-collapse">
                                    Request No: {{ $request->request_no }}
                                </button>
                            </h2>
                            <div id="Action-{{  $request->request_no  }}-collapse" class="accordion-collapse collapse"
                                aria-labelledby="headingApproval"
                                data-bs-parent="#Action-{{  $request->request_no  }}-collapse">
                                <div class="accordion-body">
                                    <p><strong>Catatan:</strong> {{ $request->purpose }}</p>
                                </div>
                                <div class="accordion-body">
                                    <table class="table table-sm table-bordered mb-4">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama Software</th>
                                                <th>Kategori</th>
                                                <th>Pabrikan</th>
                                                <th>Jumlah</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($request->items as $item)
                                            <tr wire:key="request-item-{{ $item->id }}">
                                                <td>{{ $item->software_name }}</td>
                                                <td>{{ $item->softwareCategory->name ?? '-' }}
                                                </td>
                                                <td>{{ $item->softwareManufacturer->name ?? '-'
                                                    }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>

                                                    @if($item->approvals->where('status',
                                                    'Rejected')->first())
                                                    @php
                                                    $approval = $item->approvals
                                                    ->where('status', 'Rejected')
                                                    ->first();
                                                    @endphp
                                                    <div class="text-danger">
                                                        <strong>Rejected by:</strong> {{
                                                        $approval &&
                                                        $approval->approver ?
                                                        $approval->approver->name
                                                        : '-'
                                                        }}<br>
                                                        <strong>Alasan:</strong> {{ $approval ?
                                                        $approval->remarks : '-' }}
                                                    </div>
                                                    @else
                                                    @php
                                                    $approval = $item->approvals
                                                    ->where('status', 'Pending')
                                                    ->first();
                                                    @endphp
                                                    <span>
                                                        {{ ucfirst($item->status) ?? '-' }}
                                                        {{ $approval ? $approval->approver->name
                                                        : '-'
                                                        }}
                                                    </span>

                                                    @endif

                                                </td>
                                                <td>
                                                    @if(isset($item))
                                                    @if($item->approvals->where('status',
                                                    'Rejected')->first())
                                                    <button class="btn btn-danger btn-sm" disabled>Rejected</button>
                                                    @elseif($item->approvals->where('user_id',
                                                    Auth::id())->where('status',
                                                    'Approved')->first())
                                                    <button class="btn btn-success btn-sm" disabled>Approved</button>
                                                    @elseif($item->where('status',
                                                    'Canceled')->first())
                                                    <button class="btn btn-danger btn-sm" disabled>Canceled</button>
                                                    @else

                                                    @if ($item->approvals->where('user_id',
                                                    Auth::id())->where('status',
                                                    'Pending')->first())
                                                    <button class="btn btn-success btn-sm"
                                                        wire:click="showApproveModal({{ $item->id }})">Approve</button>
                                                    <button class="btn btn-danger btn-sm"
                                                        wire:click="showRejectModal({{ $item->id }})">Reject</button>
                                                    @endif
                                                    @endif
                                                    @endif

                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="6">Spesifikasi : {{ $item->remarks
                                                    }} </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="text-center">Tidak ada
                                                    item pada
                                                    pengajuan ini.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @empty
                            <div class="alert alert-info">Tidak ada pengajuan lisensi yang
                                membutuhkan
                                persetujuan</div>
                            @endforelse
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $requests->links() }}
                        </div>

                        <!--- End Accordion Items -->
                    </div>
                </div>
            </div>
            @endif


        </div> --}}

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
                                    <ul class="list-group mb-3">
                                        <li class="list-group-item">
                                            <strong>Nama Lisensi:</strong> {{ $item_req->software_name ?? '-' }}
                                        </li>
                                        <li class="list-group-item">
                                            <strong>Pemohon:</strong> {{ $item_req->request->user->name ?? '-' }}
                                        </li>
                                        <li class="list-group-item">
                                            <strong>Tanggal Pengajuan:</strong>
                                            {{ $item_req->created_at ? $item->created_at->format('Y-m-d') : '-' }}
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
        @if($approveModal)
            <div id="approveModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <form wire:submit.prevent="{{ $action }}">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ $action == 'approve' ? 'Approve' : 'Reject' }}</h5>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Nama Software:</strong></label>
                                    <div>{{ isset($item) ? $item->software_name : '-' }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="remarks">Remarks (Wajib jika menolak)</label>
                                    <textarea class="form-control" wire:model="remarks"></textarea>
                                    @error('remarks') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    wire:click="closeApproveModal">Batal</button>
                                <button type="submit"
                                    class="btn btn-primary">{{ $action == 'approve' ? 'Approve' : 'Reject' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
