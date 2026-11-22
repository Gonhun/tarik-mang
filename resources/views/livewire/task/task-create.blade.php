<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tambah Task</h5>
            <a href="{{ route('task.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    
        <div class="card-body">
            @if (session()->has('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
    
            <form wire:submit.prevent="save">
    
                <div class="mb-3">
                    <label class="form-label">Judul Task</label>
                    <input type="text" wire:model="title" class="form-control">
                    @error('title') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
    
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea wire:model="description" class="form-control"></textarea>
                </div>
    
                <div class="mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" wire:model="start_date" class="form-control" disabled>
                    @error('start_date') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
    
                <div class="mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" wire:model="due_date" class="form-control">
                    @error('due_date') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
    
                <div class="mb-3">
                    <label class="form-label">Assign Ke User</label>
                    <select wire:model="assigned_to" class="form-select">
                        <option value="">-- Pilih User --</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to') 
                        <small class="text-danger">{{ $message }}</small> 
                    @enderror
                </div>
    
                <button class="btn btn-primary">Simpan Task</button>
    
            </form>
        </div>
    </div>
</div>
