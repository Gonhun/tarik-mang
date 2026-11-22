<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ $title ?? 'Default Title' }}</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="{{ asset('tickets/assets/vendors/feather/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('tickets/assets/vendors/ti-icons/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('tickets/assets/vendors/css/vendor.bundle.base.css') }}">
    <link rel="stylesheet" href="{{ asset('tickets/assets/vendors/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('tickets/assets/vendors/mdi/css/materialdesignicons.min.css') }}">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- <link rel="stylesheet" href="assets/vendors/datatables.net-bs4/dataTables.bootstrap4.css"> -->
    {{-- <link rel="stylesheet" href="{{ asset('tickets/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}"> --}}
    <link rel="stylesheet" href="{{ asset('tickets/assets/vendors/ti-icons/css/themify-icons.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('tickets/assets/js/select.dataTables.min.css') }}">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="{{ asset('tickets/assets/css/style.css') }}">
    <!-- endinject -->
    <link rel="shortcut icon" href="{{ asset('tickets/assets/images/favicon.png') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/css/bootstrap-select.min.css">
  </head>
  <body>
    <div class="container-scroller">
      
      <!-- partial:partials/_navbar.html -->
      @if(Route::is('tickets.under-developer'))
      @else
      @include('components.layouts.tickets.nav')
      @endif
      
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        @if(Route::is('tickets.under-developer'))
        @else
        @include('components.layouts.tickets.sidebar')
        @endif
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              {{ $slot }}
            </div>
          </div>
          
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
          @include('components.layouts.tickets.footer')
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="{{ asset('tickets/assets/vendors/js/vendor.bundle.base.js') }}"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="{{ asset('tickets/assets/vendors/chart.js/chart.umd.js') }}"></script>
    {{-- <script src="{{  asset('tickets/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
    <!-- <script src="assets/vendors/datatables.net-bs4/dataTables.bootstrap4.js"></script> -->
    <script src="{{ asset('tickets/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script> --}}
    <script src="{{ asset('tickets/assets/js/dataTables.select.min.js') }}"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="{{ asset('tickets/assets/js/off-canvas.js') }}"></script>
    <script src="{{ asset('tickets/assets/js/template.js') }}"></script>
    <script src="{{ asset('tickets/assets/js/settings.js') }}"></script>
    <script src="{{ asset('tickets/assets/js/todolist.js') }}"></script>
    <!-- endinject -->
    <!-- Custom js for this page-->
    <script src="{{ asset('tickets/assets/js/jquery.cookie.js') }}" type="text/javascript"></script>
    <script src="{{ asset('tickets/assets/js/dashboard.js') }}"></script>
    <!-- <script src="assets/js/Chart.roundedBarCharts.js"></script> -->
    <!-- End custom js for this page-->
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/js/bootstrap-select.min.js"></script>
    @livewireScripts
    @stack('scripts')
    @stack('styles')
  </body>
</html>