<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div
                        class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-package fs-4 text-primary me-2"></i>
                            <h5 class="mb-0 fw-bold">Master Catalog Software</h5>
                        </div>
                        <button class="btn btn-primary btn-sm px-3 shadow-sm" wire:click="create">
                            <i class="bx bx-plus me-1"></i> Daftarkan Software Baru
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text bg-light border-0"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control bg-light border-0 shadow-none"
                                        placeholder="Cari nama atau versi software..." wire:model.live="search">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select bg-light border-0 shadow-none"
                                    wire:model.live="filter_manufacturer">
                                    <option value="">Semua Manufaktur</option>
                                    @foreach($manufacture_options as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-top">
                                <thead class="bg-light">
                                    <tr class="text-uppercase text-muted fw-bold"
                                        style="font-size: 0.7rem; letter-spacing: 1px;">
                                        <th class="ps-3">Software Details</th>
                                        <th>Category</th>
                                        <th>Manufacturer</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($this->datas as $software)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center">
                                                    <div
                                                        class="avatar-soft bg-primary-subtle text-primary rounded p-2 me-3">
                                                        <i class="bx bx-code-alt fs-4"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark">{{ $software->name }}</div>
                                                        <small class="text-muted">Version: {{ $software->version }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-secondary-subtle text-secondary px-2">{{ $software->category->name }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bx bx-buildings me-1 text-muted"></i>
                                                    <span class="small">{{ $software->manufacturer->name }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input" type="checkbox" checked
                                                        wire:click="toggleStatus(1)">
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-icon btn-light text-warning me-1"
                                                    wire:click="edit({{ $software->id }})">
                                                    <i class="bx bx-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-icon btn-light text-danger"
                                                    wire:click="confirmDelete({{ $software->id }})">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="bx bx-package fs-3 mb-2"></i>
                                                <div class="fw-bold">Tidak ada software yang ditemukan.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade @if($isModalOpen) show d-block @endif" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="bx bx-edit-alt me-2"></i> {{ $software_id ? 'Edit Software' : 'Daftarkan Software' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>
                <div class="modal-body p-4">
                    <form wire:submit.prevent="store">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Manufacturer <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i
                                            class="bx bx-buildings"></i></span>
                                    <select id="manufacture_select" class="form-select bg-light border-0 @error('manufacture_id') is-invalid @enderror"
                                        wire:model="manufacture_id">
                                        <option value="">Pilih Manufaktur...</option>
                                        @foreach ($manufacture_options as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('manufacture_id') <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Software Category <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bx bx-tag"></i></span>
                                    <select id="category_select" class="form-select bg-light border-0 @error('category_id') is-invalid @enderror"
                                        wire:model="category_id">
                                        <option value="">Pilih Kategori...</option>
                                        @foreach ($category_options as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('category_id') <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Software Name <span
                                        class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control bg-light border-0 @error('name') is-invalid @enderror"
                                    wire:model="name" placeholder="Contoh: Photoshop">
                                @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Version</label>
                                <input type="text" class="form-control bg-light border-0" wire:model="version"
                                    placeholder="v.2024">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Description</label>
                                <textarea class="form-control bg-light border-0" rows="3" wire:model="description"
                                    placeholder="Catatan opsional mengenai software ini..."></textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light px-4" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bx bx-save me-1"></i> Simpan Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            document.addEventListener('livewire:init', () => {
                const modalId = '#softwareModal'; // Sesuaikan dengan ID modal Anda

                const initSoftwareSelects = () => {
                    // Inisialisasi Manufacturer
                    $('#manufacturer_select').select2({
                        dropdownParent: $(modalId),
                        placeholder: 'Pilih Manufaktur...',
                        allowClear: true,
                        width: '100%'
                    }).on('change', function (e) {
                        @this.set('manufacture_id', $(this).val());
                    });

                    // Inisialisasi Category
                    $('#category_select').select2({
                        dropdownParent: $(modalId),
                        placeholder: 'Pilih Kategori...',
                        allowClear: true,
                        width: '100%'
                    }).on('change', function (e) {
                        @this.set('category_id', $(this).val());
                    });
                };

                // Trigger saat modal dibuka
                Livewire.on('open-software-modal', (event) => {
                    setTimeout(() => {
                        initSoftwareSelects();

                        // Jika sedang EDIT, set value select2 agar sinkron dengan data di PHP
                        if (event[0].manufacturer_id) {
                            $('#manufacturer_select').val(event[0].manufacturer_id).trigger('change.select2');
                        }
                        if (event[0].category_id) {
                            $('#category_select').val(event[0].category_id).trigger('change.select2');
                        }
                    }, 200);
                });

                // Bersihkan Select2 saat modal ditutup agar tidak memory leak
                $(modalId).on('hidden.bs.modal', function () {
                    $('#manufacturer_select').select2('destroy');
                    $('#category_select').select2('destroy');
                });
            });
        </script>
    @endpush
</div>