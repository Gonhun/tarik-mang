<div class="table-responsive">
    <table class="table align-items-center mb-0">
        <thead>
            <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Waktu</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">User</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Aksi</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Model</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">ID Record</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($auditLogs as $log)
                <tr>
                    <td><p class="text-sm mb-0" title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</p></td>
                    <td><p class="text-sm mb-0">{{ $log->user->name ?? 'Sistem' }}</p></td>
                    <td><span class="badge bg-gradient-secondary">{{ $log->event }}</span></td>
                    <td><p class="text-sm mb-0">{{ class_basename($log->auditable_type) }}</p></td>
                    <td><p class="text-sm mb-0">{{ $log->auditable_id }}</p></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4">Tidak ada data log audit ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-3">
        {{ $auditLogs->links() }}
    </div>
</div>