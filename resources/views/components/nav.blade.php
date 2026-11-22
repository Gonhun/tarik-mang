@php
    $navUser = Auth::user();
    $avatar = empty($navUser->pp_path) ? asset('assets/img/avatars/1.png') : asset('storage/' . $navUser->pp_path);
@endphp
<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0" href="javascript:void(0)" aria-label="Buka menu">
      <i class="icon-base bx bx-menu icon-md"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center justify-content-between w-100 gap-2" id="navbar-collapse">
    {{-- Judul halaman (dari #[Title] komponen) --}}
    <div class="navbar-page-title text-truncate">
      <span class="fw-semibold text-heading">{{ $pageTitle }}</span>
    </div>

    <ul class="navbar-nav flex-row align-items-center ms-auto gap-1 gap-sm-2">
      <li class="nav-item navbar-jobsite" title="Jobsite aktif">
        <livewire:components.nav.selected-jobsite />
      </li>

      {{-- Notifikasi: hal yang menunggu persetujuan user --}}
      <livewire:components.nav.approval-bell lazy />

      <li class="nav-item navbar-dropdown dropdown-user dropdown">
        <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown" aria-label="Menu profil">
          <div class="avatar avatar-online">
            <img src="{{ $avatar }}" alt="" class="rounded-circle object-fit-cover w-100 h-100" />
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <div class="dropdown-item-text d-flex align-items-center gap-3 py-2">
              <div class="avatar avatar-online flex-shrink-0">
                <img src="{{ $avatar }}" alt="" class="rounded-circle object-fit-cover w-100 h-100" />
              </div>
              <div class="min-w-0">
                <h6 class="mb-0 text-truncate">{{ $navUser->name }}</h6>
                <small class="text-body-secondary d-block text-truncate">{{ $navUser->position->name ?? ($navUser->department->name ?? '') }}</small>
              </div>
            </div>
          </li>
          <li><div class="dropdown-divider my-1"></div></li>
          <li>
            <a class="dropdown-item" href="{{ route('myprofil') }}">
              <i class="icon-base bx bx-user icon-md me-3"></i><span>Profil Saya</span>
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="{{ route('my.asset') }}">
              <i class="icon-base bx bx-laptop icon-md me-3"></i><span>Aset Saya</span>
            </a>
          </li>
          <li><div class="dropdown-divider my-1"></div></li>
          <li>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
              @csrf
              <button type="submit" class="dropdown-item text-danger">
                <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Keluar</span>
              </button>
            </form>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</nav>
