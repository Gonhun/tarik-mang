<div>
    <div class="container-fluid py-4">
        @if (session()->has('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif
        @if (session()->has('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            {{-- Kolom Kiri: Daftar Workflow & Form Tambah/Edit Workflow --}}
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Template Alur Kerja</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($workflows as $workflow)
                            <div class="list-group-item d-flex justify-content-between align-items-center {{ $selectedWorkflow && $selectedWorkflow->id == $workflow->id ? 'active' : '' }}">
                                <a href="#" wire:click.prevent="selectWorkflow({{ $workflow->id }})" 
                                   class="text-decoration-none flex-grow-1 {{ $selectedWorkflow && $selectedWorkflow->id == $workflow->id ? 'text-black' : 'text-dark' }}">
                                    {{ $workflow->name }}
                                </a>
                                <button wire:click.prevent="editWorkflow({{ $workflow->id }})" 
                                        class="btn btn-sm p-1 ms-2 {{ $selectedWorkflow && $selectedWorkflow->id == $workflow->id ? 'btn-outline-light' : 'btn-outline-warning' }}" 
                                        title="Edit Workflow">
                                    <i class="bx bx-bxs-edit-alt"></i>
                                </button>
                                <button wire:click.prevent="deleteWorkflow({{ $workflow->id }})" 
                                        wire:confirm="Hapus template workflow {{ $workflow->name }}?"
                                        class="btn btn-sm p-1 ms-2 {{ $selectedWorkflow && $selectedWorkflow->id == $workflow->id ? 'btn-outline-light' : 'btn-outline-danger' }}" 
                                        title="Hapus Workflow">
                                    <i class="bx bxs-trash-alt"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-body border-top">
                        <h6 class="mb-3">{{ $workflow_id ? 'Edit Workflow' : 'Buat Workflow Baru' }}</h6>
                        <form wire:submit.prevent="saveWorkflow">
                            <div class="mb-3">
                                <label for="workflow_name" class="form-label">Nama Workflow</label>
                                <input type="text" wire:model.defer="workflow_name" class="form-control" id="workflow_name">
                                @error('workflow_name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="workflow_description" class="form-label">Deskripsi</label>
                                <textarea wire:model.defer="workflow_description" class="form-control" id="workflow_description" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">{{ $workflow_id ? 'Edit' : 'Simpan' }} Workflow</button>
                            <button type="button" wire:click="$set('workflow_id', null); $set('workflow_name', ''); $set('workflow_description', '')" class="btn btn-secondary btn-sm">Batal Edit</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Detail Langkah-langkah Workflow yang Dipilih --}}
            <div class="col-md-8">
                @if ($selectedWorkflow)
                    <div class="card">
                        @php
                            $previewSite = $jobsites->firstWhere('id', (int) $previewJobsiteId);
                            $stepCount = $selectedWorkflow->steps->count();
                        @endphp
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div>
                                <h5 class="mb-0">Tahapan: {{ $selectedWorkflow->name }}</h5>
                                <small class="text-muted">
                                    @if ($usedBy->isEmpty())
                                        Belum dipakai aturan validasi mana pun.
                                    @else
                                        Dipakai untuk:
                                        {{ $usedBy->map(fn ($u) => ($typeLabels[$u->transaction_type] ?? $u->transaction_type) . ' (' . ($u->position->name ?? 'semua jabatan') . ', ' . ($u->jobsite->code ?? 'semua site') . ')')->implode('; ') }}
                                    @endif
                                </small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label for="previewSite" class="small text-muted mb-0 text-nowrap">Pratinjau site</label>
                                <select id="previewSite" wire:model.live="previewJobsiteId" class="form-select form-select-sm" style="min-width: 140px">
                                    <option value="">(tanpa site)</option>
                                    @foreach ($jobsites as $j)
                                        <option value="{{ $j->id }}">{{ $j->code }}</option>
                                    @endforeach
                                </select>
                                <span wire:loading wire:target="previewJobsiteId,moveStep,removeStep,addStep" class="spinner-border spinner-border-sm text-primary" role="status"></span>
                            </div>
                        </div>
                        <div class="card-body">
                            @if ($selectedWorkflow->steps->isEmpty())
                                <div class="alert alert-warning small mb-0"><i class="bx bx-error"></i> Workflow ini belum punya tahapan. Pengajuan yang memakai workflow ini akan ditolak.</div>
                            @else
                                <ul class="list-group">
                                    @foreach ($selectedWorkflow->steps as $step)
                                        @php
                                            $approver = $preview[$step->id] ?? null;
                                            $role = $step->approverPositionLevel->name ?? null;
                                            $skipped = $role === 'Kepala Proyek' && in_array($previewSite->code ?? null, ['SMD', 'JKT'], true);
                                        @endphp
                                        <li class="list-group-item d-flex flex-wrap align-items-center gap-3 py-3" wire:key="step-{{ $step->id }}">
                                            <span class="badge rounded-pill bg-primary fs-6 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">{{ $step->step_order }}</span>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-semibold">
                                                    {{ $step->user ? $step->user->name : ($role ?? 'Level jabatan dihapus') }}
                                                </div>
                                                <small class="text-muted d-block">
                                                    {{ $step->user ? 'User tertentu' : 'Jabatan ' . ($role ?? '-') }}
                                                    · {{ $step->department->name ?? 'semua department' }}
                                                </small>
                                                <small class="d-block">
                                                    @if ($skipped)
                                                        <span class="text-muted"><i class="bx bx-skip-next"></i> Dilewati untuk site {{ $previewSite->code }}</span>
                                                    @elseif ($approver)
                                                        <span class="text-success"><i class="bx bx-check-circle"></i> Approver: {{ $approver->name }}</span>
                                                    @else
                                                        <span class="text-danger"><i class="bx bx-error-circle"></i> Approver tidak ditemukan{{ $previewSite ? ' di ' . $previewSite->code : '' }} maupun JKT — pengajuan akan ditolak</span>
                                                    @endif
                                                </small>
                                            </div>
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Atur tahap {{ $step->step_order }}">
                                                <button type="button" wire:click="moveStep({{ $step->id }}, 'up')" class="btn btn-outline-secondary" @disabled($loop->first) aria-label="Naikkan"><i class="bx bx-chevron-up"></i></button>
                                                <button type="button" wire:click="moveStep({{ $step->id }}, 'down')" class="btn btn-outline-secondary" @disabled($loop->last) aria-label="Turunkan"><i class="bx bx-chevron-down"></i></button>
                                                <button type="button" wire:click="removeStep({{ $step->id }})" wire:confirm="Hapus tahap {{ $step->step_order }} dari workflow {{ $selectedWorkflow->name }}?" class="btn btn-outline-danger" aria-label="Hapus"><i class="bx bx-trash"></i></button>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="card-footer">
                            <h6 class="mb-3">Tambah Langkah Baru</h6>
                            <form wire:submit.prevent="addStep" class="row g-2 align-items-end">
                                <div class="col">
                                    <label for="new_step_Department_id" class="form-label">Pilih Department Approver</label>
                                    <select wire:model.live="new_step_Department_id" class="form-select" id="new_step_Department_id">
                                        <option value="">-- Global / Dept Terakit --</option>
                                        @foreach($allDepartments as $allDepartment)
                                            <option value="{{ $allDepartment->id }}">{{ $allDepartment->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('new_step_Department_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col">
                                    <label for="new_step_user_id" class="form-label">User Approver</label>
                                    <select wire:model.live="new_step_user_id" class="form-select" id="new_step_user_id">
                                        <option value="">-- User Dept Terakit --</option>
                                        @foreach($userDepartments as $userDepartment)
                                            <option value="{{ $userDepartment->id }}">{{ $userDepartment->nik }} - {{ $userDepartment->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('new_step_user_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col">
                                    <label for="new_step_position_id" class="form-label">Pilih Jabatan Approver</label>
                                    <select wire:model.defer="new_step_position_id" class="form-select" id="new_step_position_id">
                                        <option value="">-- Pilih Jabatan (wajib jika tanpa user) --</option>
                                        @foreach($allPositions as $position)
                                            <option value="{{ $position->id }}">{{ $position->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('new_step_position_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-success" wire:loading.attr="disabled" wire:target="addStep">
                                        <span wire:loading.remove wire:target="addStep">Tambah tahap</span>
                                        <span wire:loading wire:target="addStep"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="card">
                        <div class="card-body text-center">
                            <p class="text-muted">Pilih sebuah template alur kerja dari daftar di sebelah kiri untuk melihat atau mengedit langkah-langkah persetujuannya.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>