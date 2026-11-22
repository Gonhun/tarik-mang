<div class="card mb-6">
    <!-- Account -->
    <div class="card-body">
      <div class="flex flex-col items-center justify-center mb-3">
        <span class="badge bg-gradient-primary text-white px-4 py-2 fs-5 shadow-sm" >
         NIK: <span class="fw-bold">{{ $user->nik }}</span>
        </span>
       
      </div>
      <div class="d-flex align-items-start align-items-sm-center gap-6 pb-4 border-bottom">
        <div class="relative w-[100px] h-[100px] group cursor-pointer">
            <img
          src="@if ($pp && method_exists($pp, 'temporaryUrl'))
                    {{ $pp->temporaryUrl() }}
                @elseif (!empty($existing_pp_path))
                    {{ asset('storage/' . $existing_pp_path) }}
                @else
                    {{ asset('assets/img/avatars/1.png') }}
                @endif"
          alt="user-avatar"
          class="d-block w-px-100 h-px-100 rounded object-cover"
          id="uploadedAvatar" />
          <div
          class="absolute inset-0 bg-black/50 text-white flex items-center justify-center text-sm font-semibold opacity-0 group-hover:opacity-100 transition-opacity duration-200"
      >
          Upload
          
      </div>
      
          <div wire:loading wire:target="pp" class="mt-1 text-primary">Uploading...</div>
          @error('pp') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        
        <div class="button-wrapper">
          <label for="upload" class="btn btn-primary me-3 mb-4" tabindex="0">
            <span class="d-none d-sm-block">Upload new photo</span>
            <i class="icon-base bx bx-upload d-block d-sm-none"></i>
            <input
              type="file"
              id="upload"
              wire:model="pp"
              class="account-file-input"
              hidden />
          </label>
          <button type="button" wire:click="resetInputFields" class="btn btn-outline-secondary account-image-reset mb-4">
            <i class="icon-base bx bx-reset d-block d-sm-none"></i>
            <span class="d-none d-sm-block">Reset</span>
          </button>

          <div>Allowed JPG, GIF or PNG. Max size of 1Mb</div>
          
        </div>
      </div>
    </div>
    <div class="card-body pt-4">
      <form wire:submit.prevent="store" id="formAccountSettings" method="POST" onsubmit="return false">
        <div class="row g-6">
          <div class="col-md-6">
            <label for="firstName" class="form-label">First Name</label>
            <input
              class="form-control @error('name') is-invalid @enderror"
              type="text"
              id="firstName"
              name="firstName"
              wire:model="name"
              disabled />
            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label" for="phoneNumber">Whatsapp Number</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text">ID (+62)</span>
              <input
                type="text"
                id="phoneNumber"
                name="phoneNumber"
                wire:model="wa"
                class="form-control @error('wa') is-invalid @enderror"
                placeholder="202 555 0111" 
                />
            </div>
            @error('wa') <span class="text-danger">{{ $message }}</span> @enderror
          </div>
          <div class="col-md-6">
            <label for="language" class="form-label">Jobsite</label>
            <select id="language" wire:model="jobsite_id" class="select2 form-select @error('jobsite_id') is-invalid @enderror" disabled>
                @if (!empty($jobsistes))
                @foreach ($jobsistes as $jobsiste)
                    <option value="{{ $jobsiste->id }}">{{$jobsiste->name}} ({{$jobsiste->code}})</option>
                @endforeach
                @endif
            </select>
            @error('jobsite_id') <span class="text-danger">{{ $message }}</span> @enderror
          </div>
          <div class="col-md-6">
            <label for="language" class="form-label">Atasan Langsung</label>
            <select id="language" wire:model.live="manager_id" class="select2 form-select @if (session()->has('manager')) is-valid @endif @error('manager_id') is-invalid @enderror" disabled>
                @if (!empty($usersForManagerSelection))
                <option value="">---PILIH ATASAN---</option>
                @foreach ($usersForManagerSelection as $managerOption)
                    <option value="{{ $managerOption->id }}">{{ $managerOption->name }} · {{ $managerOption->nik }}{{ $managerOption->department ? ' · ' . $managerOption->department->code : '' }}{{ $managerOption->position ? ' · ' . $managerOption->position->name : '' }}</option>
                @endforeach
                @endif
            </select>
            @if (session()->has('manager'))<div style="color: green">{{ session('manager') }}</div>@endif
            @error('manager_id') <span class="text-danger">{{ $message }}</span> @enderror
          </div>
          <div class="col-md-6">
            <label for="address" class="form-label">Departement</label>
            <input type="text" class="form-control" id="address" disabled name="address" value="{{ $user->department->name ?? '-' }}" placeholder="Address" readonly/>
          </div>
          <div class="col-md-6">
            <label for="state" class="form-label">Jabatan</label>
            <input class="form-control" type="text" disabled id="state" name="state" placeholder="California" value="{{ $user->karyawans->name ?? '-' }}" readonly/>
          </div>
          {{-- <div class="col-md-6">
            <label class="form-label" for="inputGroupFile01">Struktur Organisasi</label>
            <input type="file" wire:model="so" class="form-control @error('so') is-invalid @enderror" id="inputGroupFile01">
            <div wire:loading wire:target="so" class="mt-1 text-primary">Uploading...</div>
            <div style="color: {{ empty($user->so_path) ? 'red' : 'green' }}">{{ empty($user->so_path) ? 'Belum ada Struktur Organisasi di upload' : 'Sudah ada Struktur Organisasi di upload' }}</div>
            @error('so') <span class="text-danger">{{ $message }}</span> @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label" for="inputGroupFile01">Jobs Desk</label>
            <input type="file" wire:model="jd" class="form-control @error('jd') is-invalid @enderror" id="inputGroupFile01">
            <div wire:loading wire:target="jd" class="mt-1 text-primary">Uploading...</div>
            <div style="color: {{ empty($user->jd_path) ? 'red' : 'green' }}">{{ empty($user->so_path) ? 'Belum ada Job Desk di upload' : 'Sudah ada Job Desk di upload' }}</div>
            @error('jd') <span class="text-danger">{{ $message }}</span> @enderror
          </div> --}}
          
        </div>
        <div class="mt-6">
          <button type="submit" class="btn btn-primary me-3">Save changes</button>
          <button type="reset" class="btn btn-outline-secondary">Cancel</button>
        </div>
      </form>
    </div>
    <!-- /Account -->
  </div>

  <div class="mt-4">
    <label class="form-label">Lihat File Struktur Organisasi / Job Desk</label>
    <div class="d-flex flex-wrap gap-3">
      @if (!empty($user->so_path))
        <div>
          <span class="fw-semibold">Struktur Organisasi:</span>
          @php
            $soExt = pathinfo($user->so_path, PATHINFO_EXTENSION);
          @endphp
          @if (in_array(strtolower($soExt), ['jpg','jpeg','png','gif','bmp','webp']))
            <a href="{{ asset('storage/'.$user->so_path) }}" target="_blank" class="btn btn-sm btn-info ms-2">Lihat Gambar</a>
            <br>
            <img src="{{ asset('storage/'.$user->so_path) }}" alt="Struktur Organisasi" style="max-width:220px;max-height:180px;margin-top:8px;border-radius:8px;box-shadow:0 2px 8px #0001;">
          @elseif (strtolower($soExt) === 'pdf')
            <a href="{{ asset('storage/'.$user->so_path) }}" target="_blank" class="btn btn-sm btn-info ms-2">Lihat PDF</a>
            <br>
            <iframe src="{{ asset('storage/'.$user->so_path) }}" style="width:220px;height:180px;margin-top:8px;border-radius:8px;box-shadow:0 2px 8px #0001;" frameborder="0"></iframe>
          @else
            <a href="{{ asset('storage/'.$user->so_path) }}" target="_blank" class="btn btn-sm btn-info ms-2">Download File</a>
          @endif
        </div>
      @endif
     
      @if (!empty($user->position->job_description_path))
        <div>
          <span class="fw-semibold">Job Desk: </span>
          @php
            $jdExt = pathinfo($user->position->job_description_path, PATHINFO_EXTENSION);
          @endphp
          @if (in_array(strtolower($jdExt), ['jpg','jpeg','png','gif','bmp','webp']))
            <a href="{{ asset('storage/'.$user->position->job_description_path) }}" target="_blank" class="btn btn-sm btn-info ms-2">Lihat Gambar</a>
            <br>
            <img src="{{ asset('storage/'.$user->position->job_description_path) }}" alt="Job Desk" style="max-width:220px;max-height:180px;margin-top:8px;border-radius:8px;box-shadow:0 2px 8px #0001;">
          @elseif (strtolower($jdExt) === 'pdf')
            <a href="{{ asset('storage/'.$user->position->job_description_path) }}" target="_blank" class="btn btn-sm btn-info ms-2">Lihat PDF</a>
            <br>
            <iframe src="{{ asset('storage/'.$user->position->job_description_path) }}" style="width:220px;height:180px;margin-top:8px;border-radius:8px;box-shadow:0 2px 8px #0001;" frameborder="0"></iframe>
          @else
            <a href="{{ asset('storage/'.$user->position->job_description_path) }}" target="_blank" class="btn btn-sm btn-info ms-2">Download File</a>
          @endif
        </div>
      @endif

      @if (empty($user->position->job_description_path))
        <div class="text-muted">Belum ada file Struktur Organisasi atau Job Desk yang diupload.</div>
      @endif
    </div>
  </div>