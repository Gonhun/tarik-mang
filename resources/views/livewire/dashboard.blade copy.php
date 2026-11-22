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
    @if (Auth::user()->roles[0]['name'] =='Karyawan') 
        
        <div class="container-fluid py-4">
            <div class="card-header mb-2">
                <h5 class="mb-0">Daftar BAST Belum validasi</h5>
                <p class="text-sm mb-0">Daftar Asset yang di sign ke {{Auth::user()->name}} belum di validasi</p>
            </div>
            
            <div class="table-responsive shadow rounded">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No. Transaksi</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tgl Transaksi</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Jenis</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Dari User</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Kepada User</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Item</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dokumen</th>
                            <th class="text-secondary opacity-7"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction) {{-- Asumsi var $transactions dari komponen --}}
                            <tr>
                                <td><p class="text-xs font-weight-bold mb-0 px-3">{{ $transaction->transaction_number }}</p></td>
                                <td><p class="text-xs font-weight-bold mb-0">{{ \Carbon\Carbon::parse($transaction->transaction_date)->isoFormat('D MMM YYYY') }}</p></td>
                                <td>
                                    <span class="badge badge-sm bg-gradient-primary">
                                        @if($transaction->transaction_type == 'HANDOVER') Serah Terima
                                        @elseif($transaction->transaction_type == 'RETURN') Pengembalian
                                        @elseif($transaction->transaction_type == 'DAMAGE_REPORT') Lap. Kerusakan
                                        @else {{ Str::title(str_replace('_', ' ', $transaction->transaction_type)) }}
                                        @endif
                                    </span>
                                </td>
                                <td><p class="text-xs font-weight-bold mb-0">{{ $transaction->fromUser->name ?? 'N/A' }}</p></td>
                                <td><p class="text-xs font-weight-bold mb-0">{{ $transaction->toUser->name ?? 'N/A' }}</p></td>
                                <td class="text-center"><span class="badge bg-secondary">{{ $transaction->items_count ?? $transaction->items->count() }}</span></td>
                                <td class="text-center">
                                    <span class="badge bg-gradient-{{ $transaction->status == 'Completed' || $transaction->status == 'Processed' ? 'success' : ($transaction->status == 'Draft' || $transaction->status == 'Pending Inspection' ? 'info' : 'warning') }}">
                                        {{ $transaction->status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if ($transaction->document_path)
                                        <a href="{{ asset('storage/'.$transaction->document_path) }}" target="_blank" class="btn btn-link text-primary p-0 m-0" data-bs-toggle="tooltip" title="{{$transaction->document_name}}">
                                            <i class="bx bx-bxs-show"></i> Lihat
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <button wire:click="edit({{ $transaction->id }})" class="btn btn-link text-success mb-0 p-1" data-bs-toggle="tooltip" title="Edit Transaksi">
                                        <i class="bx bx-check-circle text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-4">Tidak ada data transaksi aset ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        
            <div class="mt-3">
                {{ $transactions->links() }}
            </div>
        </div>
        
    @else
    <div>
        <div class="container-fluid py-4">
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
        </div>
      </div>
    @endif
    
    
    
    
    @push('scripts')
    <!-- Font Awesome for icons (optional, can be removed if already loaded) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <script>
      // Gunakan livewire:init untuk memastikan Livewire dan ApexCharts sudah siap
      document.addEventListener('livewire:init', () => {
    
          // Inisialisasi semua objek chart sekali saja
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
                  fontSize: '20px',
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
              series: [0], // Default dummy value to ensure chart renders
              labels: ['-'], // Default dummy label
              dataLabels: {
                enabled: true,
                    formatter: function (val, opts) {
                        return opts.w.globals.series[opts.seriesIndex]; // nilai asli, bukan persen
                    },
                  style: {
                      fontSize: '16px',
                      fontWeight: 'bold'
                  }
              },
            //   tooltip: {
            //       enabled: true,
            //       custom: function({ series, seriesIndex, dataPointIndex, w }) {
            //           // Perbaiki agar tooltip selalu menampilkan label dan value
            //           let label = w.globals.labels[seriesIndex] || '-';
            //           let value = series[seriesIndex] || 0;
            //           return `<div class="apexcharts-tooltip-title" style="font-weight:600;">${label}</div>
            //                   <div style="font-size:16px;font-weight:bold;">${value} aset</div>`;
            //       }
            //   }
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
                  yaxis: { lines: { show: true } }
              },
              tooltip: {
                  theme: 'light',
                  y: { formatter: val => val + " aset" }
              },
              series: [{ name: 'Jumlah Aset', data: [] }],
              xaxis: {
                  categories: [],
                  labels: { rotate: -15, style: { fontSize: '13px', colors: '#374151' } }
              },
              yaxis: {
                  labels: { style: { fontSize: '13px', colors: '#374151' } }
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
              series: [{ name: 'Jumlah Aset', data: [] }],
              xaxis: {
                  categories: [],
                  labels: { style: { fontSize: '13px', colors: '#374151' } }
              },
              yaxis: {
                  labels: { style: { fontSize: '13px', colors: '#374151' } }
              }
          });
          assetsByJobsiteChart.render();
    
          const assetRequestTrendChart = new ApexCharts(document.querySelector("#assetRequestTrendChart"), {
              chart: {
                  type: 'line',
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
              series: [{ name: 'Jumlah Pengajuan', data: [] }],
              xaxis: {
                  type: 'category',
                  categories: [],
                  labels: { style: { fontSize: '13px', colors: '#374151' } }
              },
              yaxis: {
                  labels: { style: { fontSize: '13px', colors: '#374151' } }
              }
          });
          assetRequestTrendChart.render();
    
          // Listener untuk event 'updateCharts' dari komponen Livewire
          Livewire.on('updateCharts', (event) => {
              const data = event[0] || event; // Kompatibilitas Livewire v3
    
              // Update setiap chart dengan data baru
              assetsByStatusChart.updateOptions({
                  labels: data.assetsByStatus.labels,
                  series: data.assetsByStatus.series,
                  // Animasi bounce saat update
                  chart: { animations: { enabled: true, easing: 'easeinout', speed: 900 } }
              });
    
              assetsByTypeChart.updateOptions({
                  xaxis: { categories: data.assetsByType.categories },
                  series: [{ data: data.assetsByType.series }],
                  chart: { animations: { enabled: true, easing: 'easeinout', speed: 1000 } }
              });
    
              assetsByJobsiteChart.updateOptions({
                  xaxis: { categories: data.assetsByJobsite.categories },
                  series: [{ data: data.assetsByJobsite.series }],
                  chart: { animations: { enabled: true, easing: 'easeinout', speed: 1000 } }
              });
    
              assetRequestTrendChart.updateOptions({
                  xaxis: { categories: data.assetRequestTrend.labels },
                  series: [{ data: data.assetRequestTrend.series }],
                  chart: { animations: { enabled: true, easing: 'easeinout', speed: 1200 } }
              });
    
              // Efek animasi pada chart container saat update
              document.querySelectorAll('.chart-animate').forEach(el => {
                  el.classList.remove('chart-animate');
                  void el.offsetWidth; // trigger reflow
                  el.classList.add('chart-animate');
              });
          });
    
      });
    </script>
    @endpush
</div>