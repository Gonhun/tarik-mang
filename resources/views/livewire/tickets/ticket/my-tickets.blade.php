<div>
    <div class="content-wrapper">
        <div class="row">
          <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <p class="card-title mb-0">My Tickets</p>
                    <button class="btn btn-primary btn-sm" wire:click="openModal">
                        <i class="mdi mdi-plus"></i> Add
                    </button>
                </div>
                <div class="row mb-2">
                    <div class="col-md-12">
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Cari No Ticket, Kategori, Sub Kategori...">
                    </div>
                    <div class="col-md-12">
                        @if (session()->has('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @elseif ($errors->any())
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="table-responsive">
                  <table class="table table-striped table-borderless">
                    <thead>
                      <tr>
                        <th>Ticket No</th>
                        <th>Jobsite</th>
                        <th>Requester</th>
                        <th>Department</th>
                        <th>Category</th>
                        <th>Sub Title</th>
                        <th>Title</th>
                        <th>Reported On</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td>
                                    <a href="{{ route('tickets.ticket.detail', $ticket->ticket_no) }}" class="btn btn-primary btn-sm">
                                        <i class="mdi mdi-eye"></i>{{ $ticket->ticket_no }}
                                    </a>
                                </td>
                                <td>{{ $ticket->jobsite->code }}</td>
                                <td>{{ $ticket->user->name }}</td>
                                <td>{{ $ticket->user->department->code }}</td>
                                <td>{{ $ticket->subcategory->category->name }}</td>
                                <td>{{ $ticket->subcategory->name }}</td>
                                <td>{{ $ticket->title }}</td>
                                <td>{{ $ticket->created_at->format('d M Y') }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'open' => 'primary',
                                            'in_progress' => 'info',
                                            'pending' => 'warning',
                                            'resolved' => 'success',
                                            'closed' => 'secondary'
                                        ];
                                        $statusLabels = [
                                            'open' => 'Open',
                                            'in_progress' => 'In Progress',
                                            'pending' => 'Pending',
                                            'resolved' => 'Resolved',
                                            'closed' => 'Closed'
                                        ];
                                        $badgeColor = $statusColors[$ticket->status] ?? 'dark';
                                        $badgeLabel = $statusLabels[$ticket->status] ?? ucfirst($ticket->status);
                                    @endphp
                                    <span class="badge badge-{{ $badgeColor }}">
                                        {{ $badgeLabel }}
                                    </span>
                                </td>
                                {{-- <td>
                                    <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-primary btn-sm">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                   
                                </td> --}}
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No data found</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                          <td colspan="3" class="text-center">{{ $tickets->links() }}</td>
                        </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      @if ($isModalOpen)
      <!--
      Kenapa setelah close modal page jadi hitam dan freeze?
      Ini biasanya karena modal Bootstrap-nya hanya dihilangkan dari DOM (karena isModalOpen = false), 
      tapi backdrop/modal-backdrop masih tertinggal di DOM (tidak ikut dihapus oleh Bootstrap JS).
      Karena kita control modal via Livewire, bukan full oleh Bootstrap. Juga, class show/d-block manual bisa menyebabkan backdrop tidak terhapus.

      Solusi: saat close modal, pastikan juga menghapus .modal-backdrop dan class/d-block dari body/modal agar tidak freeze.
      -->
      <div >
        <div wire:ignore id="myModal" class="modal fade show d-block"  tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">Create New Ticket</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Jobsite <span class="text-danger">*</span></label>
                                        <select id="selectJobsites" class="selectJobsites form-control" data-live-search="true"  wire:model.defer="jobsite_id" >
                                            <option value="">-- Pilih Jobsites --</option>
                                            @foreach ($jobsites as $jobsite)
                                                <option value="{{ $jobsite->id }}">{{ $jobsite->code }} - {{ $jobsite->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('category_id') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div> 
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Category <span class="text-danger">*</span></label>
                                        <select id="selectCategorys" class="selectCategorys form-control" data-live-search="true"  wire:change="GetSubCategorys($event.target.value)" >
                                            <option value="">-- Pilih Kategori --</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('category_id') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>  
                                <div class="col-md-4">
                                    <div class="form-group" wire:ignore>
                                        <label>Sub Category <span class="text-danger">*</span></label>
                                        <select id="selectSubCategorys"
                                            class="selectSubCategorys form-control"
                                            data-live-search="true"
                                            wire:model.defer="subcategory_id">

                                            <option value="">-- Pilih Sub Kategori --</option>
                                        </select>
                                        @error('subcategory_id') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" wire:model.defer="title" placeholder="Title">
                                        @error('title') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" wire:model.defer="description" placeholder="Description"></textarea>
                                        @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Attachment</label>
                                        <input type="file" class="form-control" wire:model.defer="attachment" placeholder="Attachment">
                                        @error('attachment') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">Simpan</span>
                                <span class="d-none" wire:loading.class.remove="d-none">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
    @endif
</div>
@push('scripts')
<script>
/*
 * Kenapa setelah close modal page jadi hitam dan freeze?
 * Penyebab: Karena Livewire hanya menghapus elemen modal dari DOM tanpa memastikan backdrop modal hilang.
 * Backdrop .modal-backdrop (dan scroll-lock pada body) kadang tetap ada karena Bootstrap JS gak handle normal via Livewire.
 * 
 * Solusi: Hapus backdrop dan class yg berkaitan secara manual, ketika modal ditutup (event modal-closed).
 */
 document.addEventListener('livewire:initialized', () => {

    Livewire.on('sub-category-updated', (subs) => {
        let select = $('#selectSubCategorys');

        // destroy dulu biar ga numpuk
        select.selectpicker('destroy');

        // kosongkan
        select.empty();
        select.append('<option value="">-- Pilih Sub Kategori --</option>');

        // isi baru dari Livewire
        subs = subs.flat(); // hilangkan nested array
        subs.forEach(item => {
            select.append(`<option value="${item.id}">${item.name}</option>`);
        });
        // hidupkan lagi
        select.selectpicker({
            container: '#myModal'
        });

    });

});

document.addEventListener('modal-opened', function () {
    setTimeout(function() {
    
        $('#selectCategorys').selectpicker({
            container: '#myModal'
        });
        
        $('#selectJobsites').selectpicker({
            container: '#myModal'
        });
        $('#selectSubCategorys').selectpicker({
            container: '#myModal'
        });
    }, 10);
});
</script>
@endpush
