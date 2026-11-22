<div class="container-fluid">
    <h4 class="mb-4">Master Part Number (Katalog Part)</h4>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
                {{-- Input Pencarian --}}
                <input type="text" 
                    wire:model.live.debounce.300ms="search" 
                    class="form-control form-control-sm" 
                    placeholder="Cari Part Number / Nama..." 
                    style="width: 250px;">
                
                {{-- Filter Per Halaman --}}
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto;">
                    <option value="10">10 per halaman</option>
                    <option value="25">25 per halaman</option>
                    <option value="50">50 per halaman</option>
                </select>
            </div>
            
            {{-- Tombol Tambah --}}
            <button class="btn btn-primary btn-sm" wire:click="openCreateModal">
                <i class="fas fa-plus"></i> Tambah Part Baru
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 16%">Part Number</th>
                            <th style="width: 16%">Material Code</th>
                            <th style="width: 22%">Nama Part</th>
                            <th style="width: 10%">Satuan</th>
                            <th style="width: 14%">Harga Default</th>
                            <th style="width: 12%" class="text-center">Total Stok</th>
                            <th style="width: 10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($parts as $part)
                            <tr>
                                <td>{{ $part->part_number }}</td>
                                <td>{{ $part->material_number }}</td>
                                <td>{{ $part->name }}</td>
                                <td>{{ $part->unit ?? '-' }}</td>
                                <td>Rp {{ number_format($part->default_price ?? 0, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    {{-- Menggunakan Accessor dari Model --}}
                                    <span class="badge bg-secondary">{{ number_format($part->total_stock) }}</span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info me-1" wire:click="edit({{ $part->id }})">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" wire:click="delete({{ $part->id }})" wire:confirm="Apakah Anda yakin ingin menghapus part ini?">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ada data Master Part yang ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer">
            {{ $parts->links() }}
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL TAMBAH/EDIT PART --}}
    {{-- ========================================================== --}}

    @if($showModal)
    <div class="modal fade {{ $showModal ? 'show d-block' : '' }}" tabindex="-1" role="dialog" aria-labelledby="MasterPartModal" aria-hidden="true" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form wire:submit="store">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $partId ? 'Edit Part: ' . $part_number : 'Tambah Part Baru' }}</h5>
                        <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="part_number" class="form-label required">Part Number</label>
                                <input type="text" id="part_number" class="form-control" wire:model="part_number">
                                @error('part_number') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="material_number" class="form-label required">Material Number</label>
                                <input type="text" id="material_number" class="form-control" wire:model="material_number">
                                @error('material_number') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="name" class="form-label required">Nama Part</label>
                                <input type="text" id="name" class="form-control" wire:model="name">
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">Satuan (Unit)</label>
                                <input type="text" id="unit" class="form-control" wire:model="unit">
                                @error('unit') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="default_price" class="form-label">Harga Default (Rp)</label>
                                <input type="number" step="0.01" id="default_price" class="form-control" wire:model="default_price">
                                @error('default_price') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea id="description" class="form-control" rows="3" wire:model="description"></textarea>
                            @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    
    {{-- Script untuk menutup modal setelah simpan (jika diperlukan) --}}
    @script
        <script>
            // Event listener untuk menutup modal jika menggunakan Alpine/Bootstrap JS
            Livewire.on('close-modal', () => {
                const modalElement = document.querySelector('.modal.show');
                if (modalElement) {
                    modalElement.classList.remove('show', 'd-block');
                    modalElement.style.backgroundColor = '';
                }
            });
            // Opsional: Untuk merefresh komponen setelah delete
            Livewire.on('refreshComponent', () => {
                Livewire.dispatch('refresh');
            });
        </script>
    @endscript

</div>