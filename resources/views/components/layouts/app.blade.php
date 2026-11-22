<!doctype html>
@php
    // Judul halaman: dari #[Title] komponen bila ada, kalau tidak dari nama route.
    $routeTitles = [
        'dashboard' => 'Dashboard', 'my.asset' => 'Aset Saya', 'myprofil' => 'Profil Saya',
        'asset-requests.*' => 'Permintaan Aset', 'requests.license.*' => 'Permintaan Lisensi',
        'asset.index' => 'Inventaris Aset', 'assets.show' => 'Detail Aset', 'asset.bast' => 'Serah Terima Aset',
        'asset.pengembalian' => 'Pengembalian Aset', 'asset.qc' => 'QC Aset Baru',
        'asset.mutasi-status' => 'Detail Transaksi', 'asset.mutasi' => 'Mutasi Aset', 'asset.loans' => 'Peminjaman Aset',
        'asset.damage' => 'Kerusakan Aset', 'asset.disposed' => 'Disposal Aset', 'asset.opname*' => 'Stock Opname',
        'asset.labels*' => 'Cetak Label QR', 'coa.index' => 'Master COA', 'forecast.*' => 'Forecast',
        'request.asset-prsap' => 'PR & No. Akuntansi', 'request.asset-po' => 'Purchase Order',
        'request.asset-po-returns' => 'Retur QC', 'licenses.*' => 'Lisensi', 'software.main' => 'Software',
        'admin.users' => 'Daftar User', 'admin.roles-permissions' => 'Role & Permission',
        'admin.jabatan.levels' => 'Level Jabatan', 'admin.jabatan' => 'Jabatan', 'admin.standar-asset' => 'Standar Aset Jabatan',
        'admin.asset-type' => 'Tipe Aset', 'admin.asset-brand' => 'Merek Aset', 'admin.asset-series' => 'Seri & Spesifikasi',
        'admin.jobsites' => 'Jobsite', 'admin.dept' => 'Departemen', 'admin.entitas' => 'Entitas', 'cc.cost-center' => 'Cost Center',
        'admin.workflow.types' => 'Jenis Transaksi', 'admin.workflow*' => 'Workflow Validasi',
        'admin.fortigate*' => 'Fortigate', 'admin.wa' => 'WhatsApp', 'admin.logs' => 'Audit Log',
        'Isr.*' => 'Billing ISR', 'task.*' => 'Task', 'tickets.*' => 'Tiket', 'organization-chart' => 'Struktur Organisasi',
    ];
    $pageTitle = $title ?? collect($routeTitles)->first(fn ($label, $pattern) => request()->routeIs($pattern)) ?? 'AMS IT OPS';
@endphp
<html
  lang="id"
  class="layout-menu-fixed layout-compact"
  data-assets-path="{{ asset('assets') }}/"
  data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $pageTitle === 'AMS IT OPS' ? $pageTitle : $pageTitle . ' · AMS IT OPS' }}</title>
    <meta name="description" content="Asset Management System IT Operations" />

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap"
      rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />

    <!-- Gaya global aplikasi (dimuat terakhir agar bisa merapikan template) -->
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ filemtime(public_path('assets/css/app.css')) }}" />

    <!-- Helpers & config template (wajib di head) -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>

    @livewireStyles
    @stack('styles')
  </head>

  <body>
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        @include('components.aside')

        <div class="layout-page">
          @include('components.nav')

          <div class="content-wrapper">
            {{ $slot }}

            <livewire:chat.widget />

            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl">
                <div class="footer-container d-flex align-items-center justify-content-between py-3 flex-md-row flex-column gap-1">
                  <div class="text-body-secondary small">
                    © {{ date('Y') }} <a href="https://binasaranasukses.co.id" target="_blank" rel="noopener" class="footer-link fw-medium">PT Bina Sarana Sukses</a>
                    · IT Operations
                  </div>
                  <div class="text-body-secondary small d-none d-md-block">AMS IT OPS</div>
                </div>
              </div>
            </footer>

            <div class="content-backdrop fade"></div>
          </div>
        </div>
      </div>

      <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <!-- Core JS: masing-masing cukup dimuat SEKALI.
         Memuat Bootstrap dua kali membuat dropdown/modal terbuka lalu langsung tertutup. -->
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>

    <!-- Vendors JS -->
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apextree@2.3.1/apextree.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>

    <!-- Main JS -->
    <script src="{{ asset('assets/js/main.js') }}"></script>

    @livewireScripts
    @stack('scripts')
    @include('components.SweetAlert.swal')
  </body>
</html>
