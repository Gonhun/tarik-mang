<div>
    <div class="d-flex Justify-content-between p-2">
        <div class="ms-auto align-items-center">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <select id="jobsites" class="form-select form-select-sm mb-2" aria-label="Select Jobsite"
                        wire:model="site_filter">
                        @foreach ($jobsites as $jobsite)
                            <option value="{{ $jobsite->id }}">
                                {{ $jobsite->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select id="depts" class="form-select form-select-sm mb-2" aria-label="Select Dept"
                        wire:model="dept_filter">
                        @foreach ($departements as $depts)
                            <option value="{{ $depts->id }}">{{ $depts->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select id="statusess" class="form-select form-select-sm mb-2" aria-label="Select Status"
                        wire:model="status_filter">
                        <option value="">Select Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive p-1">
        <table class="table table-bordered align-items-center mb-0 flex-nowrap">
            <thead>
                <tr>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                        List
                        Pengajuan Lisensi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->dataRequestApproves() as $request)
                    <tr wire:key="detail-{{ $request->request_no }}">
                        <td>
                            <button class="btn btn-sm" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-{{ $request->request_no }}">
                                <span class="bx bx-caret-down"></span>
                                
                            </button>
                            Request No: {{ $request->request_no }}
                        </td>
                    </tr>
                    <tr class="collapse" id="collapse-{{ $request->request_no }}">
                        <td>
                            <p><strong>Catatan:</strong> {{ $request->purpose }}</p>
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
                                            <td>{{ $item->software_name }} {{$item->id}}</td>
                                            <td>{{ $item->softwareCategory->name ?? '-' }}</td>
                                            <td>{{ $item->softwareManufacturer->name ?? '-'}}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>
                                                @if($item->approvals->where('status', 'Rejected')->first())
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
                                                    @if(
                                                            $item->approvals->where(
                                                                'status',
                                                                'Rejected'
                                                            )->first()
                                                            || $item &&
                                                            $item->approvals->where(
                                                                'status',
                                                                'Pending'
                                                            )->count() === 0
                                                        )
                                                        <button class="btn btn-danger btn-sm" disabled>Cancel</button>
                                                    @else
                                                        <button class="btn btn-sm btn-danger">Cancel</button>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="6">Spesifikasi :
                                                {{ $item->remarks
                                                                                                                                                                                                            }}
                                            </td>
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
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-4 pt-3">
            {{ $this->dataRequestApproves()->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>