<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
      <a href="#" class="app-brand-link">
        <span class="app-brand-logo demo">
          <span class="text-primary">
            <svg
              width="25"
              viewBox="0 0 25 42"
              version="1.1"
              xmlns="http://www.w3.org/2000/svg"
              xmlns:xlink="http://www.w3.org/1999/xlink">
              <defs>
                <path
                  d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z"
                  id="path-1"></path>
                <path
                  d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z"
                  id="path-3"></path>
                <path
                  d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z"
                  id="path-4"></path>
                <path
                  d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z"
                  id="path-5"></path>
              </defs>
              <g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                  <g id="Icon" transform="translate(27.000000, 15.000000)">
                    <g id="Mask" transform="translate(0.000000, 8.000000)">
                      <mask id="mask-2" fill="white">
                        <use xlink:href="#path-1"></use>
                      </mask>
                      <use fill="currentColor" xlink:href="#path-1"></use>
                      <g id="Path-3" mask="url(#mask-2)">
                        <use fill="currentColor" xlink:href="#path-3"></use>
                        <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3"></use>
                      </g>
                      <g id="Path-4" mask="url(#mask-2)">
                        <use fill="currentColor" xlink:href="#path-4"></use>
                        <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4"></use>
                      </g>
                    </g>
                    <g
                      id="Triangle"
                      transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) ">
                      <use fill="currentColor" xlink:href="#path-5"></use>
                      <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use>
                    </g>
                  </g>
                </g>
              </g>
            </svg>
          </span>
        </span>
        <span class="app-brand-text demo menu-text fw-bold ms-2">AMS | IT OPS</span>
      </a>

      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
        <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
      </a>
    </div>

    <div class="menu-divider mt-0"></div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
      <li class="menu-item {{ request()->routeIs('dashboard') ? 'active open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-home-smile"></i>
          <div class="text-truncate" data-i18n="Dashboards">Dashboards</div>
          {{-- <span class="badge rounded-pill bg-danger ms-auto">5</span> --}}
        </a>
        <ul class="menu-sub">
          <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Analytics">Analytics</div>
            </a>
          </li>
          {{-- <li class="menu-item">
            <a
              href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/app-academy-dashboard.html"
              target="_blank"
              class="menu-link">
              <div class="text-truncate" data-i18n="Academy">Academy</div>
              <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
            </a>
          </li> --}}
        </ul>
      </li>
      <li class="menu-item {{ request()->routeIs('my.asset') ? 'active' : '' }}">
        <a
          href="{{ route('my.asset') }}"
          class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-file-md"></i>
          <div class="text-truncate" data-i18n="Documentation">Asset Saya</div>
        </a>
      </li>
      <!-- Administrator -->
      @canany(['Manajement IT','Add User'])
      <li class="menu-item {{ request()->routeIs('admin.*') ? 'active open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-bxs-user"></i>
          <div class="text-truncate" data-i18n="Dashboards">Administrator</div>
          {{-- <span class="badge rounded-pill bg-danger ms-auto">5</span> --}}
        </a>
        <ul class="menu-sub">
          <li class="menu-item {{ request()->routeIs('admin.users','admin.roles-permissions') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
              <div class="text-truncate">Manajement User</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                <a href="{{ route('admin.users')}}" class="menu-link">
                  <div class="text-truncate" data-i18n="Analytics">Users</div>
                </a>
              </li>
              @can('Manajement IT')
              <li class="menu-item {{ request()->routeIs('admin.roles-permissions') ? 'active' : '' }}">
                <a href="{{ route('admin.roles-permissions')}}" class="menu-link">
                  <div class="text-truncate" data-i18n="Analytics">Permissions</div>
                </a>
              </li>
              @endcan
            </ul>
          </li>
            <li class="menu-item {{ request()->routeIs('admin.jabatan','admin.standar-asset') ? 'active open' : '' }}">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <div class="text-truncate">Manajement Jabatan</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.jabatan') ? 'active' : '' }}">
                  <a href="{{ route('admin.jabatan')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="Analytics">Jabatan</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.jabatan.levels') ? 'active' : '' }}">
                  <a href="{{ route('admin.jabatan.levels')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="Analytics">Level</div>
                  </a>
                </li>
                @can('Manajement IT')
                <li class="menu-item {{ request()->routeIs('admin.standar-asset') ? 'active' : '' }}">
                  <a href="{{ route('admin.standar-asset') }}" class="menu-link">
                    <div class="text-truncate">Asset Jabatan</div>
                  </a>
                </li>
                @endcan
              </ul>
            </li>
            @can('Manajement IT')
            <li class="menu-item {{ request()->routeIs('admin.asset-type', 'admin.asset-brand', 'admin.asset-series') ? 'active open' : '' }}">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <div class="text-truncate">Kategori Asset</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.asset-type') ? 'active' : '' }}">
                  <a href="{{ route('admin.asset-type') }}" class="menu-link">
                    <div class="text-truncate">Type Asset</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.asset-brand') ? 'active' : '' }}">
                  <a href="{{ route('admin.asset-brand') }}" class="menu-link">
                    <div class="text-truncate">Merek Asset</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.asset-series') ? 'active' : '' }}">
                  <a href="{{ route('admin.asset-series') }}" class="menu-link">
                    <div class="text-truncate">Series & Spec Asset</div>
                  </a>
                </li>
              </ul>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.jobsites') ? 'active' : '' }}">
              <a href="{{ route('admin.jobsites')}}" class="menu-link">
                <div class="text-truncate" data-i18n="Analytics">Jobsites</div>
              </a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.dept') ? 'active' : '' }}">
              <a href="{{ route('admin.dept')}}" class="menu-link">
                <div class="text-truncate" data-i18n="Analytics">Departements</div>
              </a>
            </li>
            
            <li class="menu-item {{ request()->routeIs('admin.entitas') ? 'active' : '' }}">
              <a href="{{ route('admin.entitas')}}" class="menu-link">
                <div class="text-truncate" data-i18n="Analytics">Entitas</div>
              </a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
              <a href="{{ route('admin.logs')}}" class="menu-link">
                <div class="text-truncate" data-i18n="audits">Audits</div>
              </a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.workflow') ? 'active' : '' }}">
              <a href="{{ route('admin.workflow')}}" class="menu-link">
                <div class="text-truncate" data-i18n="audits">Work Flow Validasi</div>
              </a>
            </li>
          @endcan
          {{-- <li class="menu-item">
            <a
              href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/app-academy-dashboard.html"
              target="_blank"
              class="menu-link">
              <div class="text-truncate" data-i18n="Academy">Academy</div>
              <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
            </a>
          </li> --}}
        </ul>
      </li>
      @endcan
      
      
      @can('Manajement CC')
      <li class="menu-item {{ request()->routeIs('cc.*') ? 'active open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-bxs-user"></i>
          <div class="text-truncate" data-i18n="Dashboards">Data Center (CC)</div>
          {{-- <span class="badge rounded-pill bg-danger ms-auto">5</span> --}}
        </a>
        <ul class="menu-sub">
          <li class="menu-item {{ request()->routeIs('cc.cost-center') ? 'active' : '' }}">
            <a href="{{ route('cc.cost-center')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Analytics">Cost Center</div>
            </a>
          </li>
          {{-- <li class="menu-item">
            <a
              href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/app-academy-dashboard.html"
              target="_blank"
              class="menu-link">
              <div class="text-truncate" data-i18n="Academy">Academy</div>
              <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
            </a>
          </li> --}}
        </ul>
      </li>
      @endcan
      <!-- Dashboards -->
      
      <!-- Master Asset -->
      @canany(['Master Asset','Mutasi'])
      <li class="menu-item {{ request()->routeIs('asset.*') ? 'active open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-database"></i>
          <div class="text-truncate" data-i18n="Layouts">Master Asset</div>
        </a>

        <ul class="menu-sub">
          
          @can('Master Asset')
          <li class="menu-item {{ request()->routeIs('asset.index') ? 'active' : '' }}">
            <a href="{{ route('asset.index')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Inventarisir</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('coa.index') ? 'active' : '' }}">
            <a href="{{ route('coa.index')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Master COA</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('asset.damage') ? 'active' : '' }}">
            <a href="{{ route('asset.damage')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Damage</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('asset.loans') ? 'active' : '' }}">
            <a href="{{ route('asset.loans')}}" class="menu-link">
              <div class="text-truncate">Peminjaman</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('asset.opname*') ? 'active' : '' }}">
            <a href="{{ route('asset.opname')}}" class="menu-link">
              <div class="text-truncate">Stock Opname</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('asset.labels') ? 'active' : '' }}">
            <a href="{{ route('asset.labels')}}" class="menu-link">
              <div class="text-truncate">Cetak Label QR</div>
            </a>
          </li>
          @livewire('sidebar.menu-item', [
                    'routeName' => 'asset.qc',
                    'label' => 'QC Aset', 
                    'badgeType' => 'asset_qc_count'
                ])
          @endcan
          
          @can('Mutasi')
          <li class="menu-item {{ request()->routeIs('asset.mutasi.*') ? 'active' : '' }}">
            <a href="{{ route('asset.mutasi')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Mutasi</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('asset.disposed') ? 'active' : '' }}">
            <a href="{{ route('asset.disposed')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Disposed</div>
            </a>
          </li>
          @endcan
        
          {{-- Master User --}}
          
          
        </ul>
      </li>
      @endcanany
      @can('Lisensi')
      <li class="menu-item {{ request()->routeIs('licenses.*') ? 'active open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bxs-category"></i>
          <div class="text-truncate" data-i18n="Layouts">Master Licenses</div>
        </a>

        <ul class="menu-sub">
          <li class="menu-item {{ request()->routeIs('licenses.category') ? 'active' : '' }}">
            <a href="{{ route('licenses.category')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Category</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('licenses.manufacture') ? 'active' : '' }}">
            <a href="{{ route('licenses.manufacture')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Manufacture</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('software.main') ? 'active' : '' }}">
            <a href="{{ route('software.main')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Software</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('licenses.main') ? 'active' : '' }}">
            <a href="{{ route('licenses.main')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Licensi</div>
            </a>
          </li>
        </ul>
      </li>
      @endcan
      @can('Manajement Request')
         <!-- request asset -->
      <li class="menu-item {{ request()->routeIs('request.*') ? 'active open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-add-to-queue"></i>
          <div class="text-truncate" data-i18n="Layouts">Manajemen Permintaan</div>
        </a>

        <ul class="menu-sub">
          <li class="menu-item {{ request()->routeIs('request.*') ? 'active open' : '' }}">
          </li>
          <li class="menu-item {{ request()->routeIs('request.asset-prsap') ? 'active' : '' }}">
            <a href="{{ route('request.asset-prsap')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">PR & No Accounting</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('request.asset-po') ? 'active' : '' }}">
            <a href="{{ route('request.asset-po')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Purchase Order</div>
            </a>
          </li>
          @livewire('sidebar.menu-item', [
                    'routeName' => 'request.asset-po-returns',
                    'label' => 'Return QC Asset', 
                    'badgeType' => 'return_asset_qc_count',
                    // 'icon' => 'bx bx-arrow-back'
                ])
          {{-- Master User --}}
          
          
        </ul>
      </li>
      @endcan
      @can('Isr')
        <li class="menu-item {{ request()->routeIs('isr.*') ? 'active' : '' }}">
          <a
            href="{{ route('Isr.main') }}"
            class="menu-link">
            <i class="menu-icon tf-icons bx bxs-radio"></i>
            <div class="text-truncate" data-i18n="Documentation">Billing ISR</div>
          </a>
        </li> 
      @endcan
      
     
      @livewire('sidebar.menu-item', [
                    'routeName' => 'asset-requests.dashboard',
                    'label' => 'Request Asset', 
                    'badgeType' => 'awaiting_my_action',
                    'icon' => 'bx bx-file'
                ])
                @livewire('sidebar.menu-item', [
                  'routeName' => 'requests.license.main',
                  'label' => 'Request Licenses', 
                  'badgeType' => 'awaiting_my_action',
                  'icon' => 'bx bx-file'
              ])
      {{-- <li class="menu-item {{ request()->routeIs('asset-requests.*') ? 'active' : '' }}">
        <a
          href="{{ route('asset-requests.dashboard') }}"
          class="menu-link">
          <i class="menu-icon tf-icons bx bx-file"></i>
          <div class="text-truncate" data-i18n="Documentation">Request Asset</div>
        </a>
      </li> --}}
      @can('Master Licensi')
      {{-- <li class="menu-item {{ request()->routeIs('bast.*') ? 'active open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-file-blank"></i>
          <div class="text-truncate" data-i18n="Layouts">Berita Acara</div>
        </a>

        <ul class="menu-sub">
          <li class="menu-item {{ request()->routeIs('bast.index') ? 'active' : '' }}">
            <a href="{{ route('bast.index')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Serah Terima/Pengembailan</div>
            </a>
          </li>
          
          <li class="menu-item {{ request()->routeIs('bast.damage') ? 'active' : '' }}">
            <a href="{{ route('bast.damage')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Without navbar">Kerusakan/Maintenance</div>
            </a>
          </li>
        </ul>
      </li> --}}
      @endcan
      @if(Auth::user()->department_id === '8')
      <li class="menu-item {{ request()->routeIs('task.*') ? 'active' : '' }}">
        <a
          href="{{ route('task.index') }}"
          class="menu-link">
          <i class="menu-icon tf-icons bx bx-task-x"></i>
          <div class="text-truncate" data-i18n="Documentation">Taks</div>
        </a>
      </li> 
      @endif
      <!-- Layouts -->
      
      {{-- <a class="menu-link">Coming Soon</a>
      <!-- Download Mobile-->
      <li class="menu-item ">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-bxs-download"></i>
          <div class="text-truncate" data-i18n="Layouts">Download Mobile</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item">
            <a href="#" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Andorid</div>
            </a>
          </li>
          <li class="menu-item ">
            <a href="#" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">IOS</div>
            </a>
          </li>
        </ul>
      </li>
      <!-- Download Dekstop-->
      <li class="menu-item ">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-bxs-download"></i>
          <div class="text-truncate" data-i18n="Layouts">Download Desktop</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item">
            <a href="#" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Windows</div>
            </a>
          </li>
          <li class="menu-item ">
            <a href="#" class="menu-link">
              <div class="text-truncate" data-i18n="Without menu">Macbook</div>
            </a>
          </li>
          <li class="menu-item ">
            <a href="#" class="menu-link">
              <div class="text-truncate" data-i18n="Without navbar">Linux</div>
            </a>
          </li>
        </ul>
      </li> --}}
      
      {{-- <li class="menu-item">
        <a
          href="#"
          target="_blank"
          class="menu-link">
          <i class="menu-icon tf-icons bx bx-file"></i>
          <div class="text-truncate" data-i18n="Documentation">Documentation</div>
        </a>
      </li> --}}
    </ul>
  </aside>