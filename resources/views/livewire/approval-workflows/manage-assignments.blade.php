<div>
    <div class="container-fluid py-4">
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
                    <a href="{{ route('admin.workflow.add') }}" class="btn btn-success btn-sm ms-2 d-inline-flex align-items-center">
                        <i class="bx bx-bell-plus me-1"></i>
                        <span>Create Step</span>
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
                                    <td class="fw-bold">{{ $assignment->transaction_type }}</td>
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
                                        <button wire:click="delete({{ $assignment->id }})" onclick="confirm('Yakin hapus aturan ini?') || event.stopImmediatePropagation()" class="btn btn-sm btn-outline-danger py-1 px-2 mb-0">Hapus</button>
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
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $assignment_id ? 'Edit Aturan Validasi' : 'Tambah Aturan Validasi Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted">Aturan ini menentukan alur persetujuan yang akan digunakan. Kosongkan Jabatan atau Jobsite untuk menerapkan aturan ke semua.</p>
                            <div class="mb-3">
                                <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                                <select wire:model.defer="transaction_type" class="form-select @error('transaction_type') is-invalid @enderror">
                                    <option value="">-- Pilih Jenis Transaksi --</option>
                                    @foreach($transactionTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('transaction_type') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Berlaku untuk Jabatan (Opsional)</label>
                                    <select wire:model.defer="position_id" class="form-select">
                                        <option value="">-- Semua Jabatan --</option>
                                        @foreach($allPositions as $position)
                                            <option value="{{ $position->id }}">{{ $position->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Berlaku untuk Jobsite (Opsional)</label>
                                    <select wire:model.defer="jobsite_id" class="form-select">
                                        <option value="">-- Semua Jobsite --</option>
                                        @foreach($allJobsites as $jobsite)
                                            <option value="{{ $jobsite->id }}">{{ $jobsite->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gunakan Alur Kerja (Workflow) <span class="text-danger">*</span></label>
                                <select wire:model.defer="approval_workflow_id" class="form-select @error('approval_workflow_id') is-invalid @enderror">
                                    <option value="">-- Pilih Alur Kerja --</option>
                                    @foreach($allWorkflows as $workflow)
                                        <option value="{{ $workflow->id }}">{{ $workflow->name }}</option>
                                    @endforeach
                                </select>
                                @error('approval_workflow_id') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Aturan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    @livewire('modal.workflows.workflow-visualizer-modal')
</div>