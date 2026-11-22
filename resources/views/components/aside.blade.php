@php
    $user = Auth::user();
    // 'active' bila route sekarang cocok dengan salah satu pola.
    $on = fn (...$patterns) => request()->routeIs(...$patterns) ? 'active' : '';
    $open = fn (...$patterns) => request()->routeIs(...$patterns) ? 'active open' : '';

    $assetMenu = $user->canAny(['Master Asset', 'Mutasi', 'Pemegang Aset', 'QC Aset', 'Peminjaman', 'Kerusakan Aset', 'Stock Opname', 'Cetak Label']);
    $adminUser = $user->canAny(['Add User', 'Add Permissions']);
    $adminJabatan = $user->canAny(['Add Jabatan', 'Manajement IT']);
    $adminKategori = $user->canAny(['Add Type', 'Add Brands', 'Add Series']);
    $adminOrg = $user->canAny(['Add Jobsites', 'Add Dept', 'Add Entitas', 'Add Cost Center']);
    $adminIntegrasi = $user->canAny(['Fortigate', 'Whatsapp']);
    $adminAny = $adminUser || $adminJabatan || $adminKategori || $adminOrg || $adminIntegrasi
        || $user->canAny(['Add Workflow', 'Logs', 'Pengaturan Tampilan']);
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand">
    <a href="{{ route('dashboard') }}" class="app-brand-link gap-2">
      <span class="app-brand-mark"><i class="bx bx-cube-alt"></i></span>
      <span class="app-brand-text menu-text">
        <span class="d-block fw-bold lh-1">AMS</span>
        <small class="d-block text-body-secondary fw-medium">IT Operations</small>
      </span>
    </a>
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto" aria-label="Tutup menu">
      <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    {{-- ===== Utama ===== --}}
    <li class="menu-item {{ $on('dashboard') }}">
      <a href="{{ route('dashboard') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-home-smile"></i>
        <div class="text-truncate">Dashboard</div>
      </a>
    </li>
    <li class="menu-item {{ $on('my.asset') }}">
      <a href="{{ route('my.asset') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-laptop"></i>
        <div class="text-truncate">Aset Saya</div>
      </a>
    </li>

    {{-- ===== Pengajuan ===== --}}
    <li class="menu-header small"><span class="menu-header-text">Pengajuan</span></li>
    @livewire('sidebar.menu-item', [
        'routeName' => 'asset-requests.dashboard',
        'activePattern' => 'asset-requests.*',
        'label' => 'Permintaan Aset',
        'badgeType' => 'awaiting_my_action',
        'icon' => 'bx bx-cart-add',
    ], key('menu-asset-requests'))
    @livewire('sidebar.menu-item', [
        'routeName' => 'requests.license.main',
        'activePattern' => 'requests.license.*',
        'label' => 'Permintaan Lisensi',
        'icon' => 'bx bx-key',
    ], key('menu-license-requests'))

    {{-- ===== Manajemen Aset ===== --}}
    @if ($assetMenu)
      <li class="menu-header small"><span class="menu-header-text">Manajemen Aset</span></li>
      @can('Master Asset')
        <li class="menu-item {{ $on('asset.index', 'assets.show', 'asset.bast', 'asset.pengembalian') }}">
          <a href="{{ route('asset.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-package"></i>
            <div class="text-truncate">Inventaris Aset</div>
          </a>
        </li>
      @endcan
      @can('Pemegang Aset')
        <li class="menu-item {{ $on('asset.holders*') }}">
          <a href="{{ route('asset.holders') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-user-pin"></i>
            <div class="text-truncate">Pemegang Aset</div>
          </a>
        </li>
      @endcan
      @can('QC Aset')
        @livewire('sidebar.menu-item', [
            'routeName' => 'asset.qc',
            'label' => 'QC Aset Baru',
            'badgeType' => 'asset_qc_count',
            'icon' => 'bx bx-check-shield',
        ], key('menu-asset-qc'))
      @endcan
      @can('Mutasi')
        <li class="menu-item {{ $on('asset.mutasi', 'asset.mutasi-status') }}">
          <a href="{{ route('asset.mutasi') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-transfer"></i>
            <div class="text-truncate">Mutasi</div>
          </a>
        </li>
      @endcan
      @can('Peminjaman')
        <li class="menu-item {{ $on('asset.loans') }}">
          <a href="{{ route('asset.loans') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-time-five"></i>
            <div class="text-truncate">Peminjaman</div>
          </a>
        </li>
      @endcan
      @can('Kerusakan Aset')
        <li class="menu-item {{ $on('asset.damage') }}">
          <a href="{{ route('asset.damage') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-wrench"></i>
            <div class="text-truncate">Kerusakan</div>
          </a>
        </li>
      @endcan
      @can('Mutasi')
        <li class="menu-item {{ $on('asset.disposed') }}">
          <a href="{{ route('asset.disposed') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-trash"></i>
            <div class="text-truncate">Disposal</div>
          </a>
        </li>
      @endcan
      @can('Stock Opname')
        <li class="menu-item {{ $on('asset.opname*') }}">
          <a href="{{ route('asset.opname') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-scan"></i>
            <div class="text-truncate">Stock Opname</div>
          </a>
        </li>
      @endcan
      @can('Cetak Label')
        <li class="menu-item {{ $on('asset.labels*') }}">
          <a href="{{ route('asset.labels') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-qr"></i>
            <div class="text-truncate">Cetak Label QR</div>
          </a>
        </li>
      @endcan
      @can('Master Asset')
        <li class="menu-item {{ $on('coa.index', 'master.forecast.pivot', 'forecast.*') }}">
          <a href="{{ route('coa.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
            <div class="text-truncate">Master COA</div>
          </a>
        </li>
      @endcan
    @endif

    {{-- ===== Pengadaan ===== --}}
    @can('Manajement Request')
      <li class="menu-header small"><span class="menu-header-text">Pengadaan</span></li>
      <li class="menu-item {{ $on('request.asset-prsap') }}">
        <a href="{{ route('request.asset-prsap') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-receipt"></i>
          <div class="text-truncate">PR &amp; No. Akuntansi</div>
        </a>
      </li>
      <li class="menu-item {{ $on('request.asset-po') }}">
        <a href="{{ route('request.asset-po') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-purchase-tag"></i>
          <div class="text-truncate">Purchase Order</div>
        </a>
      </li>
      @livewire('sidebar.menu-item', [
          'routeName' => 'request.asset-po-returns',
          'label' => 'Retur QC',
          'badgeType' => 'return_asset_qc_count',
          'icon' => 'bx bx-undo',
      ], key('menu-po-returns'))
    @endcan

    {{-- ===== Lisensi ===== --}}
    @can('Lisensi')
      <li class="menu-header small"><span class="menu-header-text">Lisensi &amp; Software</span></li>
      <li class="menu-item {{ $on('licenses.main', 'licenses.detail') }}">
        <a href="{{ route('licenses.main') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-key"></i>
          <div class="text-truncate">Lisensi</div>
        </a>
      </li>
      <li class="menu-item {{ $on('software.main') }}">
        <a href="{{ route('software.main') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-window-alt"></i>
          <div class="text-truncate">Software</div>
        </a>
      </li>
      <li class="menu-item {{ $open('licenses.category', 'licenses.manufacture') }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-category"></i>
          <div class="text-truncate">Master Lisensi</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item {{ $on('licenses.category') }}">
            <a href="{{ route('licenses.category') }}" class="menu-link"><div class="text-truncate">Kategori</div></a>
          </li>
          <li class="menu-item {{ $on('licenses.manufacture') }}">
            <a href="{{ route('licenses.manufacture') }}" class="menu-link"><div class="text-truncate">Manufaktur</div></a>
          </li>
        </ul>
      </li>
    @endcan

    {{-- ===== Administrasi ===== --}}
    @if ($adminAny)
      <li class="menu-header small"><span class="menu-header-text">Administrasi</span></li>

      @if ($adminUser)
        <li class="menu-item {{ $open('admin.users', 'admin.roles-permissions') }}">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-user"></i>
            <div class="text-truncate">Pengguna</div>
          </a>
          <ul class="menu-sub">
            @can('Add User')
              <li class="menu-item {{ $on('admin.users') }}">
                <a href="{{ route('admin.users') }}" class="menu-link"><div class="text-truncate">Daftar User</div></a>
              </li>
            @endcan
            @can('Add Permissions')
              <li class="menu-item {{ $on('admin.roles-permissions') }}">
                <a href="{{ route('admin.roles-permissions') }}" class="menu-link"><div class="text-truncate">Role &amp; Permission</div></a>
              </li>
            @endcan
          </ul>
        </li>
      @endif

      @if ($adminJabatan)
        <li class="menu-item {{ $open('admin.jabatan*', 'admin.standar-asset') }}">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-id-card"></i>
            <div class="text-truncate">Jabatan</div>
          </a>
          <ul class="menu-sub">
            @can('Add Jabatan')
              <li class="menu-item {{ $on('admin.jabatan') }}">
                <a href="{{ route('admin.jabatan') }}" class="menu-link"><div class="text-truncate">Daftar Jabatan</div></a>
              </li>
              <li class="menu-item {{ $on('admin.jabatan.levels') }}">
                <a href="{{ route('admin.jabatan.levels') }}" class="menu-link"><div class="text-truncate">Level</div></a>
              </li>
            @endcan
            @can('Manajement IT')
              <li class="menu-item {{ $on('admin.standar-asset') }}">
                <a href="{{ route('admin.standar-asset') }}" class="menu-link"><div class="text-truncate">Standar Aset Jabatan</div></a>
              </li>
            @endcan
          </ul>
        </li>
      @endif

      @if ($adminKategori)
        <li class="menu-item {{ $open('admin.asset-type', 'admin.asset-brand', 'admin.asset-series') }}">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-collection"></i>
            <div class="text-truncate">Kategori Aset</div>
          </a>
          <ul class="menu-sub">
            @can('Add Type')
              <li class="menu-item {{ $on('admin.asset-type') }}">
                <a href="{{ route('admin.asset-type') }}" class="menu-link"><div class="text-truncate">Tipe</div></a>
              </li>
            @endcan
            @can('Add Brands')
              <li class="menu-item {{ $on('admin.asset-brand') }}">
                <a href="{{ route('admin.asset-brand') }}" class="menu-link"><div class="text-truncate">Merek</div></a>
              </li>
            @endcan
            @can('Add Series')
              <li class="menu-item {{ $on('admin.asset-series') }}">
                <a href="{{ route('admin.asset-series') }}" class="menu-link"><div class="text-truncate">Seri &amp; Spesifikasi</div></a>
              </li>
            @endcan
          </ul>
        </li>
      @endif

      @if ($adminOrg)
        <li class="menu-item {{ $open('admin.jobsites', 'admin.dept', 'admin.entitas', 'cc.cost-center') }}">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-buildings"></i>
            <div class="text-truncate">Organisasi</div>
          </a>
          <ul class="menu-sub">
            @can('Add Jobsites')
              <li class="menu-item {{ $on('admin.jobsites') }}">
                <a href="{{ route('admin.jobsites') }}" class="menu-link"><div class="text-truncate">Jobsite</div></a>
              </li>
            @endcan
            @can('Add Dept')
              <li class="menu-item {{ $on('admin.dept') }}">
                <a href="{{ route('admin.dept') }}" class="menu-link"><div class="text-truncate">Departemen</div></a>
              </li>
            @endcan
            @can('Add Entitas')
              <li class="menu-item {{ $on('admin.entitas') }}">
                <a href="{{ route('admin.entitas') }}" class="menu-link"><div class="text-truncate">Entitas</div></a>
              </li>
            @endcan
            @can('Add Cost Center')
              <li class="menu-item {{ $on('cc.cost-center') }}">
                <a href="{{ route('cc.cost-center') }}" class="menu-link"><div class="text-truncate">Cost Center</div></a>
              </li>
            @endcan
          </ul>
        </li>
      @endif

      @can('Add Workflow')
        <li class="menu-item {{ $open('admin.workflow*') }}">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-git-branch"></i>
            <div class="text-truncate">Workflow Validasi</div>
          </a>
          <ul class="menu-sub">
            <li class="menu-item {{ $on('admin.workflow', 'admin.workflow.add') }}">
              <a href="{{ route('admin.workflow') }}" class="menu-link"><div class="text-truncate">Aturan Validasi</div></a>
            </li>
            <li class="menu-item {{ $on('admin.workflow.types') }}">
              <a href="{{ route('admin.workflow.types') }}" class="menu-link"><div class="text-truncate">Jenis Transaksi</div></a>
            </li>
          </ul>
        </li>
      @endcan

      @if ($adminIntegrasi)
        <li class="menu-item {{ $open('admin.fortigate*', 'admin.wa') }}">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-plug"></i>
            <div class="text-truncate">Integrasi</div>
          </a>
          <ul class="menu-sub">
            @can('Fortigate')
              <li class="menu-item {{ $on('admin.fortigate*') }}">
                <a href="{{ route('admin.fortigate') }}" class="menu-link"><div class="text-truncate">Fortigate</div></a>
              </li>
            @endcan
            @can('Whatsapp')
              <li class="menu-item {{ $on('admin.wa') }}">
                <a href="{{ route('admin.wa') }}" class="menu-link"><div class="text-truncate">WhatsApp</div></a>
              </li>
            @endcan
          </ul>
        </li>
      @endif

      @can('Pengaturan Tampilan')
        <li class="menu-item {{ $on('admin.login-settings') }}">
          <a href="{{ route('admin.login-settings') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-palette"></i>
            <div class="text-truncate">Tampilan Login</div>
          </a>
        </li>
      @endcan
      @can('Logs')
        <li class="menu-item {{ $on('admin.logs') }}">
          <a href="{{ route('admin.logs') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-history"></i>
            <div class="text-truncate">Audit Log</div>
          </a>
        </li>
      @endcan
    @endif

    {{-- ===== Lainnya ===== --}}
    @if ($user->can('Isr') || $user->isItStaff())
      <li class="menu-header small"><span class="menu-header-text">Lainnya</span></li>
      @can('Isr')
        <li class="menu-item {{ $on('Isr.*') }}">
          <a href="{{ route('Isr.main') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-broadcast"></i>
            <div class="text-truncate">Billing ISR</div>
          </a>
        </li>
      @endcan
      @if ($user->isItStaff())
        <li class="menu-item {{ $on('task.*') }}">
          <a href="{{ route('task.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-task"></i>
            <div class="text-truncate">Task</div>
          </a>
        </li>
      @endif
    @endif
  </ul>
</aside>
