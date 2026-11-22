<!doctype html>

<html
  lang="en"
  class="layout-wide customizer-hide"
  data-assets-path="../assets/"
  data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>AMS System</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet" />
    
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

    <!-- endbuild -->

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />

    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
      <script src="
      https://cdn.jsdelivr.net/npm/@rive-app/canvas@2.30.4/rive.min.js
      "></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @livewireStyles
  </head>

  <body>
    <!-- Content -->

    {{ $slot }}

    <!-- / Content -->

    
    <!-- Core JS -->

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>

    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>

    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="../assets/vendor/js/menu.js"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->

    <!-- Main JS -->

    <script src="../assets/js/main.js"></script>

    <!-- Page JS -->

    <!-- Place this tag before closing body tag for github widget button. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
   
  <script>
      document.addEventListener('livewire:load', function () {
          const toastEl = document.getElementById('liveToast');
          const toastTitleEl = document.getElementById('toastTitle');
          const toastMessageEl = document.getElementById('toastMessage');
          const toastTimeEl = document.getElementById('toastTime'); // Opsional untuk waktu
          console.log('aaaaa');
          if (toastEl) {
              // Inisialisasi instance Toast Bootstrap sekali saja
              const liveToast = new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
              
              window.addEventListener('show-toast', event => {
                
                  let type = event.detail.type || 'info';
                  let message = event.detail.message || 'Tidak ada pesan.';
                  let title = 'Pemberitahuan';
  
                  // Hapus class warna sebelumnya
                  toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white');
                  toastTitleEl.classList.remove('text-white'); // Reset warna teks title
  
                  switch (type) {
                      case 'success':
                          title = 'Sukses!';
                          toastEl.classList.add('bg-success', 'text-white');
                          toastTitleEl.classList.add('text-white');
                          break;
                      case 'error':
                          title = 'Error!';
                          toastEl.classList.add('bg-danger', 'text-white');
                          toastTitleEl.classList.add('text-white');
                          break;
                      case 'warning':
                          title = 'Peringatan!';
                          toastEl.classList.add('bg-warning', 'text-dark'); // Mungkin butuh teks gelap
                          break;
                      case 'info':
                      default:
                          title = 'Informasi';
                          toastEl.classList.add('bg-info', 'text-dark'); // Mungkin butuh teks gelap
                          break;
                  }
  
                  if (toastTitleEl) toastTitleEl.innerText = title;
                  if (toastMessageEl) toastMessageEl.innerText = message;
                  if (toastTimeEl) toastTimeEl.innerText = 'Baru saja'; // Atau format waktu
  
                  liveToast.show();
              });
          }
      });
      // Jika Anda juga menggunakan session flash untuk toast (alternatif atau tambahan)
      // Anda bisa menambahkan kode ini untuk menampilkan toast dari session flash:
      @if (session()->has('toast-success'))
          document.addEventListener('DOMContentLoaded', function () {
              window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: "{{ session('toast-success') }}" } }));
          });
      @endif
      @if (session()->has('toast-error'))
           document.addEventListener('DOMContentLoaded', function () {
              window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: "{{ session('toast-error') }}" } }));
          });
      @endif
      // Dan seterusnya untuk tipe lain (info, warning)
  </script>
    
    
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @livewireScripts
    @stack('scripts')
    @stack('styles')
    @include('components.SweetAlert.swal')
   
  </body>
</html>
