<div class="container mt-4">
    <div class="mb-4 d-flex justify-content-end">
        <a href="{{ route('task.create') }}" class="btn btn-primary">
            <i class="bi bi-plus"></i> Add Task
        </a>
    </div>
    <div class="mb-4">
        <input
            type="text"
            class="form-control"
            placeholder="Cari task berdasarkan judul, deskripsi, atau user..."
            wire:model.debounce.live.500ms="search"
        >
    </div>
    <div class="row">
        <div class="accordion" id="accordionJobsite">
            @php
                $groupedTasks = $tasks->groupBy(function($task) {
                    return $task->user && $task->user->jobsite ? $task->user->jobsite->code : 'Tanpa Jobsite';
                });
            @endphp
            @foreach($groupedTasks as $jobsiteCode => $siteTasks)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-{{ \Illuminate\Support\Str::slug($jobsiteCode) }}">
                        <button class="accordion-button collapsed" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapse-{{ \Illuminate\Support\Str::slug($jobsiteCode) }}"
                            aria-expanded="false"
                            aria-controls="collapse-{{ \Illuminate\Support\Str::slug($jobsiteCode) }}">
                            Task Jobsite: {{ $jobsiteCode }}
                        </button>
                    </h2>
                    <div id="collapse-{{ \Illuminate\Support\Str::slug($jobsiteCode) }}"
                        class="accordion-collapse collapse"
                        aria-labelledby="heading-{{ \Illuminate\Support\Str::slug($jobsiteCode) }}"
                        data-bs-parent="#accordionJobsite">
                        <div class="accordion-body">
                            <div class="row">
                                @foreach($siteTasks as $task)
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body">

                                                {{-- User Info --}}
                                                <div class="d-flex align-items-center mb-3">
                                                    @if($task->user && $task->user->profile_photo_url)
                                                        <img src="{{ $task->user->profile_photo_url }}" alt="Profile Photo" class="rounded-circle me-2" width="40" height="40">
                                                    @else
                                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($task->user?->name ?? 'User') }}&background=0D8ABC&color=fff" alt="User" class="rounded-circle me-2" width="40" height="40">
                                                    @endif
                                                    <div>
                                                        <strong class="d-block">
                                                            {{ $task->user?->name ?? 'Tidak Ada User' }}
                                                            @if($task->user && $task->user->whatsapp)
                                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $task->user->whatsapp) }}"
                                                                    target="_blank"
                                                                    title="Chat via WhatsApp"
                                                                    class="ms-2"
                                                                    style="text-decoration: none; color: #25D366;">
                                                                    <i class="bi bi-whatsapp" style="font-size: 1.2em;"></i>
                                                                </a>
                                                            @endif
                                                        </strong>
                                                        <small class="text-muted">Assigned User</small>
                                                    </div>
                                                </div>

                                                <h5 class="card-title mb-2">{{ $task->title }}</h5>
                                                
                                                {{-- Due Date Display --}}
                                                <div class="mb-2">
                                                    <span class="badge bg-light text-dark">
                                                        <i class="bi bi-calendar-event me-1"></i>
                                                        @if($task->due_date)
                                                            Due: {{ \Carbon\Carbon::parse($task->due_date)->locale('id')->isoFormat('D MMM YYYY') }}
                                                        @else
                                                            <span class="fst-italic text-muted">No Due Date</span>
                                                        @endif
                                                    </span>
                                                </div>

                                                @if($task->description)
                                                    <p class="text-muted mb-3">{{ $task->description }}</p>
                                                @else
                                                    <p class="text-muted mb-3 fst-italic">Tidak ada deskripsi</p>
                                                @endif

                                                {{-- Status Badge --}}
                                                <span class="badge 
                                                @if($task->status=='pending') bg-secondary
                                                @elseif($task->status=='progress') bg-warning
                                                @else bg-success @endif">
                                                    {{ ucfirst($task->status) }}
                                                </span>

                                                {{-- Progress Bar --}}
                                                <div class="mt-3">
                                                    <label>Progress: {{ $task->progress }}%</label>
                                                    <div class="progress">
                                                        <div class="progress-bar 
                                                        @if($task->progress==100) bg-success @else bg-info @endif" 
                                                        style="width: {{ $task->progress }}%">
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                @if($task->user_id == Auth::id() && $task->progress!==100)
                                                    <button class="btn btn-info btn-sm mt-3"
                                                        wire:click="openModal({{ $task->id }})">
                                                        Update Progress
                                                    </button>
                                                @endif
                                                {{-- Riwayat Log --}}
                                                <div class="mt-3">
                                                    <strong>History:</strong>
                                                    <div style="max-height: 180px; overflow-y: auto;">
                                                        <ul class="list-group">
                                                            @forelse($task->logs as $log)
                                                                <li class="list-group-item">
                                                                    <strong>{{ $log->progress }}%</strong> - {{ $log->note }} <br>
                                                                    <small class="text-muted">
                                                                        {{ $log->created_at->diffForHumans() }}
                                                                        @if($log->user)
                                                                            &middot; oleh {{ $log->user->name }}
                                                                        @endif
                                                                    </small>
                                                                </li>
                                                            @empty
                                                                <li class="list-group-item text-muted fst-italic">Belum ada riwayat</li>
                                                            @endforelse
                                                        </ul>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- MODAL --}}
    <div class="modal fade" id="progressModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Update Progress</h5>
                </div>

                <div class="modal-body">
                    
                    <label>Progress (%)</label>
                    <input type="number" min="0" max="100" class="form-control"
                        wire:model="progressInput">
                    @error('progressInput')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                    <label class="mt-3">Catatan Progress</label>
                    <textarea class="form-control" rows="3" wire:model="note"
                        placeholder="Apa yang sudah dikerjakan?"></textarea>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-success" wire:click="saveProgress">
                        Simpan
                    </button>
                </div>

            </div>
        </div>
    </div>
    
</div>

{{-- JS Event Listener untuk Modal Bootstrap --}}
<script>
    window.addEventListener('open-progress-modal', () => {
        var myModal = new bootstrap.Modal(document.getElementById('progressModal'));
        myModal.show();
    });

    window.addEventListener('close-progress-modal', () => {
        var modalEl = document.getElementById('progressModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
    });
</script>
