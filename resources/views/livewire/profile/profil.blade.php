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
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('organization-chart') }}" target="_blank"
                      ><i class="icon-base bx bx-bxs-group icon-sm me-1_5"></i> Struktur Organisasi</a
                    >
                  </li>
                <li class="nav-item">
                  <a class="nav-link {{ $tab == 2 ? 'active' : '' }}" wire:click="$set('tab', 2)"
                    ><i class="icon-base bx bx-link-alt icon-sm me-1_5"></i> Asset Usage</a
                  >
                </li>
                <li class="nav-item">
                  <a class="nav-link {{ $tab == 3 ? 'active' : '' }}" wire:click="$set('tab', 3)" href="#"
                    ><i class="icon-base bx bx-bell icon-sm me-1_5"></i> Logging</a
                  >
                </li>
                
              </ul>
            </div>
           @if ($tab == 0)
                @include('livewire.profile.account')
            @elseif ($tab == 1)
               
            @elseif ($tab == 2)
                @include('livewire.profile.asset-usage')
            @elseif ($tab == 3)
                @include('livewire.profile.logging')
           @endif
          </div>
        </div>
      </div>

      <!-- / Content -->
  