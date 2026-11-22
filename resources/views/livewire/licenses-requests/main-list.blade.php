<div wire:key="main-list-root-container">
    <div class="row">
        <div class="col-sm-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center g-3">
                        <div class="col-md-4">
                            <h5 class="mb-0 fw-bold text-dark">List Pengajuan</h5>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex flex-wrap justify-content-md-end gap-2">
                                <div style="min-width: 120px;">
                                    <select id="jobsites" class="form-select form-select-sm shadow-none"
                                        wire:model="site_filter">
                                        @foreach ($jobsites as $jobsite)
                                            <option value="{{ $jobsite->id }}">{{ $jobsite->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="min-width: 120px;">
                                    <select id="depts" class="form-select form-select-sm shadow-none"
                                        wire:model="dept_filter">
                                        @foreach ($departements as $depts)
                                            <option value="{{ $depts->id }}">{{ $depts->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="min-width: 140px;">
                                    <select id="statusess" class="form-select form-select-sm shadow-none"
                                        wire:model="status_filter">
                                        <option value="">Select Status</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Rejected">Rejected</option>
                                    </select>
                                </div>
                                <div style="min-width: 140px;">
                                    <input type="text" class="form-control form-control-sm shadow-none" placeholder="Search..."
                                        wire:model.live="search" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="text-uppercase text-muted fw-bold"
                                    style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                    <th class="ps-4" style="width: 15%">No Pengajuan</th>
                                    <th style="width: 15%">Tgl Pengajuan</th>
                                    <th style="width: 10%">Status</th>
                                    <th style="width: 45%">Detail Item</th>
                                    <th class="text-center" style="width: 15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->dataRequestApproves() as $request)
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
                                            @php
                                                $color = match ($request->status) {
                                                    'Approved' => 'success',
                                                    'Pending' => 'warning',
                                                    'Rejected' => 'danger',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span
                                                class="badge rounded-pill bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }} px-3">
                                                {{ $request->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-2 py-2">
                                                @foreach ($request->items as $item)
                                                    <div class="p-2 border rounded bg-light-subtle">
                                                        <div
                                                            class="d-flex justify-content-between align-items-start border-bottom pb-1 mb-1">
                                                            <div>
                                                                <div class="fw-bold small text-dark">{{ $item->software_name }}
                                                                </div>
                                                                <div class="text-muted" style="font-size: 0.7rem;">Category:
                                                                    {{ $item->softwareCategory->name }}
                                                                </div>
                                                            </div>
                                                            <div class="text-end">
                                                                <span
                                                                    class="badge bg-white text-dark border fw-normal">{{ $item->quantity }}
                                                                    Unit</span>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center small">
                                                            <div>
                                                                <span class="text-muted">Total:</span>
                                                                <span class="fw-bold text-dark">Rp
                                                                    {{ number_format(($item->price * $item->quantity), 0, ',', '.') }}</span>
                                                            </div>
                                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                                <i class="bi bi-tag me-1"></i>Acc: <span
                                                                    class="text-dark">{{ $item->no_accounting ?? '-' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm">
                                                Detail
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white py-3">
                    {{ $this->dataRequestApproves()->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>