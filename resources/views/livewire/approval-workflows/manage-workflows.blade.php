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
                                        onclick="return confirm('Anda yakin ingin menghapus template workflow ini?') || event.stopImmediatePropagation()"
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
                        <div class="card-header">
                            <h5 class="mb-0">Langkah Persetujuan untuk: {{ $selectedWorkflow->name }}</h5>
                        </div>
                        <div class="card-body">
                            @if($selectedWorkflow->steps->isEmpty())
                                <p class="text-muted">Belum ada langkah persetujuan yang ditambahkan untuk workflow ini.</p>
                            @else
                                <ul class="list-group">
                                    @foreach($selectedWorkflow->steps as $step)
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 shadow-sm rounded mb-2" style="background: linear-gradient(90deg, #f8fafc 80%, #e0e7ff 100%);">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-gradient bg-primary fs-6 me-3" style="min-width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                                   {{ $step->step_order }}
                                                </span>
                                                <div>
                                                    <div class="fw-bold text-primary">
                                                        {{ $step->department->code ?? '🌐 Global / Dept Terkait' }}
                                                    </div>
                                                    <div class="text-muted small">
                                                        <i class="bx bx-user-pin"></i>
                                                        {{ $step->user->name ?? 'User Global' }} - {{ $step->approverPositionLevel->name ?? 'Level Jabatan Dihapus' }}
                                                    </div>
                                                </div>
                                            </div>
                                            <button wire:click="removeStep({{ $step->id }})" class="btn btn-sm btn-outline-danger mb-0 py-1 px-2" title="Hapus Langkah Ini">
                                                <i class="bx bxs-trash-alt"></i>
                                            </button>
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
                                        <option value="">-- Pilih Jabatan --</option>
                                        @foreach($allPositions as $position)
                                            <option value="{{ $position->id }}">{{ $position->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('new_step_position_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-success">Tambah Langkah</button>
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