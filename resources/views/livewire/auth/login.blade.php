<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">
        <!-- Login Card -->
        <div data-step="{{ $step }}" id="login-step-wrapper" class="card px-sm-6 px-0 glass-morphism animate-card">
          <div class="card-body">
            <!-- Animated Logo -->
            <div class="app-brand justify-content-center mb-4">
              <a href="#" class="app-brand-link gap-2">
                <div class="logo-container">
                  <span class="app-brand-text demo text-heading fw-bold neon-text">IT OPS</span>
                  <div class="logo-glow"></div>
                </div>
              </a>
            </div>
            <!-- Welcome Text -->
            <div class="welcome-section text-center mb-4 animate-fade-in">
              <h4 class="mb-2 gradient-text">Welcome to AMS! 👋</h4>
             
              <div class="rive-canvas-wrapper mx-auto mb-3">
                <canvas id="riveCanvas" width="260" height="260" style="display: block !important; background: transparent !important;"></canvas>
                <div class="rive-canvas-glow"></div>
              </div>
              
              @if ($step == 3)
              <p class="mb-6 text-muted">Buat Password baru</p>
              @else
              {{-- <p class="mb-6 text-muted">Please sign-in to your account and start the adventure</p> --}}
              @endif
            </div>
            
            <form id="formAuthentication" class="mb-6 animate-slide-up" wire:submit.prevent="authenticate">
                @csrf
              
              @if($step == 1)
              <div class="mb-4">
                <label for="nik" class="form-label floating-label">NIK</label>
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="bx bx-user"></i></span>
                  <input
                    wire:model.debounce="nik"
                    type="text"
                    class="form-control modern-input @error('nik') is-invalid @enderror"
                    id="nik"
                    name="nik"
                    placeholder="Enter your NIK"
                    autofocus />
                    @error('nik')
                      <div class="invalid-feedback">
                          {{ $message }}
                      </div>
                  @enderror
                </div>
              
              </div>
              <div class="mb-4 text-center">
                <button type="button" id="lanjutkan" wire:click="cekNik" class="btn btn-primary d-grid w-100 modern-button animate-pulse position-relative overflow-hidden" style="font-size: 1.2rem; letter-spacing: 1px;">
                  <span class="button-text cursor-pointer d-flex align-items-center justify-content-center">
                    <i class="bx bx-log-in-circle me-2" style="font-size: 1.5rem; animation: bounceIn 1s;"></i>
                    <span class="gradient-text" style="font-weight: bold;">Lanjutkan</span>
                    <i class="bx bx-right-arrow-alt button-icon ms-2" style="font-size: 1.3rem; animation: slideRight 1s infinite alternate;"></i>
                  </span>
                  <span class="login-glow"></span>
                </button>
                
              </div>
              @elseif ($step==2)
                <div class="mb-4 form-password-toggle">
                  <label class="form-label floating-label" for="password">Password</label>
                  <div class="input-group input-group-merge">
                    <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
                    <input
                      wire:model="password"
                      type="{{ $showPassword ? 'text' : 'password' }}"
                      id="password"
                      class="form-control modern-input @error('password') is-invalid @enderror"
                      name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password" />
                    <span class="input-group-text cursor-pointer toggle-password" style="cursor: pointer;" wire:click="togglePassword">
                      <i id="togglePassword" class="bx {{ $showPassword ? 'bx-show' : 'bx-hide' }}"></i>
                    </span>
                      @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                  </div>
                  
                </div>
                <div class="mb-4">
                  <div class="d-flex justify-content-between align-items-center">
                    <div class="form-check">
                      <input class="form-check-input custom-checkbox" wire:model="remember" type="checkbox" id="remember-me" />
                      <label class="form-check-label" for="remember-me">Remember Me</label>
                    </div>
                  </div>
                </div>
                <div class="mb-4 text-center">
                  <button type="submit" class="btn btn-primary d-grid w-100 modern-button animate-pulse position-relative overflow-hidden" style="font-size: 1.2rem; letter-spacing: 1px;">
                    <span class="button-text cursor-pointer d-flex align-items-center justify-content-center">
                      <i class="bx bx-log-in-circle me-2" style="font-size: 1.5rem; animation: bounceIn 1s;"></i>
                      <span class="gradient-text" style="font-weight: bold;">Masuk ke AMS</span>
                      <i class="bx bx-right-arrow-alt button-icon ms-2" style="font-size: 1.3rem; animation: slideRight 1s infinite alternate;"></i>
                    </span>
                    <span class="login-glow"></span>
                  </button>
                  <div class="mt-2">
                    <small class="text-muted animate-fade-in">Selamat datang kembali! Silakan login untuk melanjutkan.</small>
                  </div>
                </div>
                @elseif ($step == 3)
                <div class="mb-4 form-password-toggle">
                  <label class="form-label floating-label" for="new-password">Password</label>
                  <div class="input-group input-group-merge">
                    <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
                    <input
                      wire:model="new_password"
                      type="{{ $showPassword ? 'text' : 'password' }}"
                      id="passwordInput"
                      class="form-control modern-input @error('new_password') is-invalid @enderror"
                      name="new_password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="new-password" />
                    <span class="input-group-text cursor-pointer toggle-password" style="cursor: pointer;" wire:click="togglePassword">
                      <i class="bx {{ $showPassword ? 'bx-show' : 'bx-hide' }}"></i>
                    </span>
                    @error('new_password')
                      <div class="invalid-feedback">
                          {{ $message }}
                      </div>
                    @enderror
                  </div>
                </div>
                <div class="mb-4 form-password-toggle">
                  <label class="form-label floating-label" for="passwordInput">Confirm Password</label>
                  <div class="input-group input-group-merge">
                    <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
                    <input
                      wire:model="confirm_password"
                      type="{{ $showPassword ? 'text' : 'password' }}"
                      id="passwordInput"
                      class="form-control modern-input @error('confirm_password') is-invalid @enderror"
                      name="confirm_password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="passwordInput" />
                    <span class="input-group-text cursor-pointer toggle-password" style="cursor: pointer;" wire:click="togglePassword">
                      <i class="bx {{ $showPassword ? 'bx-show' : 'bx-hide' }}"></i>
                    </span>
                    @error('confirm_password')
                      <div class="invalid-feedback">
                          {{ $message }}
                      </div>
                    @enderror
                  </div>
                </div>
                <button wire:click="createdPassword" type="button" class="btn btn-primary w-100 mt-3">
                  Simpan & Login
                </button>
              @endif
              <style>
                @keyframes bounceIn {
                  0% { transform: scale(0.7); opacity: 0.5; }
                  60% { transform: scale(1.2); opacity: 1; }
                  100% { transform: scale(1); }
                }
                @keyframes slideRight {
                  0% { transform: translateX(0); }
                  100% { transform: translateX(8px); }
                }
                .login-glow {
                  position: absolute;
                  left: 0; top: 0; width: 100%; height: 100%;
                  pointer-events: none;
                  background: radial-gradient(circle at 50% 80%, rgba(0,198,255,0.15) 0%, rgba(0,115,230,0.05) 80%, transparent 100%);
                  z-index: 0;
                  animation: glowPulse 2s infinite alternate;
                }
                @keyframes glowPulse {
                  0% { opacity: 0.7; }
                  100% { opacity: 1; }
                }
                .rive-canvas-wrapper {
                  position: relative;
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  width: 260px;
                  height: 260px;
                  margin: 0 auto 1.5rem auto;
                  border-radius: 50%;
                
                  overflow: visible;
                  z-index: 1;
                  transition: box-shadow 0.3s;
                  animation: rivePopIn 1.2s cubic-bezier(.68,-0.55,.27,1.55);
                }
                
                
                @keyframes riveGlowPulse {
                  0% { opacity: 0.7; }
                  100% { opacity: 1; }
                }
                @keyframes rivePopIn {
                  0% { transform: scale(0.7); opacity: 0.2; }
                  60% { transform: scale(1.1); opacity: 1; }
                  100% { transform: scale(1); }
                }
                #rive-canvas {
                  position: relative;
                  z-index: 2;
                  border-radius: 50%;
                  background: transparent;
                  box-shadow: 0 0 0 0 transparent;
                  transition: filter 0.2s;
                  filter: drop-shadow(0 0 8px rgba(0,198,255,0.12));
                }
              </style>
            </form>

            {{-- <p class="text-center register-link animate-fade-in">
              <span>New on our platform?</span>
              <a href="{{ route('register') }}" class="gradient-link">
                <span>Create an account</span>
              </a>
            </p> --}}
          </div>
        </div>
      </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
  const canvas = document.getElementById('riveCanvas');
  let handsUpInput;

  const riveInstance = new rive.Rive({
    src: "/assets/riv/login_screen_character.riv", //
    canvas: canvas,
    autoplay: true,
    stateMachines: ["State Machine 1"],
    onLoad: () => {
      const inputs = riveInstance.stateMachineInputs("State Machine 1");
      handsUpInput = inputs.find(i => i.name === "hands_up");
    }
  });

  window.addEventListener('step-updated', (event) => {
    console.log(event);
    const currentStep = event.detail[0];

    console.log('Step now:', currentStep.step);

    // Jika di step password, aktifkan hands_up
    if (handsUpInput) {
      handsUpInput.value = (currentStep.step === 2 || currentStep.step === 3);
    }
   
  });

  // Optional: kontrol saat input password difokuskan
  document.addEventListener("focusin", (e) => {
    if (e.target.id === "password" && handsUpInput) {
      handsUpInput.value = true;
    }
  });

  document.addEventListener("focusout", (e) => {
    if (e.target.id === "password" && handsUpInput) {
      handsUpInput.value = false;
    }
  });
});
</script>
@endpush
