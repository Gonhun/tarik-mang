<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0">Manajemen Entitas</h5>
                            <button wire:click="create()" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Buat Entitas Baru
                            </button>
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
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="px-4 pt-3">
                            <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="Cari berdasarkan kode atau nama entitas...">
                        </div>
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ID</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Kode</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Nama Entitas</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tanggal Dibuat</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($entities as $entity)
                                        <tr>
                                            <td>
                                                <div class="d-flex px-3 py-1">
                                                    <h6 class="mb-0 text-sm">{{ $entity->id }}</h6>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $entity->code }}</p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $entity->name }}</p>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="text-secondary text-xs font-weight-bold">{{ $entity->created_at->format('d M Y H:i') }}</span>
                                            </td>
                                            <td class="align-middle">
                                                <button wire:click="edit({{ $entity->id }})" class="btn btn-link text-secondary mb-0 p-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Entitas">
                                                    <i class="fas fa-edit text-sm"></i>
                                                </button>
                                                <button wire:click="delete({{ $entity->id }})"
                                                        onclick="confirm('Apakah Anda yakin ingin menghapus entitas ini: {{ $entity->code }} - {{ $entity->name }}?') || event.stopImmediatePropagation()"
                                                        class="btn btn-link text-danger text-gradient p-1 mb-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus Entitas">
                                                    <i class="far fa-trash-alt text-sm"></i>
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

@push('scripts')
<script>
    // Untuk inisialisasi tooltip Bootstrap jika Anda menggunakannya
    document.addEventListener('livewire:load', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
    // Re-initialize tooltips after Livewire updates the DOM
    Livewire.hook('message.processed', (message, component) => {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            var existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
            if (existingTooltip) {
                existingTooltip.dispose();
            }
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>
@endpush