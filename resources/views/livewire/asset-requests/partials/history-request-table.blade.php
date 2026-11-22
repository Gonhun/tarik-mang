<div class="table-responsive">
    <table class="table align-items-center mb-0">
        <thead>
            <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No. Pengajuan</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Pemohon</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tgl Selesai</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status Final</th>
                <th class="text-secondary opacity-7"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($historyRequests as $request)
                <tr>
                    <td><p class="text-sm font-weight-bold mb-0 px-3">{{ $request->request_number }}</p></td>
                    <td><p class="text-sm mb-0">{{ $request->requester->name ?? 'N/A' }}</p></td>
                    <td><p class="text-sm mb-0">{{ $request->updated_at->isoFormat('D MMM YYYY') }}</p></td>
                    <td>
                        <span class="badge badge-sm bg-gradient-{{ $request->status == 'Completed' || $request->status == 'Approved' ? 'success' : 'danger' }}">
                            {{ $request->status }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('asset-requests.show', $request->id) }}" class="btn btn-sm btn-outline-secondary mb-0">
                            Lihat Detail
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4">Tidak ada data history pengajuan ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-3">
        {{ $historyRequests->links() }}
    </div>
</div>