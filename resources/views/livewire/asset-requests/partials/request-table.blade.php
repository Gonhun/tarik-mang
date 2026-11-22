<div class="table-responsive">
    <table class="table align-items-center mb-0">
        <thead>
            <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No. Pengajuan</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Pemohon</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Jobsite</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tgl Pengajuan</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Pending Di</th> {{-- <-- KOLOM BARU --}}
                <th class="text-secondary opacity-7"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requests as $request)
                <tr>
                    <td><p class="text-sm font-weight-bold mb-0 px-3">{{ $request->request_number }}</p></td>
                    <td><p class="text-sm mb-0">{{ $request->requester->name ?? 'N/A' }}</p></td>
                    <td><p class="text-sm mb-0">{{ $request->jobsite->name ?? 'N/A' }}</p></td>
                    <td><p class="text-sm mb-0">{{ $request->request_date->isoFormat('D MMM YYYY') }}</p></td>
                    <td>
                        <span class="badge badge-sm bg-gradient-info">{{ $request->status }}</span>
                    </td>
                    <td> {{-- <-- DATA BARU --}}
                        @php
                            $currentApproverName = '-';
                            if (str_contains($request->status, 'Pending')) {
                                if ($request->status === 'Pending IT Item Validation') {
                                    $currentApproverName = $request->itUserPic->name ?? 'Tim IT Site';
                                } else {
                                    $pendingApproval = $request->approvals->where('status', 'Pending')->first();
                                    if ($pendingApproval) {
                                        $currentApproverName = $pendingApproval->approver->name ?? $pendingApproval->approver_role_name;
                                    }
                                }
                            }
                        @endphp
                        <p class="text-sm font-weight-bold mb-0">{{ $currentApproverName }}</p>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('asset-requests.show', $request->id) }}" class="btn btn-sm btn-outline-primary mb-0">
                            Lihat Detail
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-4">Tidak ada data pengajuan aset ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-3">
        {{ $requests->links() }}
    </div>
</div>