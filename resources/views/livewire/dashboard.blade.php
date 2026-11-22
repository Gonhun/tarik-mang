<div>
    <style>
        /* Responsive card grid */
        @media (max-width: 991.98px) {
            .dashboard-card-col {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        @media (min-width: 992px) {
            .dashboard-card-col {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        .dashboard-card {
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08), 0 1.5px 4px rgba(0,0,0,0.04);
            background: linear-gradient(135deg, #f8fafc 60%, #e0e7ff 100%);
            transition: transform 0.25s cubic-bezier(.4,2,.6,1), box-shadow 0.25s;
            overflow: hidden;
            position: relative;
        }
        .dashboard-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 8px 32px rgba(80,80,200,0.18), 0 2px 8px rgba(0,0,0,0.08);
        }
        .dashboard-card-header {
            background: linear-gradient(90deg, #6366f1 0%, #818cf8 100%);
            color: #fff;
            border-bottom: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(99,102,241,0.08);
        }
        .dashboard-card-body {
            background: transparent;
            padding: 1.5rem 1.5rem 1rem 1.5rem;
            min-height: 370px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Chart container animation */
        .chart-animate {
            animation: fadeInUp 1s cubic-bezier(.4,2,.6,1);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: translateY(0);}
        }
    </style>
    
    <div>
        {{-- <div class="container-fluid py-4"> --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <label for="jobsiteFilter" class="form-label fw-semibold text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-2"></i>Filter Jobsite
                                    </label>
                                    <select wire:model.live="selectedJobsite" id="jobsiteFilter" class="form-select border-0 bg-light">
                                        <option value="">Semua Jobsite</option>
                                        @foreach($jobsites as $jobsite)
                                            <option value="{{ $jobsite->id }}">{{ $jobsite->code }} - ({{ $jobsite->name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-3 mb-3 mb-md-0">
                                    {{-- <label for="statusFilter" class="form-label fw-semibold text-muted mb-2">
                                        <i class="fas fa-info-circle me-2"></i>Filter Status
                                    </label> --}}
                                    {{-- <select wire:model.live="selectedStatus" id="statusFilter" class="form-select border-0 bg-light">
                                        <option value="">Semua Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="retired">Retired</option>
                                    </select> --}}
                                </div>
                                
                                <div class="col-md-3 mb-3 mb-md-0">
                                    {{-- <label for="typeFilter" class="form-label fw-semibold text-muted mb-2">
                                        <i class="fas fa-tag me-2"></i>Filter Tipe Aset
                                    </label> --}}
                                    {{-- <select wire:model.live="selectedType" id="typeFilter" class="form-select border-0 bg-light">
                                        <option value="">Semua Tipe</option>
                                        @foreach($assetTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select> --}}
                                </div>
                                
                                <div class="col-md-3 d-flex align-items-end mt-7">
                                    <button wire:click="resetFilters" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-refresh me-2"></i>Reset Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row d-flex flex-wrap">
                {{-- CARD UNTUK PIE CHART --}}
                <div class="dashboard-card-col mb-4 d-flex">
                    <div class="dashboard-card w-100">
                        <div class="dashboard-card-header">
                            <i class="fas fa-chart-pie me-2"></i> Aset Berdasarkan Status
                        </div>
                        <div class="dashboard-card-body">
                            {{-- Wadah untuk chart --}}
                            <div wire:ignore id="assetsByStatusChart" class="chart-animate"></div>
                        </div>
                    </div>
                </div>
      
                {{-- CARD UNTUK BAR CHART --}}
                <div class="dashboard-card-col mb-4 d-flex">
                    <div class="dashboard-card w-100">
                        <div class="dashboard-card-header">
                            <i class="fas fa-layer-group me-2"></i> Top 10 Tipe Aset
                        </div>
                        <div class="dashboard-card-body">
                            {{-- Wadah untuk chart --}}
                            <div wire:ignore id="assetsByTypeChart" class="chart-animate"></div>
                        </div>
                    </div>
                </div>
            </div>
      
            <div class="row d-flex flex-wrap mt-4">
                {{-- CARD UNTUK HORIZONTAL BAR CHART --}}
                <div class="dashboard-card-col mb-4 d-flex">
                    <div class="dashboard-card w-100">
                        <div class="dashboard-card-header">
                            <i class="fas fa-map-marker-alt me-2"></i> Jumlah Aset per Jobsite
                        </div>
                        <div class="dashboard-card-body">
                            {{-- Wadah untuk chart --}}
                            <div wire:ignore id="assetsByJobsiteChart" class="chart-animate"></div>
                        </div>
                    </div>
                </div>
      
                {{-- CARD UNTUK LINE CHART --}}
                <div class="dashboard-card-col mb-4 d-flex">
                    <div class="dashboard-card w-100">
                        <div class="dashboard-card-header">
                            <i class="fas fa-chart-line me-2"></i> Tren Pengajuan Aset (12 Bulan Terakhir)
                        </div>
                        <div class="dashboard-card-body">
                            {{-- Wadah untuk chart --}}
                            <div wire:ignore id="assetRequestTrendChart" class="chart-animate"></div>
                        </div>
                    </div>
                </div>
            </div>
        {{-- </div> --}}
      </div>
    
    
    
    
    @push('scripts')
    <!-- Font Awesome for icons (optional, can be removed if already loaded) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <script>
      // Gunakan livewire:init untuk memastikan Livewire dan ApexCharts sudah siap
      document.addEventListener('livewire:init', () => {
       

          // FIXED: Defensive, correct legend, color, and fallback handling
          const assetsByStatusChart = new ApexCharts(document.querySelector("#assetsByStatusChart"), {
              chart: {
                  type: 'pie',
                  height: 350,
                  animations: {
                      enabled: true,
                      easing: 'easeinout',
                      speed: 900,
                      animateGradually: { enabled: true, delay: 200 },
                      dynamicAnimation: { enabled: true, speed: 700 }
                  },
                  toolbar: { show: false }
              },
              legend: {
                  position: 'bottom',
                  fontSize: '16px',
                  fontWeight: 500,
                  labels: { colors: ['#374151'] }
              },
              stroke: { width: 2, colors: ['#fff'] },
              fill: {
                  type: 'gradient',
                  gradient: {
                      shade: 'light',
                      type: "diagonal1",
                      shadeIntensity: 0.5,
                      gradientToColors: ["#6366f1", "#818cf8", "#f472b6", "#facc15"],
                      inverseColors: true,
                      opacityFrom: 0.85,
                      opacityTo: 0.95,
                      stops: [0, 100]
                  }
              },
              series: [], // Default dummy value to ensure chart renders
              labels: [], // Default dummy label
              dataLabels: {
                  enabled: true,
                  formatter: function (val, opts) {
                      // Defensive: check if opts.w.globals.series exists and is array
                      if (
                          opts &&
                          opts.w &&
                          opts.w.globals &&
                          Array.isArray(opts.w.globals.series) &&
                          typeof opts.seriesIndex !== 'undefined'
                      ) {
                          // If value is undefined, fallback to 0
                          return opts.w.globals.series[opts.seriesIndex] !== undefined
                              ? opts.w.globals.series[opts.seriesIndex]
                              : 0;
                      }
                      return 0;
                  },
                  style: {
                      fontSize: '14px',
                      fontWeight: 'bold'
                  }
              },
              tooltip: {
                  y: {
                      formatter: function(val) {
                          return val + " aset";
                      }
                  }
              }
          });
          
          assetsByStatusChart.render();

          const assetsByTypeChart = new ApexCharts(document.querySelector("#assetsByTypeChart"), {
              chart: {
                  type: 'bar',
                  height: 350,
                  animations: {
                      enabled: true,
                      easing: 'easeinout',
                      speed: 1000,
                      animateGradually: { enabled: true, delay: 150 },
                      dynamicAnimation: { enabled: true, speed: 700 }
                  },
                  toolbar: { show: false }
              },
              plotOptions: {
                  bar: {
                      borderRadius: 8,
                      horizontal: false,
                      columnWidth: '55%',
                      endingShape: 'rounded'
                  }
              },
              dataLabels: { enabled: false },
              colors: ['#6366f1', '#818cf8', '#f472b6', '#facc15'],
              grid: {
                  borderColor: "#e5e7eb",
                  strokeDashArray: 4,
                  yaxis: { 
                    lines: { show: true } 
                }
              },
              tooltip: {
                  theme: 'light',
                  y: { formatter: val => val + " aset" }
              },
              series: [{ name: 'Jumlah Aset', data: [0] }], // Default dummy data
              xaxis: {
                  categories: ['-'],
                  labels: { rotate: -15, style: { fontSize: '13px', colors: '#374151' } }
              },
              yaxis: {
                
                  labels: { formatter: function (val) {
                        return Math.round(val); // Menghilangkan koma
                        },style: { fontSize: '13px', colors: '#374151' } }
              }
          });
          assetsByTypeChart.render();

          const assetsByJobsiteChart = new ApexCharts(document.querySelector("#assetsByJobsiteChart"), {
              chart: {
                  type: 'bar',
                  height: 350,
                  animations: {
                      enabled: true,
                      easing: 'easeinout',
                      speed: 1000,
                      animateGradually: { enabled: true, delay: 150 },
                      dynamicAnimation: { enabled: true, speed: 700 }
                  },
                  toolbar: { show: false }
              },
              plotOptions: {
                  bar: {
                      borderRadius: 8,
                      horizontal: true,
                      barHeight: '60%',
                      endingShape: 'rounded'
                  }
              },
              dataLabels: { enabled: true, style: { fontSize: '14px', colors: ['#6366f1'] } },
              colors: ['#facc15', '#6366f1', '#818cf8', '#f472b6'],
              grid: {
                  borderColor: "#e5e7eb",
                  strokeDashArray: 4,
                  xaxis: { lines: { show: true } }
              },
              tooltip: {
                  theme: 'light',
                  y: { formatter: val => val + " aset" }
              },
              series: [{ name: 'Jumlah Aset', data: [0] }], // Default dummy data
              xaxis: {
                  categories: ['-'],
                  labels: { 
                    formatter: function (val) {
                    return Math.round(val); // Angka horizontal: jumlah aset
                    },style: { fontSize: '13px', colors: '#374151' } }
              },
              yaxis: {
                  labels: { style: { fontSize: '13px', colors: '#374151' } }
              }
          });
          assetsByJobsiteChart.render();

          const assetRequestTrendChart = new ApexCharts(document.querySelector("#assetRequestTrendChart"), {
              chart: {
                  type: 'area',
                  height: 350,
                  zoom: { enabled: false },
                  animations: {
                      enabled: true,
                      easing: 'easeinout',
                      speed: 1200,
                      animateGradually: { enabled: true, delay: 200 },
                      dynamicAnimation: { enabled: true, speed: 800 }
                  },
                  toolbar: { show: false }
              },
              dataLabels: { enabled: true, style: { fontSize: '14px', colors: ['#6366f1'] } },
              stroke: { curve: 'smooth', width: 4, colors: ['#6366f1'] },
              markers: {
                  size: 6,
                  colors: ['#fff'],
                  strokeColors: '#6366f1',
                  strokeWidth: 3,
                  hover: { size: 9 }
              },
              fill: {
                  type: 'gradient',
                  gradient: {
                      shade: 'light',
                      type: "vertical",
                      shadeIntensity: 0.4,
                      gradientToColors: ["#818cf8"],
                      inverseColors: false,
                      opacityFrom: 0.5,
                      opacityTo: 0.1,
                      stops: [0, 100]
                  }
              },
              grid: {
                  borderColor: "#e5e7eb",
                  strokeDashArray: 4,
                  yaxis: { lines: { show: true } }
              },
              tooltip: {
                  theme: 'light',
                  y: { formatter: val => val + " pengajuan" }
              },
              series: [{ name: 'Jumlah Pengajuan', data: [0] }], // Default dummy data
              xaxis: {
                  type: 'category',
                  categories: ['-'],
                  labels: { style: { fontSize: '13px', colors: '#374151' } }
              },
              yaxis: {
                  labels: { formatter: function (val) {
                    return Math.round(val); // Angka horizontal: jumlah aset
                    },style: { fontSize: '13px', colors: '#374151' } }
              }
          });
          assetRequestTrendChart.render();

          function safeArray(arr, fallback = []) {
              return Array.isArray(arr) ? arr : (arr !== undefined && arr !== null ? [arr] : fallback);
          }
          // Listener untuk event 'updateCharts' dari komponen Livewire
          Livewire.on('updateCharts', (event) => {
            // Normalize event data: sometimes Livewire sends array, sometimes object
            // const data = Array.isArray(event) ? event[0] : event;
            // console.log(event);
            const data = Array.isArray(event) ? event[0] : event
            if (!data) {
                  console.error('Menerima event updateCharts tanpa data.');
                  return;
              }
              console.log('status ',data.assetsByStatus);
              console.log('assetsByType ',data.assetsByType);
              // Update Pie Chart (Status)
              if (data.assetsByStatus) {
                    assetsByStatusChart.updateOptions({
                        labels: data.assetsByStatus.labels || [],
                        series: data.assetsByStatus.series  || []
                    });
                }
              
            // Update chart: Assets By Type
            if (data.assetsByType) {
                  assetsByTypeChart.updateOptions({
                      series: [{ data: data.assetsByType.series  || [] }],
                      xaxis: { categories: data.assetsByType.categories || [] }
                  });
              }
              
              // Update Horizontal Bar Chart (Jobsite)
              if (data.assetsByJobsite) {
                  assetsByJobsiteChart.updateOptions({
                      series: [{ data: data.assetsByJobsite.series || [] }],
                      xaxis: { categories: data.assetsByJobsite.categories || [] }
                  });
              }
              
              // Update Line Chart (Tren Pengajuan)
              if (data.assetRequestTrend) {
                  assetRequestTrendChart.updateOptions({
                      series: [{ data: data.assetRequestTrend.series || [] }],
                      xaxis: { categories: data.assetRequestTrend.labels || [] }
                  });
              }
        });
    
      });
    </script>
    @endpush
</div>