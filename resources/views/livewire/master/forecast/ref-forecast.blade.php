<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex Justify-content-between pb-0">
                        <h5 class="mb-0">Forecast</h5>
                        <div class="ms-auto align-items-baseline d-flex">
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <button class="btn btn-primary btn-sm mb-2" id="btnAdd" wire:click="openModal">
                                        <i class="bx bx-bxs-cog me-1"></i> Input Reference Forecast
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-1">
                            <table class="table table-bordered align-items-center mb-0 flex-nowrap">
                                <thead>
                                    <tr>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            No</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Aksi</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Material Code</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Material Name</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Max LT</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Avg LT</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Periode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->datas() as $data)
                                        <tr>
                                            <td class="align-middle text-center">
                                                <span
                                                    class="text-secondary text-xs font-weight-bold">{{ $loop->iteration }}</span>
                                            </td>
                                            <td class="align-middle text-center">
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
                                            <td class="align-middle text-center">
                                                <span
                                                    class="text-secondary text-xs font-weight-bold">{{ $data->material_code }}</span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span
                                                    class="text-secondary text-xs font-weight-bold">{{ $data->material_name }}</span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span
                                                    class="text-secondary text-xs font-weight-bold">{{ number_format($data->max_lt, 2) }}</span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span
                                                    class="text-secondary text-xs font-weight-bold">{{ number_format($data->avg_lt, 2) }}</span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span
                                                    class="text-secondary text-xs font-weight-bold">{{ $data->period }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 pt-3">
                            {{ $this->datas()->links('pagination::bootstrap-5') }}
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
                            <h5 class="modal-title" id="modalTambahCoaLabel">Tambah Reference Forecast Baru</h5>
                            <button type="button" wire:click.prevent="closeModal" class="btn-close"
                                data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form wire:submit="save">
                                <div class="mb-3">
                                    <label for="material_code" class="form-label">Material Code</label>
                                    <input type="text" class="form-control" id="material_code" name="material_code"
                                        wire:model="material_code" @if ($this->material_code) disabled @endif>
                                </div>
                                <div class="mb-3">
                                    <label for="material_name" class="form-label">Material Name</label>
                                    <input type="text" class="form-control" id="material_name" name="material_name"
                                        wire:model="material_name">
                                </div>
                                <div class="mb-3">
                                    <label for="max_lt" class="form-label">Max Leadtime</label>
                                    <input type="number" class="form-control" id="max_lt" name="max_lt" wire:model="max_lt" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label for="avg_lt" class="form-label">Avg Leadtime</label>
                                    <input type="number" class="form-control" id="avg_lt" name="avg_lt" wire:model="avg_lt" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label for="period" class="form-label">Period</label>
                                    <input type="number" class="form-control" id="period" name="period" wire:model="period">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                                    <button type="submit" class="btn btn-primary">
                                        <span wire:loading.remove
                                            wire:target="save">{{ $this->material_code ? 'Simpan Perubahan' : 'Simpan Data' }}</span>
                                        <span wire:loading wire:target="save">Menyimpan...</span>
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