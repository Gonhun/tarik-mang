<div>
    
    <div class="container-xxl flex-grow-1 container-p-y">
      
        <div class="row">
          <div class="col-xxl-8 mb-6 order-0">
            <div class="card">
              
              <div class="d-flex align-items-start row">
                <div class="col-sm-7">
                  <div class="card-body">
                    <h5 class="card-title text-primary mb-3">Selamat Datang {{Auth::user()->name}}! 🎉</h5>
                    <p class="mb-6">
                      {{-- You have done 72% more sales today.<br />Check your new badge in your profile. --}}
                    </p>
    
                    {{-- <a href="javascript:;" class="btn btn-sm btn-outline-primary">View Badges</a> --}}
                  </div>
                
                </div>
                <div class="col-sm-5 text-center text-sm-left">
                  <div class="card-body pb-0 px-0 px-md-6">
                    <canvas id="rive-animation-canvas" class="rive-bg-canvas" height="175"></canvas>
                    {{-- <img
                      src="{{ asset('assets/img/illustrations/man-with-laptop.png') }}"
                      height="175"
                      alt="View Badge User" /> --}}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xxl-4 col-lg-12 col-md-4 order-1">
            <div class="row">
              <div class="col-lg-6 col-md-12 col-6 mb-6">
                <div class="card h-100">
                  <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between mb-4">
                      <div class="avatar flex-shrink-0">
                        <img
                          src="{{ asset('assets/img/icons/unicons/chart-success.png') }}"
                          alt="chart success"
                          class="rounded" />
                      </div>
                      <div class="dropdown">
                        <button
                          class="btn p-0"
                          type="button"
                          id="cardOpt3"
                          data-bs-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false">
                          <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                          <a class="dropdown-item" href="javascript:void(0);">View More</a>
                          <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                        </div>
                      </div>
                    </div>
                    <p class="mb-1">Total Pengajuan {{in_array(Auth::user()->department->code, ['IT', 'DIR']) ? '': 'Saya'}}</p>
                    <h4 class="card-title mb-3">{{$totalPengajuan}}</h4>
                    {{-- <small class="text-success fw-medium"
                      ><i class="icon-base bx bx-up-arrow-alt"></i> +72.80%</small
                    > --}}
                  </div>
                </div>
              </div>
              <div class="col-lg-6 col-md-12 col-6 mb-6">
                <div class="card h-100">
                  <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between mb-4">
                      <div class="avatar flex-shrink-0">
                        <img
                          src="{{ asset('assets/img/icons/unicons/wallet-info.png') }}"
                          alt="wallet info"
                          class="rounded" />
                      </div>
                      <div class="dropdown">
                        <button
                          class="btn p-0"
                          type="button"
                          id="cardOpt6"
                          data-bs-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false">
                          <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                          <a class="dropdown-item" href="javascript:void(0);">View More</a>
                          <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                        </div>
                      </div>
                    </div>
                    <p class="mb-1">{{in_array(Auth::user()->department->code, ['IT', 'DIR']) ? 'Total BAST Pending': 'BAST Pending Saya'}}</p>
                    <h4 class="card-title mb-3">0</h4>
                    {{-- <small class="text-success fw-medium"
                      ><i class="icon-base bx bx-up-arrow-alt"></i> +28.42%</small
                    > --}}
                  </div>
                </div>
              </div>
              
            </div>
            
          </div>
          
          
        </div>
        @if (in_array(Auth::user()->department->code, ['IT', 'DIR']))
            <livewire:dashboard />
          @endif
        <div class="row">
          <!-- Order Statistics -->
          <div class="col-md-6 col-lg-4 col-xl-4 order-0 mb-6">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between">
                <div class="card-title mb-0">
                  <h5 class="mb-1 me-2">Daftar Asset</h5>
                </div>
                <div class="dropdown">
                  <button
                    class="btn text-body-secondary p-0"
                    type="button"
                    id="orederStatistics"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false">
                    <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="orederStatistics">
                    <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                    <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                    <a class="dropdown-item" href="javascript:void(0);">Share</a>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-6">
                  <div class="d-flex flex-column align-items-center gap-1">
                    <h3 class="mb-1">{{ number_format($totalAsset, 0, ',', '.') }}</h3>
                    <small>Total Asset</small>
                  </div>
                  <div id="orderStatisticsChart1"></div>
                </div>
                <ul class="p-0 m-0">
                    @if(!empty($totalAssetByType))
                        @foreach ($totalAssetByType as $type)
                        <li class="d-flex align-items-center mb-5">
                            <div class="avatar flex-shrink-0 me-3">
                              <span class="avatar-initial rounded bg-label-primary"
                                ><i class="icon-base bx {{$type->assetType->name == 'Laptop' ? 'bx-laptop' : 'bx-mobile-alt'}}"></i
                              ></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                              <div class="me-2">
                                <h6 class="mb-0">{{ $type->assetType->name }}</h6>
                                {{-- <small>Mobile, Earbuds, TV</small> --}}
                              </div>
                              <div class="user-progress">
                                <h6 class="mb-0">{{ number_format($type->total) }}</h6>
                              </div>
                            </div>
                          </li>
                        @endforeach
                    @endif  
                </ul>
              </div>
            </div>
          </div>
          <!--/ Order Statistics -->
    
          <!-- Expense Overview -->
          <div class="col-md-6 col-lg-4 order-1 mb-6">
            <div class="card h-100">
              <div class="card-header nav-align-top">
                <ul class="nav nav-pills flex-wrap row-gap-2" role="tablist">
                  <li class="nav-item">
                    <button
                      wire:click="$set('tab', 0)"
                      type="button"
                      class="nav-link {{$tab == 0 ? 'active':''}}"
                      role="tab"
                      data-bs-toggle="tab"
                      data-bs-target="#navs-tabs-line-card-income"
                      aria-controls="navs-tabs-line-card-income"
                      aria-selected="{{$tab == 0 ? 'true':'false'}}">
                      Support
                    </button>
                  </li>
                  {{-- <li class="nav-item">
                    <button 
                    type="button" 
                    class="nav-link" 
                    role="tab"
                    data-bs-target="#navs-tabs-line-card-income1"
                    aria-controls="navs-tabs-line-card-income1">Expenses</button>
                  </li>
                  <li class="nav-item">
                    <button type="button" class="nav-link" role="tab">Profit</button>
                  </li> --}}
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content p-0">
                  <div class="tab-pane fade show active" id="navs-tabs-line-card-income" role="tabpanel">
                    @if (session()->has('wa_sukses'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('wa_sukses') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                           @elseif(session()->has('wa_error'))
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    {{ session('wa_error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                           @endif
                    <div class="d-flex mb-6">
                      
                      <div>
                        {{-- <p class="mb-0">Perlu Support?</p> --}}
                        <div class="d-flex align-items-center">
                          <h6 class="mb-0 me-1">Panggil IT</h6>
                            <select wire:model.live="idne" class="form-select form-select-sm ms-2 @error('wa') is-invalid @enderror" style="width: auto;">
                                <option value="" selected>Pilih Team IT</option>
                                @forelse ($usersIT as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @empty
                                    <option selected disabled>Tidak ada IT</option>
                                @endforelse
                            </select>
                            @error('wa')
                              <div class="invalid-feedback d-block">
                                {{ $message }}
                              </div>
                            @enderror
                        </div>
                        
                      </div>
                      
                    </div>
                    <div>
                        
                    </div>
                    
                    {{-- <div class="d-flex align-items-center justify-content-center mt-6 gap-3">
                        <div class="w-100">
                            
                          <label for="pesan" class="form-label fw-bold mb-2">
                            <i class="bx bx-message-dots me-1 text-primary"></i> Tulis Pesan untuk IT Support
                          </label>
                          <div class="position-relative">
                            <textarea 
                              wire:model="pesan" 
                              name="pesan" 
                              id="pesan" 
                              class="form-control rounded-3 shadow-sm @error('pesan') is-invalid @enderror" 
                              rows="5" 
                              placeholder="Jelaskan masalah atau permintaan Anda di sini..."></textarea>
                            <span class="position-absolute end-0 bottom-0 m-2 text-muted" style="font-size: 0.9em;">
                              <i class="bx bx-pencil"></i>
                            </span>
                            @error('pesan')
                              <div class="invalid-feedback d-block">
                                {{ $message }}
                              </div>
                            @enderror
                          </div>
                          <div class="d-flex justify-content-end mt-2">
                            <button 
                              type="button" 
                              class="btn btn-primary btn-sm px-4 rounded-pill shadow"
                              wire:click="kirimPesan"
                            >
                              <i class="bx bx-send me-1"></i> Kirim Pesan
                            </button>
                          </div>
                          
                          
                        </div> --}}
                      {{-- <div class="flex-shrink-0">
                        <div id="expensesOfWeek"></div>
                      </div>
                      <div>
                        <h6 class="mb-0">Income this week</h6>
                        <small>$39k less than last week</small>
                      </div> --}}
                    </div>
                  </div>
                  {{-- <div class="tab-pane fade" id="navs-tabs-line-card-income1" role="tabpanel">
                    <div class="d-flex mb-6">
                      <div class="avatar flex-shrink-0 me-3">
                        <img src="../assets/img/icons/unicons/wallet.png" alt="User" />
                      </div>
                      <div>
                        <p class="mb-0">Total Balancesss</p>
                        <div class="d-flex align-items-center">
                          <h6 class="mb-0 me-1">$459.10</h6>
                          <small class="text-success fw-medium">
                            <i class="icon-base bx bx-chevron-up icon-lg"></i>
                            42.9%
                          </small>
                        </div>
                      </div>
                    </div>
                    <div id="incomeChart"></div>
                    <div class="d-flex align-items-center justify-content-center mt-6 gap-3">
                      <div class="flex-shrink-0">
                        <div id="expensesOfWeek"></div>
                      </div>
                      <div>
                        <h6 class="mb-0">Income this week</h6>
                        <small>$39k less than last week</small>
                      </div>
                    </div>
                  </div> --}}
                </div>
              </div>
            </div>
          </div>
          <!--/ Expense Overview -->
    
          <!-- Transactions -->
          {{-- <div class="col-md-6 col-lg-4 order-2 mb-6">
            <div class="card h-100">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">Transactions</h5>
                <div class="dropdown">
                  <button
                    class="btn text-body-secondary p-0"
                    type="button"
                    id="transactionID"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false">
                    <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="transactionID">
                    <a class="dropdown-item" href="javascript:void(0);">Last 28 Days</a>
                    <a class="dropdown-item" href="javascript:void(0);">Last Month</a>
                    <a class="dropdown-item" href="javascript:void(0);">Last Year</a>
                  </div>
                </div>
              </div>
              <div class="card-body pt-4">
                <ul class="p-0 m-0">
                  <li class="d-flex align-items-center mb-6">
                    <div class="avatar flex-shrink-0 me-3">
                      <img src="../assets/img/icons/unicons/paypal.png" alt="User" class="rounded" />
                    </div>
                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                      <div class="me-2">
                        <small class="d-block">Paypal</small>
                        <h6 class="fw-normal mb-0">Send money</h6>
                      </div>
                      <div class="user-progress d-flex align-items-center gap-2">
                        <h6 class="fw-normal mb-0">+82.6</h6>
                        <span class="text-body-secondary">USD</span>
                      </div>
                    </div>
                  </li>
                  <li class="d-flex align-items-center mb-6">
                    <div class="avatar flex-shrink-0 me-3">
                      <img src="../assets/img/icons/unicons/wallet.png" alt="User" class="rounded" />
                    </div>
                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                      <div class="me-2">
                        <small class="d-block">Wallet</small>
                        <h6 class="fw-normal mb-0">Mac'D</h6>
                      </div>
                      <div class="user-progress d-flex align-items-center gap-2">
                        <h6 class="fw-normal mb-0">+270.69</h6>
                        <span class="text-body-secondary">USD</span>
                      </div>
                    </div>
                  </li>
                  <li class="d-flex align-items-center mb-6">
                    <div class="avatar flex-shrink-0 me-3">
                      <img src="../assets/img/icons/unicons/chart.png" alt="User" class="rounded" />
                    </div>
                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                      <div class="me-2">
                        <small class="d-block">Transfer</small>
                        <h6 class="fw-normal mb-0">Refund</h6>
                      </div>
                      <div class="user-progress d-flex align-items-center gap-2">
                        <h6 class="fw-normal mb-0">+637.91</h6>
                        <span class="text-body-secondary">USD</span>
                      </div>
                    </div>
                  </li>
                  <li class="d-flex align-items-center mb-6">
                    <div class="avatar flex-shrink-0 me-3">
                      <img src="../assets/img/icons/unicons/cc-primary.png" alt="User" class="rounded" />
                    </div>
                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                      <div class="me-2">
                        <small class="d-block">Credit Card</small>
                        <h6 class="fw-normal mb-0">Ordered Food</h6>
                      </div>
                      <div class="user-progress d-flex align-items-center gap-2">
                        <h6 class="fw-normal mb-0">-838.71</h6>
                        <span class="text-body-secondary">USD</span>
                      </div>
                    </div>
                  </li>
                  <li class="d-flex align-items-center mb-6">
                    <div class="avatar flex-shrink-0 me-3">
                      <img src="../assets/img/icons/unicons/wallet.png" alt="User" class="rounded" />
                    </div>
                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                      <div class="me-2">
                        <small class="d-block">Wallet</small>
                        <h6 class="fw-normal mb-0">Starbucks</h6>
                      </div>
                      <div class="user-progress d-flex align-items-center gap-2">
                        <h6 class="fw-normal mb-0">+203.33</h6>
                        <span class="text-body-secondary">USD</span>
                      </div>
                    </div>
                  </li>
                  <li class="d-flex align-items-center">
                    <div class="avatar flex-shrink-0 me-3">
                      <img src="../assets/img/icons/unicons/cc-warning.png" alt="User" class="rounded" />
                    </div>
                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                      <div class="me-2">
                        <small class="d-block">Mastercard</small>
                        <h6 class="fw-normal mb-0">Ordered Food</h6>
                      </div>
                      <div class="user-progress d-flex align-items-center gap-2">
                        <h6 class="fw-normal mb-0">-92.45</h6>
                        <span class="text-body-secondary">USD</span>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div> --}}
          <!--/ Transactions -->
        </div>
        
    </div>
  
    
</div>


  @push('scripts')
  <script>
    // 2. Tambahkan skrip ini untuk menginisialisasi animasi Rive
    document.addEventListener("DOMContentLoaded", function() {
      const canvas = document.getElementById('rive-animation-canvas');
      let handsUpInput;

      const riveInstance = new rive.Rive({
        src: "/assets/riv/muza.riv", //
        canvas: canvas,
        autoplay: true,
        stateMachines: ["State Machine 1"],
        onLoad: () => {
          const inputs = riveInstance.stateMachineInputs("State Machine 1");
          handsUpInput = inputs.find(i => i.name === "hands_up");
        }
      });
    });
</script>
    <script>
        const cardColor = '#fff'; // warna latar belakang kartu
        const headingColor = '#566a7f'; // warna judul chart
        const legendColor = '#a1acb8'; // warna teks legend
        const fontFamily = 'Public Sans'; // font utama

        const config = {
            colors: {
            success: '#28C76F',
            primary: '#7367F0',
            secondary: '#82868b',
            info: '#00cfe8'
            }
        };
       
        document.addEventListener('livewire:init', () => {
            Livewire.on('updateOrderChart', ({labels = [], series = [] }) => {
                const chartOrderStatistics = document.querySelector('#orderStatisticsChart1'),
                orderChartConfig = {
                chart: {
                    height: 165,
                    width: 136,
                    type: 'donut',
                    offsetX: 15
                },
                labels: labels,
                series: series,
                colors: [config.colors.success, config.colors.primary, config.colors.secondary, config.colors.info],
                stroke: {
                    width: 5,
                    colors: ['#6366f1', '#818cf8', '#f472b6', '#facc15']
                },
                dataLabels: {
                    enabled: false,
                    formatter: function (val, opt) {
                    return parseInt(val) + '%';
                    }
                },
                legend: {
                    show: false
                },
                grid: {
                    padding: {
                    top: 0,
                    bottom: 0,
                    right: 15
                    }
                },
                states: {
                    hover: {
                    filter: { type: 'none' }
                    },
                    active: {
                    filter: { type: 'none' }
                    }
                },
                plotOptions: {
                    pie: {
                    donut: {
                        size: '75%',
                        labels: {
                        show: true,
                        value: {
                            fontSize: '1.125rem',
                            fontFamily: fontFamily,
                            fontWeight: 500,
                            color: headingColor,
                            offsetY: -17,
                            formatter: function (val) {
                            return parseInt(val) + '%';
                            }
                        },
                        name: {
                            offsetY: 17,
                            fontFamily: fontFamily
                        },
                        total: {
                            show: true,
                            fontSize: '13px',
                            color: legendColor,
                            label: 'Weekly',
                            formatter: function (w) {
                            return '38%';
                            }
                        }
                        }
                    }
                    }
                }
                };
                
                if (typeof chartOrderStatistics !== undefined && chartOrderStatistics !== null) {
                    const statisticsChart = new ApexCharts(chartOrderStatistics, orderChartConfig);
                    statisticsChart.render();
                }
            });
        });
    </script>
  @endpush