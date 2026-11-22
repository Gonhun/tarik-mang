<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="page-header">
      <div>
        <h4 class="mb-1">Entitas</h4>
      </div>
      <div class="d-flex flex-wrap gap-2"><button wire:click="create()" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i> Buat Entitas Baru
                            </button></div>
    </div>
    @if (session()->has('message'))
                            <div class="alert alert-success mt-3" role="alert">
                                {{ session('message') }}
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="alert alert-danger mt-3" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif
    <div class="card">
            <div class="card-body px-0 pt-0 pb-2">
                        <div class="px-4 pt-3"><div class="input-group input-group-merge" style="max-width: 380px"><span class="input-group-text"><i class="bx bx-search"></i></span><input wire:model.live.debounce.500ms="search" type="search" class="form-control" placeholder="Cari berdasarkan kode atau nama entitas..."></div></div>
                        <div class="table-responsive p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Kode</th>
                                        <th>Nama Entitas</th>
                                        <th>Tanggal Dibuat</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($entities as $entity)
                                        <tr>
                                            <td>
                                                <div class="d-flex px-3 py-1">
                                                    <span class="fw-medium text-heading">{{ $entity->id }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                {{ $entity->code }}
                                            </td>
                                            <td>
                                                {{ $entity->name }}
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="text-secondary text-xs font-weight-bold">{{ $entity->created_at->format('d M Y H:i') }}</span>
                                            </td>
                                            <td class="align-middle">
                                                <button wire:click="edit({{ $entity->id }})" class="btn btn-link text-secondary mb-0 p-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Entitas">
                                                    <i class="bx bx-edit text-sm"></i>
                                                </button>
                                                <button wire:click="delete({{ $entity->id }})"
                                                        wire:confirm="Apakah Anda yakin ingin menghapus entitas ini: {{ $entity->code }} - {{ $entity->name }}?"
                                                        class="btn btn-link text-danger text-gradient p-1 mb-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus Entitas">
                                                    <i class="bx bx-trash text-sm"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <p class="mb-0">Tidak ada data entitas ditemukan.</p>
                                                 @if(empty($search))
                                                    <p class="mb-0">Anda bisa memulai dengan membuat entitas baru.</p>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 pt-3">
                            {{ $entities->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Create dan Edit Entitas --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $entity_id ? 'Edit Entitas' : 'Buat Entitas Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="entity_code" class="form-label">Kode Entitas <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="entity_code" wire:model.defer="code" placeholder="Contoh: PTA, PTB, YSC">
                                @error('code') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="entity_name" class="form-label">Nama Entitas <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="entity_name" wire:model.defer="name" placeholder="Contoh: PT. ABC Indonesia, Yayasan XYZ">
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">{{ $entity_id ? 'Simpan Perubahan' : 'Simpan' }}</span>
                                <span wire:loading wire:target="store">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

