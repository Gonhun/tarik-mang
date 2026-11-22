<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header pb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Manajemen Cost Center</h5>
                    <button wire:click="create()" class="btn btn-primary btn-sm">
                        <i class="bx bx-bookmark-plus  me-1"></i> Tambah Cost Center
                    </button>
                </div>
                 @if (session()->has('message'))<div class="alert alert-success mt-3">{{ session('message') }}</div>@endif
                 @if (session()->has('error'))<div class="alert alert-danger mt-3">{{ session('error') }}</div>@endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="Cari No. Cost Center, Nama, Jobsite, atau Departemen...">
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No. Cost Center</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Jobsite</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Departemen</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($costCenters as $cc)
                                <tr>
                                    <td><p class="text-xs font-weight-bold mb-0 px-3">{{ $cc->cost_center_number }}</p></td>
                                    <td><p class="text-xs font-weight-bold mb-0">{{ $cc->jobsite->name ?? '-' }}</p></td>
                                    <td><p class="text-xs font-weight-bold mb-0">{{ $cc->department->name ?? '-' }}</p></td>
                                    <td class="align-middle text-end">
                                        <button wire:click="edit({{ $cc->id }})" class="btn btn-link text-secondary mb-0 p-1" title="Edit">
                                            <i class="bx bx-bxs-edit text-sm"></i>
                                        </button>
                                        <button wire:click="delete({{ $cc->id }})"
                                                onclick="confirm('Yakin hapus Cost Center ini: {{ $cc->cost_center_number }}?') || event.stopImmediatePropagation()"
                                                class="btn btn-link text-danger text-gradient p-1 mb-0" title="Hapus">
                                            <i class="bx bx-bxs-trash text-sm"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4">Tidak ada data Cost Center ditemukan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $costCenters->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $cost_center_id ? 'Edit Cost Center' : 'Tambah Cost Center Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="jobsite_id" class="form-label">Jobsite Terkait (Opsional)</label>
                                    <select wire:model.defer="jobsite_id" id="jobsite_id" class="form-select @error('jobsite_id') is-invalid @enderror">
                                        <option value="">-- Tidak Terkait Jobsite Spesifik --</option>
                                        @foreach($jobsites as $jobsite)
                                        <option value="{{ $jobsite->id }}">{{ $jobsite->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('jobsite_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                 <div class="col-md-4 mb-3">
                                    <label for="department_id" class="form-label">Departemen Terkait (Opsional)</label>
                                    <select wire:model.defer="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                        <option value="">-- Tidak Terkait Departemen Spesifik --</option>
                                        @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                     @error('department_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="cost_center_number" class="form-label">Nomor Cost Center <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('cost_center_number') is-invalid @enderror" id="cost_center_number" wire:model.defer="cost_center_number">
                                    @error('cost_center_number') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Keterangan / Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" wire:model.defer="description" rows="3"></textarea>
                                @error('description') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">{{ $cost_center_id ? 'Simpan Perubahan' : 'Simpan' }}</span>
                                <span wire:loading wire:target="store">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>