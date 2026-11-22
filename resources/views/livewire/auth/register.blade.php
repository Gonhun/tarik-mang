<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner position-relative">
        <!-- Animated Background -->
        <div class="animated-background">
          <div class="circle circle-1"></div>
          <div class="circle circle-2"></div>
          <div class="circle circle-3"></div>
          <div class="circle circle-4"></div>
          <div class="circle circle-5"></div>
        </div>
        <!-- Register Card -->
        <div class="card px-sm-6 px-0 border-0 shadow-lg rounded-4 position-relative z-1">
          <div class="card-body p-5">
            <!-- Logo -->
            <div class="app-brand justify-content-center mb-6">
              <a href="index.html" class="app-brand-link gap-2">
                <span class="app-brand-logo demo">
                  <span class="text-primary">
                    <i class="bx bx-chip fs-1"></i>
                  </span>
                </span>
                <span class="app-brand-text demo text-heading fw-bold">IT OPS</span>
              </a>
            </div>
            <!-- /Logo -->
            <h4 class="mb-2 text-center fw-bold">Welcome to the Future! 🚀</h4>
            <p class="mb-6 text-center text-muted">Join our digital transformation journey</p>

            <form id="formAuthentication" class="mb-6" wire:submit.prevent="registerUser">
              <div class="mb-4">
                <label for="username" class="form-label fw-semibold">Nama</label>
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="bx bx-user"></i></span>
                  <input
                    type="text"
                    wire:model.lazy="name"
                    class="form-control @error('name') is-invalid @enderror"
                    id="username"
                    name="username"
                    placeholder="Masukkan nama lengkap"
                    autofocus />
                </div>
              </div>
              <div class="mb-4">
                <label for="username" class="form-label fw-semibold">NIK</label>
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="bx bx-id-card"></i></span>
                  <input
                    type="text"
                    wire:model.lazy="nik"
                    class="form-control @error('nik') is-invalid @enderror"
                    id="nik"
                    name="nik"
                    placeholder="Masukkan NIK"
                    autofocus />
                    @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>
              <div class="mb-4">
                <label for="username" class="form-label fw-semibold">Departement</label>
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="bx bx-building"></i></span>
                  <select wire:model="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror">
                    <option value="">-- Pilih Departemen --</option>
                    @if($departments)
                      @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                      @endforeach
                    @endif
                  </select>
                  @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
              </div>
              <div class="mb-4">
                <label for="username" class="form-label fw-semibold">Jobsite</label>
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="bx bx-map"></i></span>
                  <select wire:model="jobsite_id" id="jobsite_id" class="form-select @error('jobsite_id') is-invalid @enderror">
                    <option value="">-- Pilih Jobsite --</option>
                    @if($jobsites)
                      @foreach($jobsites as $jobsite)
                        <option value="{{ $jobsite->id }}">{{ $jobsite->code }} - {{ $jobsite->name }}</option>
                      @endforeach
                    @endif
                  </select>
                  @error('jobsite_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
              </div>
              <div class="mb-4">
                <label for="email" class="form-label fw-semibold">Whatsapp</label>
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="bx bxl-whatsapp"></i></span>
                  <input 
                    type="telp" 
                    wire:model.lazy="whatsapp_number"
                    class="form-control @error('whatsapp_number') is-invalid @enderror" 
                    id="email" 
                    name="email" 
                    placeholder="cth: 081234567890" />
                    @error('whatsapp_number')
                      <div class="invalid-feedback">
                          {{ $message }}
                      </div>
                  @enderror
                </div>
              </div>
              <div class="mb-4">
                <label class="form-label fw-semibold" for="password">Password</label>
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
                  <input
                    wire:model.lazy="password" 
                    type="password"
                    id="password"
                    class="form-control @error('password') is-invalid @enderror"
                    name="password"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                    aria-describedby="password" />
                  <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                  @error('password')
                      <div class="invalid-feedback">
                          {{ $message }}
                      </div>
                  @enderror
                </div>
              </div>

              <button type="submit" class="btn btn-primary d-grid w-100 py-3 mt-4 rounded-pill">
                <span wire:loading.remove wire:target="registerUser">
                  <i class="bx bx-user-plus me-2"></i>Daftar Sekarang
                </span>
                <span wire:loading wire:target="registerUser">
                  <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  Memproses...
                </span>
              </button>
            </form>

            <p class="text-center mt-4">
              <span class="text-muted">Sudah punya akun?</span>
              <a href="{{ route('login') }}" class="ms-1 fw-semibold">
                <span>Masuk disini</span>
              </a>
            </p>
          </div>
        </div>
        <!-- Register Card -->
      </div>
    </div>
  </div>

  @push('script')
  <style>
    .animated-background {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 0;
    }

    .circle {
      position: absolute;
      border-radius: 50%;
      background: linear-gradient(45deg, #0073e6, #00b4db);
      opacity: 0.1;
      animation: float 15s infinite ease-in-out;
    }

    .circle-1 {
      width: 300px;
      height: 300px;
      top: -150px;
      left: -150px;
      animation-delay: 0s;
    }

    .circle-2 {
      width: 200px;
      height: 200px;
      top: 50%;
      right: -100px;
      animation-delay: -2s;
    }

    .circle-3 {
      width: 150px;
      height: 150px;
      bottom: -75px;
      left: 10%;
      animation-delay: -4s;
    }

    .circle-4 {
      width: 250px;
      height: 250px;
      top: 20%;
      left: 30%;
      animation-delay: -6s;
    }

    .circle-5 {
      width: 180px;
      height: 180px;
      bottom: 20%;
      right: 20%;
      animation-delay: -8s;
    }

    @keyframes float {
      0% {
        transform: translate(0, 0) rotate(0deg);
      }
      33% {
        transform: translate(30px, -50px) rotate(120deg);
      }
      66% {
        transform: translate(-20px, 20px) rotate(240deg);
      }
      100% {
        transform: translate(0, 0) rotate(360deg);
      }
    }

    .position-relative {
      position: relative;
    }

    .z-1 {
      z-index: 1;
    }
  </style>
  @endpush