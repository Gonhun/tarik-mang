<div>
    <div class="content-wrapper">
        <div class="row">
          <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <p class="card-title mb-0">{{ $ticket->ticket_no }}</p>
                   
                </div>
                <div class="row mb-2">
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
                <div class="row">
                    @php
                        $priorities = [
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                            'critical' => 'Critical',
                        ];
                        // Ambil semua user sebagai pilihan assign to (sebaiknya difilter di controller untuk user tertentu saja)
                    @endphp

                    <div class="col-md-6">
                        <h5>Tanggal & Jam Dibuat</h5>
                        <p>
                            {{ \Carbon\Carbon::parse($ticket->created_at)->format('d-m-Y H:i') }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5>Status</h5>
                        <span class="badge badge-{{ $ticket->status == 'open' ? 'primary' : ($ticket->status == 'closed' ? 'secondary' : 'info') }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                    </div>
                    
                    {{-- Kategori --}}
                    <div class="col-md-6">
                        <h5>Kategori</h5>
                        @if (!in_array($ticket->status, ['closed', 'resolved']) && $canManage)
                            <form wire:submit.prevent="updateCategory">
                                <select class="form-control mb-2" name="ticket_category_id" wire:model.live="categoryId" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach(\App\Models\TicketCategory::all() as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Simpan Kategori</button>
                            </form>
                        @else
                            <span>{{ $ticket->subCategory?->category?->name ?? '-' }}</span>
                        @endif
                    </div>
                    {{-- Sub Kategori --}}
                    <div class="col-md-6">
                        <h5>Sub Kategori</h5>
                        @if (!in_array($ticket->status, ['closed', 'resolved']) && $canManage)
                            <form wire:submit.prevent="updateSubCategory">
                                <select class="form-control mb-2" name="ticket_sub_category_id" wire:model="subCategoryId" required>
                                    <option value="">-- Pilih Sub Kategori --</option>
                                    @foreach($subCategories as $subCategory)
                                        <option value="{{ $subCategory->id }}">{{ $subCategory->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Simpan Sub Kategori</button>
                            </form>
                        @else
                            <span>{{ optional($ticket->subCategory)->name ?? '-' }}</span>
                        @endif
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Prioritas</h5>
                        @if ($canManage && !in_array($ticket->status, ['closed', 'resolved']))
                            <form wire:submit.prevent="updatePriority">
                                <select class="form-control mb-2" name="priority" wire:model="priority" required>
                                    <option value="">-- Pilih Prioritas --</option>
                                    @foreach($priorities as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Simpan Prioritas</button>
                            </form>
                        @else
                            <span class="badge badge-{{ $ticket->priority == 'low' ? 'success' : ($ticket->priority == 'medium' ? 'warning' : ($ticket->priority == 'high' ? 'danger' : 'dark')) }}">{{ ucfirst($ticket->priority) }}</span>
                        @endif
                    </div>
                    
                    <div class="col-md-6  mb-2">
                        <h5>Assigned To</h5>
                        @if (is_null($ticket->assigned_to) && $canManage)
                            <form wire:submit="assigned">
                                <select id="selectAssigned" data-live-search="true" class="form-control mb-2" name="assigned_to" wire:model="assignedId" required>
                                    <option value="">-- Pilih Penanggung Jawab --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->jobsite->code }} - {{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Assign</button>
                            </form>
                        @else
                            <span>{{ optional($ticket->assignedTo)->name ?? '-' }}</span>
                        @endif
                    </div>
                
                
                <div class="col-md-12 mt-2">
                    <h5>Judul Ticket</h5>
                    <p class="font-weight-bold text-capitalize mb-2">{{ $ticket->title }}</p>
                </div>
                <div class="col-md-12">
                    <h5>Deskripsi</h5>
                    <textarea id="resolution_note" class="form-control" rows="3" disabled> {!! nl2br(e($ticket->description)) !!} </textarea>
                    
                    {{-- Jika ada attachment tampilkan link download --}}
                    @if ($ticket->attachment)
                        <div class="my-3">
                            <h5>Lampiran (Attachment)</h5>
                            <a href="{{ asset('storage/' . $ticket->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-paperclip"></i> Download Attachment
                            </a>
                        </div>
                    @endif
                </div>
                <div class="col-md-12 mt-3">
                    <div class="col-md-12">
                        @if ($ticket->status !== 'closed')
                        <h5>Status</h5>
                        <form wire:submit.prevent="updateStatus">
                            <select class="form-control mb-2" name="status" wire:model.live="setStatus" required>
                                @if($ticket->status !== 'resolved')
                                    @foreach($status as $key => $label)
                                        @if ($key !== 'closed')
                                            <option value="{{ $key }}" @selected($ticket->status == $key)>{{ $label }}</option>
                                        @endif
                                    @endforeach
                                @else
                                    @if(Auth::id() == $ticket->user_id)
                                        <option >Pilih Status</option>
                                        <option value="closed" >Closed</option>
                                        <option value="open" >Re-open</option>
                                    @endif
                                @endif
                               
                            </select>
                               
                                <div class="mb-2">
                                    <label for="resolution_note">Catatan Penyelesaian (Resolution Note)</label>
                                    <textarea id="resolution_note" class="form-control" rows="3" wire:model="resolutionNote" placeholder="Tambahkan catatan penyelesaian jika diperlukan..." {{ $setStatus == 'resolved'? 'required' : '' }} {{ $canManage ? '' : 'disabled'}} {{ $ticket->status == 'closed' ? 'disabled' : ''}}></textarea>
                                </div>
                           
                            @if($setStatus !== $ticket->status)
                                <button class="btn btn-sm btn-primary" type="submit">Update Status</button>
                            @endif
                        </form>
                        @else
                            <div class="mb-2">
                                <label for="resolution_note">Catatan Penyelesaian (Resolution Note)</label>
                                <textarea id="resolution_note" class="form-control" rows="3" wire:model="resolutionNote" placeholder="Tambahkan catatan penyelesaian jika diperlukan..." {{ $setStatus == 'resolved'? 'required' : '' }} {{ $canManage ? '' : 'disabled'}} {{ $ticket->status == 'closed' ? 'disabled' : ''}}></textarea>
                            </div>
                            @if($ticket->status == 'closed' && $ticket->rating == null)
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <h5 class="mb-2">Berikan Rating Penyelesaian</h5>

                                        <div class="rating mb-2">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fa fa-star {{ $rating >= $i ? 'text-warning' : 'text-secondary' }}"
                                                wire:click="setRating({{ $i }})"
                                                style="cursor: pointer; font-size: 22px;"></i>
                                            @endfor
                                        </div>

                                        <textarea class="form-control mt-2"
                                                placeholder="Tambahkan catatan (opsional)"
                                                wire:model.defer="rating_note"></textarea>

                                        <button class="btn btn-primary mt-2" wire:click="submitRating">
                                            Submit Rating
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @endif
                        
                    </div>
                </div>
                <div class="col-md-12">
                    {{-- Routemap Proses Ticket --}}
                    <div class="my-4">
                        <h5 class="mb-3">Workflow & Detail Proses Tiket</h5>
                        <div class="d-flex align-items-center flex-wrap" style="gap:20px;">
                            {{-- Step 1: Tiket Dibuat --}}
                            <div class="text-center">
                                <div class="rounded-circle {{ in_array($ticket->status, ['open','in_progress','pending','resolved','closed']) ? 'bg-primary text-white' : 'bg-secondary text-white' }}" 
                                    style="width:48px;height:48px;line-height:48px;display:inline-block;font-weight:600;font-size:20px;">
                                    1
                                </div>
                                <div class="mt-2 text-secondary small">Created</div>
                            </div>
                            <div style="width:40px;height:4px;background:#e1e5ea;margin:0 0;border-radius:2px;"></div>

                            {{-- Step 2: Penugasan --}}
                            <div class="text-center">
                                <div class="rounded-circle {{ in_array($ticket->status, ['in_progress','pending','resolved','closed']) ? 'bg-info text-white' : 'bg-secondary text-white' }}"
                                    style="width:48px;height:48px;line-height:48px;display:inline-block;font-weight:600;font-size:20px;">
                                    2
                                </div>
                                <div class="mt-2 text-secondary small">Assigned</div>
                            </div>
                            <div style="width:40px;height:4px;background:#e1e5ea;margin:0 0;border-radius:2px;"></div>

                            {{-- Step 3: Diproses --}}
                            <div class="text-center">
                                <div class="rounded-circle {{ in_array($ticket->status, ['pending','resolved','closed']) ? 'bg-warning text-dark' : 'bg-secondary text-white' }}"
                                    style="width:48px;height:48px;line-height:48px;display:inline-block;font-weight:600;font-size:20px;">
                                    3
                                </div>
                                <div class="mt-2 text-secondary small">In Progress</div>
                            </div>
                            <div style="width:40px;height:4px;background:#e1e5ea;margin:0 0;border-radius:2px;"></div>

                            {{-- Step 4: Selesai --}}
                            <div class="text-center">
                                <div class="rounded-circle {{ in_array($ticket->status, ['resolved','closed']) ? 'bg-success text-white' : 'bg-secondary text-white' }}"
                                    style="width:48px;height:48px;line-height:48px;display:inline-block;font-weight:600;font-size:20px;">
                                    4
                                </div>
                                <div class="mt-2 text-secondary small">Resolved</div>
                            </div>
                            <div style="width:40px;height:4px;background:#e1e5ea;margin:0 0;border-radius:2px;"></div>
                            {{-- Step 5: Closed --}}
                            <div class="text-center">
                                <div class="rounded-circle {{ in_array($ticket->status, ['closed']) ? 'bg-dark text-white' : 'bg-secondary text-white' }}"
                                    style="width:48px;height:48px;line-height:48px;display:inline-block;font-weight:600;font-size:20px;">
                                    5
                                </div>
                                <div class="mt-2 text-secondary small">Closed</div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <ul class="list-group mb-2">
                                    <li class="list-group-item py-2 px-3">
                                        <strong>Dibuat oleh: </strong> {{ optional($ticket->user)->name ?? '-' }} <br>
                                        <strong>Waktu pembuatan: </strong> {{ $ticket->created_at ? \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i') : '-' }}
                                    </li>
                                    <li class="list-group-item py-2 px-3">
                                        <strong>Ditugaskan oleh:</strong> 
                                        {{ optional($ticket->assignedBy)->name ?? '-' }} <br>
                                        <strong>Ditugaskan ke:</strong> 
                                        {{ optional($ticket->assignedTo)->name ?? '-' }} <br>
                                        <strong>Jam penugasan:</strong> 
                                        {{ $ticket->assigned_at ? \Carbon\Carbon::parse($ticket->assigned_at)->format('d/m/Y H:i') : '-' }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group mb-2">
                                    <li class="list-group-item py-2 px-3">
                                        <strong>Diselesaikan oleh:</strong> {{ optional($ticket->resolvedBy)->name ?? '-' }}
                                        <br>
                                        <strong>Jam selesai (Resolved):</strong> 
                                        {{ $ticket->status === 'resolved' ? ($ticket->resolved_at ? \Carbon\Carbon::parse($ticket->resolved_at)->format('d/m/Y H:i') : '-') : '-' }}
                                    </li>
                                    <li class="list-group-item py-2 px-3">
                                        <strong>Ditutup oleh:</strong> {{ optional($ticket->closedBy)->name ?? '-' }} <br>
                                        <strong>Jam ditutup:</strong>
                                        {{ $ticket->closed_at ? \Carbon\Carbon::parse($ticket->closed_at)->format('d/m/Y H:i') : '-' }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        {{-- <div class="mt-2 small text-muted">
                            <span class="badge bg-primary">Created</span>
                            <span class="badge bg-info">Assigned</span>
                            <span class="badge bg-warning text-dark">In Progress</span>
                            <span class="badge bg-success">Resolved</span>
                            <span class="badge bg-dark">Closed</span>
                        </div> --}}
                    </div>

                    {{-- Resolution Note --}}
                    @if($ticket->resolution_note)
                        <div class="my-3">
                            <h5 class="mb-1">Catatan Penyelesaian (Resolution Note)</h5>
                            <div class="border rounded px-3 py-2 bg-light">
                                {!! nl2br(e($ticket->resolution_note)) !!}
                            </div>
                        </div>
                    @endif
                </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
     
</div>
@push('scripts')
<script>
$('#selectAssigned').selectpicker();
 document.addEventListener('livewire:initialized', () => {

    console.log('asd');

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
