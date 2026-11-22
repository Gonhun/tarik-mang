    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
      @if (session()->has('success'))<div class="alert alert-success mt-2">{{ session('success') }}</div>@endif
      <div class="row">
        <div class="col-md-12">
          <div class="nav-align-top">
            <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-md-0 gap-2">
              <li class="nav-item">
                <a class="nav-link {{ $tab == 0 ? 'active' : '' }}" wire:click="$set('tab', 0)"
                  ><i class="icon-base bx bx-user icon-sm me-1_5"></i> Account</a
                >
              </li>
              {{-- <li class="nav-item">
                <a class="nav-link {{ $tab == 1 ? 'active' : '' }}" wire:click="$set('tab', 1)"
                  ><i class="icon-base bx bx-link-alt icon-sm me-1_5"></i> Asset Usage</a
                >
              </li> --}}
              <li class="nav-item">
                <a class="nav-link {{ $tab == 2 ? 'active' : '' }}" wire:click="$set('tab', 2)" href="#"
                  ><i class="icon-base bx bx-bell icon-sm me-1_5"></i> Logging</a
                >
              </li>
              
            </ul>
          </div>
         @if ($tab == 0)
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
                      readonly />
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
                        readonly
                        placeholder="202 555 0111" />
                    </div>
                    @error('wa') <span class="text-danger">{{ $message }}</span> @enderror
                  </div>
                  <div class="col-md-6">
                    <label for="language" class="form-label">Jobsite</label>
                    <select id="language" wire:model="jobsite_id" class="select2 form-select @error('jobsite_id') is-invalid @enderror" readonly>
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
                    <select id="language" wire:model.live="manager_id" 
                    class="select2 form-select @if (session()->has('manager')) is-valid @endif @error('manager_id') is-invalid @enderror" readonly>
                        @if (!empty($usersForManagerSelection))
                        <option value="">---PILIH ATASAN---</option>
                        @foreach ($usersForManagerSelection as $managerOption)
                            <option value="{{ $managerOption->id }}">{{$managerOption->name}} ['{{ $managerOption->nik }}','{{ $managerOption->department->code }}']</option>
                        @endforeach
                        @endif
                    </select>
                    @if (session()->has('manager'))<div style="color: green">{{ session('manager') }}</div>@endif
                    @error('manager_id') <span class="text-danger">{{ $message }}</span> @enderror
                  </div>
                  <div class="col-md-6">
                    <label for="address" class="form-label">Departement</label>
                    <input type="text" class="form-control" id="address" name="address" value="{{ $user->department->name }}" 
                    placeholder="Address" readonly/>
                  </div>
                  <div class="col-md-6">
                    <label for="state" class="form-label">Jabatan</label>
                    <input class="form-control" type="text" id="state" name="state" placeholder="California" 
                    value="{{ $user->position->name }}" readonly/>
                  </div>
                  <div class="col-md-6">
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
                  </div>
                  
                </div>
                <div class="mt-6">
                  <button type="submit" class="btn btn-primary me-3">Save changes</button>
                  <button type="reset" class="btn btn-outline-secondary">Cancel</button>
                </div>
              </form>
            </div>
            <!-- /Account -->
          </div>
          @elseif ($tab == 1)
          <div class="card mb-6">
            <div class="card-body text-center py-5">
              <h3 class="mb-3"><i class="bx bx-time-five" style="font-size:2.5rem;color:#ff9800"></i></h3>
              <h4 class="mb-2">Halaman Belum Siap</h4>
              <p class="text-muted mb-0">Fitur ini sedang dalam tahap pengembangan.<br>Silakan kembali lagi nanti.</p>
            </div>
          </div>
          @elseif ($tab == 2)
          <div class="card mb-6">
            <!-- Log -->
            <div class="table-responsive p-0 mt-3">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Waktu</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">User</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Aksi</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Model Terdampak</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">IP Address</th>
                            <th class="text-secondary opacity-7"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($audits as $audit)
                            <tr>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0 px-3" data-bs-toggle="tooltip" title="{{ $audit->created_at->format('d M Y H:i:s') }}">
                                        {{ $audit->created_at->diffForHumans() }}
                                    </p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">
                                        {{ $audit->user->name ?? ($audit->user_id ?? 'Sistem/Tamu') }}
                                    </p>
                                    @if($audit->user)
                                    <p class="text-xs text-secondary mb-0">{{ $audit->user->email ?? '' }}</p>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-sm bg-gradient-{{ 
                                        $audit->event == 'created' ? 'success' : 
                                        ($audit->event == 'updated' ? 'info' : 
                                        ($audit->event == 'deleted' ? 'danger' : 
                                        ($audit->event == 'restored' ? 'warning' : 'secondary'))) 
                                        }}">
                                        {{ ucfirst($audit->event) }}
                                    </span>
                                </td>
                                <td><p class="text-xs font-weight-bold mb-0">{{ $this->getCleanModelName($audit->auditable_type) }}</p></td>
                               <td><p class="text-xs font-weight-bold mb-0">{{ $audit->ip_address }}</p></td>
                                <td class="align-middle">
                                    <button wire:click="viewDetails({{ $audit->id }})" class="btn btn-link text-primary p-1 mb-0" data-bs-toggle="tooltip" title="Lihat Detail Perubahan">
                                        <i class="bx bx-bxs-bullseye text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4">Tidak ada data log audit ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 pt-3">
                {{ $audits->links() }}
            </div>
            @if ($isDetailModalOpen && $selectedAudit)
                <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detail Audit #{{ $selectedAudit->id }}</h5>
                                <button type="button" class="btn-close" wire:click="closeDetailModal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Waktu:</strong> {{ $selectedAudit->created_at->format('d M Y, H:i:s') }} ({{ $selectedAudit->created_at->diffForHumans() }})</p>
                                <p><strong>User:</strong> {{ $selectedAudit->user->name ?? ($selectedAudit->user_id ?? 'Sistem/Tamu') }}</p>
                                <p><strong>Aksi:</strong> <span class="badge bg-gradient-info">{{ ucfirst($selectedAudit->event) }}</span></p>
                                <p><strong>Model Terdampak:</strong> {{ $this->getCleanModelName($selectedAudit->auditable_type) }} (ID: {{ $selectedAudit->auditable_id }})</p>
                                <p><strong>URL:</strong> {{ $selectedAudit->url }}</p>
                                <p><strong>IP Address:</strong> {{ $selectedAudit->ip_address }}</p>
                                <p><strong>User Agent:</strong> <small>{{ $selectedAudit->user_agent }}</small></p>

                                @if (!empty($selectedAudit->old_values) || !empty($selectedAudit->new_values))
                                    <hr>
                                    <h6>Perubahan Data:</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Field</th>
                                                    <th>Nilai Lama</th>
                                                    <th>Nilai Baru</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $oldV = (array) ($selectedAudit->old_values ?? []);
                                                    $newV = (array) ($selectedAudit->new_values ?? []);
                                                    $allKeys = array_keys($oldV + $newV); // Gabungkan semua kunci untuk perbandingan
                                                @endphp
                                                @forelse ($allKeys as $key)
                                                    @if(array_key_exists($key, $oldV) || array_key_exists($key, $newV))
                                                        @if(isset($oldV[$key]) || isset($newV[$key])) {{-- Hanya tampilkan jika ada nilai lama atau baru --}}
                                                            @if (!isset($oldV[$key]) || !isset($newV[$key]) || $oldV[$key] !== $newV[$key] || $selectedAudit->event == 'created')
                                                            <tr>
                                                                <td><strong>{{ Str::title(str_replace('_', ' ', $key)) }}</strong></td>
                                                                <td>
                                                                    @if(is_array($oldV[$key] ?? null)) {{ json_encode($oldV[$key] ?? '') }}
                                                                    @else {{ $oldV[$key] ?? '' }}
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if(is_array($newV[$key] ?? null)) {{ json_encode($newV[$key] ?? '') }}
                                                                    @else {{ $newV[$key] ?? '' }}
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            @endif
                                                        @endif
                                                    @endif
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center"><em>Tidak ada detail perubahan data yang tercatat atau data tidak berubah (untuk event 'updated').</em></td>
                                                    </tr>
                                                @endforelse
                                                @if($selectedAudit->event == 'deleted' && !empty($oldV) && empty($allKeys))
                                                    @foreach ($oldV as $key => $value)
                                                    <tr>
                                                        <td><strong>{{ Str::title(str_replace('_', ' ', $key)) }}</strong></td>
                                                        <td>
                                                            @if(is_array($value)) {{ json_encode($value) }}
                                                            @else {{ $value }}
                                                            @endif
                                                        </td>
                                                        <td class="text-muted"><em>(dihapus)</em></td>
                                                    </tr>
                                                    @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p><em>Tidak ada detail perubahan nilai lama/baru yang tercatat untuk event ini.</em></p>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" wire:click="closeDetailModal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <!-- /Log -->
          </div>
         @endif
        </div>
      </div>
    </div>
    <!-- / Content -->
