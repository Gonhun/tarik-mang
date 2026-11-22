<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="page-header">
      <div>
        <h4 class="mb-1">Master COA</h4>
        <div class="page-subtitle">Chart of account, pivot anggaran, dan forecast aset per jobsite.</div>
      </div>
    </div>
    <nav>
                    <div class="nav nav-tabs" id="coaTab" role="tablist">
                        <button class="nav-link active" id="pivot-tab" data-bs-toggle="tab"
                            data-bs-target="#pivot-tab-pane" type="button" role="tab" aria-controls="pivot-tab-pane"
                            aria-selected="false">Pivot</button>
                        <button class="nav-link" id="master-tab" data-bs-toggle="tab" data-bs-target="#master-tab-pane"
                            type="button" role="tab" aria-controls="master-tab-pane"
                            aria-selected="true">Master</button>
                    </div>
                </nav>
                <div class="tab-content" id="coaTabContent">
                    <div class="tab-pane fade show active" id="pivot-tab-pane" role="tabpanel"
                        aria-labelledby="pivot-tab" tabindex="0">
                        <livewire:master.forecast.generate-pivot />
                    </div>
                    <div class="tab-pane fade" id="master-tab-pane" role="tabpanel" aria-labelledby="master-tab"
                        tabindex="0">
                        <div class="card">
                            <div class="card-header d-flex Justify-content-between pb-0">
                                <h5 class="mb-0">Chart of Account</h5>
                                {{-- Tombol Tambah Coa --}}
                                <div class="ms-auto align-items-end d-flex">
                                    <button class="btn btn-primary btn-sm mb-2" id="btnAdd" wire:click="openModal()">
                                        <i class="bx bx-bxs-file-plus me-1"></i> Tambah Coa Baru
                                    </button>
                                </div>
                            </div>
                            <div class="card-body px-0 pt-0 pb-2">
                                <div class="table-responsive p-1">
                                    <table class="table table-bordered align-items-center mb-0">
                                        <thead>
                                            <tr>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Action</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Dept</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Cost Element</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($this->datas as $data)
                                                <tr>
                                                    <td class="w-auto">
                                                        <button class="btn btn-sm btn-outline-success shadow" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false" title="Aksi"
                                                            wire:click.prevent="edit({{ $data->id }})">
                                                            <i class="bx bx-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger shadow" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                                                            <i class="bx bx-delete"></i>
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <p class="text-xs font-weight-bold mb-0">
                                                            {{ $data->department->code ?? 'N/A' }}</p>
                                                    </td>
                                                    <td>
                                                        <p class="text-xs font-weight-bold mb-0">
                                                            {{ $data->cost_element_number ?? 'N/A' }}</p>
                                                    </td>
                                                    <td>
                                                        <p class="text-xs font-weight-bold mb-0">
                                                            {{ $data->description ?? 'N/A' }}</p>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @if($this->isModalOpen)
            <!-- Modal Tambah Coa Baru -->
            <div class="modal fade show d-block" id="modalTambahCoa" style="background-color: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTambahCoaLabel">Tambah Coa Baru</h5>
                            <button type="button" wire:click.prevent="closeModal" class="btn-close"
                                data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form wire:submit="store">
                                <div class="mb-3">
                                    <label for="department" class="form-label">Dept</label>
                                    <select id="department" class="form-select" wire:model="department_id" @if ($this->cost_element_number) disabled @endif>
                                        <option value="">Pilih</option>
                                        @foreach ($departements as $departement)
                                            <option value="{{ $departement->id }}">{{ $departement->name }}
                                                ({{ $departement->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="costElement" class="form-label">Cost Element</label>
                                    <input type="text" class="form-control" id="costElement" name="costElement"
                                        wire:model="cost_element_number" @if ($this->cost_element_number) disabled @endif>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="description" name="description"
                                        wire:model="description">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                                    <button type="submit" class="btn btn-primary">
                                        <span wire:loading.remove
                                            wire:target="store">{{ $this->cost_element_number ? 'Simpan Perubahan' : 'Simpan Data' }}</span>
                                        <span wire:loading wire:target="store">Menyimpan...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        Livewire.on('open-modal', event => {
            console.log(event[0]);
            if (event[0] === true) {
                setTimeout(function () {
                    $('#department').select2({
                        dropdownParent: $("#modalTambahCoa"),
                        allowClear: true,
                    });
                }, 100); // Delay to ensure DOM is updated
            }
        });

        document.addEventListener("livewire:load", () => {
            Livewire.hook('message.processed', (message, component) => {
                if ($('#department').length) {
                    $('#department').select2({
                        dropdownParent: $("#modalTambahCoa"),
                        allowClear: true,
                    });
                }
            });
        });

        Livewire.on('swal:success', event => {
            // If you dispatch like: $this->dispatch('swal:success', ['title' => '...', 'text' => '...'])
            // then 'event' will be the array you passed.
            // If you dispatch like: $this->dispatch('swal:success', 'Title', 'Text')
            // then 'event' will be an array of arguments.

            // Try to support both ways:
            let detail = event;
            if (Array.isArray(event) && event.length === 1 && typeof event[0] === 'object') {
                detail = event[0];
            }
            // Now detail should be an object with 'title' and 'text'
            Swal.fire({
                icon: 'success',
                title: detail.title || (event[0] ?? ''),
                text: detail.text || (event[1] ?? ''),
                timer: 2500,
                showConfirmButton: false,
            });
        });
        Livewire.on('swal:error', event => {
            // If you dispatch like: $this->dispatch('swal:success', ['title' => '...', 'text' => '...'])
            // then 'event' will be the array you passed.
            // If you dispatch like: $this->dispatch('swal:success', 'Title', 'Text')
            // then 'event' will be an array of arguments.

            // Try to support both ways:
            let detail = event;
            if (Array.isArray(event) && event.length === 1 && typeof event[0] === 'object') {
                detail = event[0];
            }
            // Now detail should be an object with 'title' and 'text'
            Swal.fire({
                icon: 'danger',
                title: detail.title || (event[0] ?? ''),
                text: detail.text || (event[1] ?? ''),
                timer: 2500,
                showConfirmButton: true,
            });
        });
    </script>
@endpush