<div>
    <div class="container-fluid py-4">
        {{-- Kesiapan workflow & uji coba --}}
        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header pb-2">
                        <h5 class="card-title mb-0">Kesiapan per jenis pengajuan</h5>
                        <small class="text-muted">Jenis tanpa aturan akan ditolak saat diajukan.</small>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($readiness as $type => $info)
                            @php
                                $ok = $info['rules'] > 0;
                                $fb = \App\Support\Workflow::fallbacks($type)[0] ?? null;
                                $fallbackLost = $fb && !$ok && ($readiness[$fb]['rules'] ?? 0) > 0;
                            @endphp
                            <li class="list-group-item d-flex align-items-center justify-content-between gap-2">
                                <div class="min-w-0">
                                    <span class="fw-medium text-capitalize">{{ $typeLabels[$type] ?? $type }}</span>
                                    <small class="d-block text-muted">{{ $type }} · {{ $info['rules'] }} aturan{{ $info['hasDefault'] ? ' · ada aturan default' : '' }}</small>
                                </div>
                                @if ($ok)
                                    <span class="badge bg-label-{{ $info['hasDefault'] ? 'success' : 'warning' }}" title="{{ $info['hasDefault'] ? '' : 'Hanya berlaku untuk jabatan/site tertentu' }}">{{ $info['hasDefault'] ? 'Siap' : 'Sebagian' }}</span>
                                @elseif ($fallbackLost)
                                    <span class="badge bg-label-info" title="Memakai workflow {{ $fb }}">Pakai {{ $fb }}</span>
                                @else
                                    <span class="badge bg-label-danger">Belum diatur</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header pb-2">
                        <h5 class="card-title mb-0">Uji coba workflow</h5>
                        <small class="text-muted">Lihat approver yang akan dipakai sebelum user mengajukan.</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-4">
                                <select wire:model.live="testType" class="form-select form-select-sm" aria-label="Jenis">
                                    @foreach ($transactionTypes as $type)
                                        <option value="{{ $type }}">{{ ucfirst($typeLabels[$type] ?? $type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-4">
                                <select wire:model.live="testJobsiteId" class="form-select form-select-sm" aria-label="Site">
                                    <option value="">Site: (tanpa site)</option>
                                    @foreach ($allJobsites as $j)
                                        <option value="{{ $j->id }}">{{ $j->code ?? $j->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-4">
                                <select wire:model.live="testPositionId" class="form-select form-select-sm" aria-label="Jabatan pemohon">
                                    <option value="">Jabatan: semua</option>
                                    @foreach ($allPositions as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div wire:loading.flex wire:target="testType,testJobsiteId,testPositionId" class="align-items-center gap-2 small text-muted mb-2">
                            <span class="spinner-border spinner-border-sm" role="status"></span> Menghitung...
                        </div>
                        @if ($test['ok'])
                            <p class="small mb-2"><i class="bx bx-check-circle text-success"></i> Workflow <strong>{{ $test['workflow']->name }}</strong> dengan {{ count($test['steps']) }} tahap:</p>
                            <ol class="list-group list-group-numbered small">
                                @foreach ($test['steps'] as $step)
                                    <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                                        <span class="ms-1 me-auto">
                                            <span class="fw-medium">{{ $step['user']->name }}</span>
                                            <span class="text-muted d-block">{{ $step['role'] }} · tahap {{ $step['step_order'] }}</span>
                                        </span>
                                        <span class="badge bg-label-{{ $step['user']->whatsapp ? 'success' : 'warning' }}" title="Notifikasi WhatsApp">{{ $step['user']->whatsapp ? 'WA ada' : 'WA kosong' }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <div class="alert alert-danger small mb-0 d-flex gap-2" role="alert">
                                <i class="bx bx-error-alt fs-5 flex-shrink-0"></i><span>{{ $test['message'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Transaksi yang sudah berjalan tapi tertahan tanpa approver --}}
        @if ($canRepair && ($stuckTotal > 0 || $repairReport))
            <div class="card mb-4 border border-danger">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2 pb-2">
                    <div>
                        <h5 class="card-title mb-0">Transaksi tertahan tanpa approver</h5>
                        <small class="text-muted">
                            {{ number_format($stuckTotal, 0, ',', '.') }} transaksi mutasi/disposal masih berjalan tanpa tahapan atau dengan tahap tanpa approver.
                            {{ number_format($stuckAuto, 0, ',', '.') }} bisa diperbaiki otomatis dari workflow saat ini; sisanya (sudah ada keputusan) perbaiki per tahap di halaman detail.
                        </small>
                    </div>
                    @if ($stuckAuto > 0)
                        <button type="button" class="btn btn-sm btn-danger" wire:click="repairBatch"
                            wire:confirm="Terapkan workflow saat ini ke maks. 25 transaksi tertahan? Approver pertama tiap transaksi akan diberi notifikasi."
                            wire:loading.attr="disabled" wire:target="repairBatch,repairOne">
                            <span wire:loading.remove wire:target="repairBatch"><i class="bx bx-refresh me-1"></i>Perbaiki 25 berikutnya</span>
                            <span wire:loading wire:target="repairBatch"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Memproses...</span>
                        </button>
                    @endif
                </div>

                @if ($repairReport)
                    @php $okCount = collect($repairReport)->where('ok', true)->count(); @endphp
                    <div class="card-body border-bottom">
                        <p class="small mb-2"><strong>Hasil:</strong> {{ $okCount }} berhasil, {{ count($repairReport) - $okCount }} gagal.</p>
                        <ul class="list-unstyled small mb-0" style="max-height: 180px; overflow-y: auto;">
                            @foreach ($repairReport as $row)
                                <li class="mb-1"><i class="bx {{ $row['ok'] ? 'bx-check text-success' : 'bx-x text-danger' }}"></i> <strong>{{ $row['number'] }}</strong> — {{ $row['message'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($stuckList->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>Transaksi</th><th>Jenis</th><th>Aset</th><th>Masalah</th><th class="text-end"></th></tr></thead>
                            <tbody>
                                @foreach ($stuckList as $trx)
                                    @php $lost = $trx->transaction_type === 'DISPOSED' && str_starts_with((string) $trx->notes, '[Kehilangan]'); @endphp
                                    <tr wire:key="stuck-{{ $trx->id }}">
                                        <td>
                                            <a href="{{ route('asset.mutasi-status', $trx->id) }}" class="fw-medium">{{ $trx->transaction_number }}</a>
                                            <small class="d-block text-muted">{{ $trx->fromJobsite->code ?? '-' }} · {{ $trx->creator->name ?? '-' }} · status {{ $trx->status }}</small>
                                        </td>
                                        <td>{{ $lost ? 'Kehilangan' : ($trx->transaction_type === 'MUTATION' ? 'Mutasi' : 'Disposal') }}</td>
                                        <td><small>{{ $trx->items->pluck('asset.asset_number')->filter()->take(2)->implode(', ') ?: '-' }}</small></td>
                                        <td><small class="text-danger">{{ $trx->approvals_count === 0 ? 'Tanpa tahapan' : 'Ada tahap tanpa approver' }}</small></td>
                                        <td class="text-end text-nowrap">
                                            @if ($trx->decided_count === 0)
                                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="repairOne({{ $trx->id }})" wire:loading.attr="disabled" wire:target="repairOne({{ $trx->id }})">
                                                    <span wire:loading.remove wire:target="repairOne({{ $trx->id }})">Terapkan workflow</span>
                                                    <span wire:loading wire:target="repairOne({{ $trx->id }})"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                                </button>
                                            @else
                                                <a href="{{ route('asset.mutasi-status', $trx->id) }}" class="btn btn-sm btn-outline-secondary">Ganti per tahap</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($stuckTotal > $stuckList->count())
                        <div class="card-body py-2 small text-muted">Menampilkan 10 terbaru dari {{ number_format($stuckTotal, 0, ',', '.') }}.</div>
                    @endif
                @endif
            </div>
        @endif

        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Master Validasi (Aturan Alur Kerja)</h5>
                    <button wire:click="create()" class="btn btn-primary btn-sm">
                        <i class="bx bx-bookmark-alt-plus me-1"></i> Tambah Aturan Baru
                    </button>
                    
                </div>
               
                <p class="text-sm">Atur alur persetujuan mana yang akan digunakan berdasarkan jenis transaksi, jabatan, dan jobsite.</p>
                @if (session()->has('message'))<div class="alert alert-success mt-3">{{ session('message') }}</div>@endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <a href="{{ route('admin.workflow.add') }}" class="btn btn-success btn-sm d-inline-flex align-items-center">
                        <i class="bx bx-bell-plus me-1"></i>
                        <span>Create Step</span>
                    </a>
                    <a href="{{ route('admin.workflow.types') }}" class="btn btn-outline-primary btn-sm ms-2 d-inline-flex align-items-center">
                        <i class="bx bx-list-ul me-1"></i>
                        <span>Master jenis transaksi</span>
                    </a>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="form-control mb-3" placeholder="Cari berdasarkan Jenis Transaksi...">
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th>Jenis Transaksi</th>
                                <th>Jabatan</th>
                                <th>Jobsite</th>
                                <th>Alur Kerja yang Digunakan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td>
                                        <span class="fw-bold">{{ ucfirst($typeLabels[$assignment->transaction_type] ?? $assignment->transaction_type) }}</span>
                                        <small class="d-block text-muted">{{ $assignment->transaction_type }} · prioritas {{ $assignment->priority }}</small>
                                    </td>
                                    <td>{{ $assignment->position->name ?? 'Semua Jabatan' }}</td>
                                    <td>{{ $assignment->jobsite->name ?? 'Semua Jobsite' }}</td>
                                    <td>
                                        @if($assignment->approvalWorkflow->name)
                                            <button wire:click="$dispatch('showWorkflowVisualizer', { workflowId: {{$assignment->approvalWorkflow->id }} })" 
                                                    class="btn btn-sm btn-outline-success mb-0">
                                                Lihat Alur
                                            </button>
                                            @else
                                            <span class="badge bg-info">Tidak Ada</span>
                                        @endif
                                        <span class="badge bg-info">{{ $assignment->approvalWorkflow->name ?? 'N/A' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <button wire:click="edit({{ $assignment->id }})" class="btn btn-sm btn-outline-secondary py-1 px-2 mb-0">Edit</button>
                                        <button wire:click="delete({{ $assignment->id }})" wire:confirm="Hapus aturan ini? Pengajuan {{ $typeLabels[$assignment->transaction_type] ?? $assignment->transaction_type }} yang cocok bisa ditolak bila tidak ada aturan lain." class="btn btn-sm btn-outline-danger py-1 px-2 mb-0">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4">Belum ada aturan validasi yang dibuat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $assignments->links() }}</div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Form Tambah/Edit Aturan --}}
    @if ($isModalOpen)
        @php
            $types = \App\Support\Workflow::types();
            $selected = $types[$transaction_type] ?? null;
        @endphp
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="ruleTitle" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ruleTitle">{{ $assignment_id ? 'Edit aturan validasi' : 'Tambah aturan validasi baru' }}</h5>
                        <button type="button" class="btn-close" wire:click="closeModal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">Aturan menentukan workflow approval yang dipakai. Kosongkan jabatan/site untuk berlaku ke semua. Bila beberapa aturan cocok, yang paling spesifik dipakai (jabatan + site → jabatan → site → default), lalu prioritas terbesar.</p>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="rule_type" class="form-label mb-1">Jenis transaksi <span class="text-danger">*</span></label>
                                <a href="{{ route('admin.workflow.types') }}" class="small" target="_blank">Lihat master jenis transaksi <i class="bx bx-link-external"></i></a>
                            </div>
                            <select id="rule_type" wire:model.live="transaction_type" class="form-select @error('transaction_type') is-invalid @enderror">
                                <option value="">-- Pilih jenis transaksi --</option>
                                @foreach ($types as $code => $t)
                                    @if (($t['aktif'] ?? true) || $code === $transaction_type)
                                        <option value="{{ $code }}">{{ $t['label'] }} ({{ $code }}){{ ($t['aktif'] ?? true) ? '' : ' — nonaktif' }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('transaction_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @if ($selected)
                                <div class="border rounded p-2 mt-2 small bg-lighter">
                                    <span class="d-block">{{ $selected['deskripsi'] }}</span>
                                    @if (!empty($selected['dipakai_di']))
                                        <span class="d-block text-muted mt-1"><i class="bx bx-menu"></i> Dipakai di: {{ implode(', ', $selected['dipakai_di']) }}</span>
                                    @endif
                                    @if (!empty($selected['fallback']))
                                        <span class="d-block text-muted"><i class="bx bx-redo"></i> Bila belum ada aturan, memakai workflow {{ $types[$selected['fallback']]['label'] ?? $selected['fallback'] }}.</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="rule_position" class="form-label">Jabatan pemohon <span class="text-muted fw-normal">(opsional)</span></label>
                                <select id="rule_position" wire:model="position_id" class="form-select">
                                    <option value="">-- Semua jabatan --</option>
                                    @foreach ($allPositions as $position)
                                        <option value="{{ $position->id }}">{{ $position->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="rule_site" class="form-label">Site pemohon <span class="text-muted fw-normal">(opsional)</span></label>
                                <select id="rule_site" wire:model="jobsite_id" class="form-select">
                                    <option value="">-- Semua site --</option>
                                    @foreach ($allJobsites as $jobsite)
                                        <option value="{{ $jobsite->id }}">{{ $jobsite->code ?? '' }} {{ $jobsite->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-8">
                                <label for="rule_workflow" class="form-label">Workflow <span class="text-danger">*</span></label>
                                <select id="rule_workflow" wire:model="approval_workflow_id" class="form-select @error('approval_workflow_id') is-invalid @enderror">
                                    <option value="">-- Pilih workflow --</option>
                                    @foreach ($allWorkflows as $workflow)
                                        <option value="{{ $workflow->id }}">{{ $workflow->name }}</option>
                                    @endforeach
                                </select>
                                @error('approval_workflow_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Belum ada? Buat di <a href="{{ route('admin.workflow.add') }}" target="_blank">Create Step</a>.</small>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="rule_priority" class="form-label">Prioritas</label>
                                <input id="rule_priority" type="number" min="0" max="100" wire:model="priority" class="form-control @error('priority') is-invalid @enderror">
                                @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Besar = didahulukan.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeModal">Batal</button>
                        <button type="button" wire:click="store" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store">
                            <span wire:loading.remove wire:target="store">Simpan aturan</span>
                            <span wire:loading wire:target="store"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @livewire('modal.workflows.workflow-visualizer-modal')
</div>