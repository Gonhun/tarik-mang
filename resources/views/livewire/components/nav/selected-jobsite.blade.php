<div>
    <select id="jobsites" class="form-select" wire:change="ChangeJobsites($event.target.value)" aria-label="Default select example">
       @foreach ($jobsites as $jobsite )
        <option value="{{ $jobsite->id }}" {{  $jobsite->id == Auth::user()->jobsite_id ? 'selected' : '' }}>{{$jobsite->code}}</option>
        {{-- <option selected>Open this select menu</option> --}}
       @endforeach
      </select>
      
</div>
@push('scripts')
<script>
    Livewire.on('swal:GantiJobsite', (event) => {
        const detail = event[0] || event;
        Swal.fire({
            title: detail.title,
            text: detail.text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Oks',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
               
                if (detail.method && detail.id) {
                    Livewire.dispatch(detail.method, [detail.id]);
                } else {
                    console.error('method atau id tidak tersedia', { method, id });
                }
            }else{
            // Kembalikan value default (reset select ke jobsite awal)
            // Temukan elemen select dan set kembali ke jobsite user saat ini
            const select = document.getElementById('jobsites');
            if (select) {
                select.value = "{{ Auth::user()->jobsite_id }}";
            }
            }
        });
    });
    </script>
@endpush
