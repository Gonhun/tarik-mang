<div>
    <div class="container-fluid py-4">
        {{-- Bagian 1: Kartu Statistik Ringkasan --}}
        <div class="row">
            <div id="rive-animation-canvas1" class="row-xl-12 mb-4">
                <div class="col-lg-12">
                    <div class="card" style="background-color: #23272b; color: #fff;">
                        <div class="d-flex align-items-start row">
                            <div class="col-lg-7">
                                <div class="card-body p-3">
                                    <h5 class="card-title text-primary mb-3" style="color: #4e9cff !important;">Selamat Datang {{ Auth::user()->name }}! 🎉</h5>
                                    <p class="mb-6">
                                        {{-- You have done 72% more sales today.<br />Check your new badge in your profile. --}}
                                    </p>
                                    {{-- <a href="javascript:;" class="btn btn-sm btn-outline-primary">View Badges</a> --}}
                                </div>
                            </div>
                            <div class="col-lg-5 d-flex align-items-center justify-content-end">
                                <div style="width:100%; max-width:350px;">
                                    <canvas id="rive-animation-canvas" class="rive-bg-canvas" style="width:100%; height:150px;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            @can('Show Dashboard')
            <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                
                <div class="card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Total Seluruh Aset</p>
                                <h5 class="font-weight-bolder">{{ $totalAssets }}</h5>
                            </div>
                            <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle"><i class="fas fa-box-open text-lg opacity-10"></i></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-body p-3">
                         <div class="row">
                            <div class="col-8">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Status Free</p>
                                <h5 class="font-weight-bolder text-success">{{ $freeAssets }}</h5>
                            </div>
                            <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle"><i class="fas fa-check-circle text-lg opacity-10"></i></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-body p-3">
                         <div class="row">
                            <div class="col-8">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Status Used</p>
                                <h5 class="font-weight-bolder text-info">{{ $usedAssets }}</h5>
                            </div>
                            <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-info shadow-info text-center rounded-circle"><i class="fas fa-user-tag text-lg opacity-10"></i></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card">
                    <div class="card-body p-3">
                         <div class="row">
                            <div class="col-8">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Status Repair</p>
                                <h5 class="font-weight-bolder text-warning">{{ $repairAssets }}</h5>
                            </div>
                            <div class="col-4 text-end"><div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle"><i class="fas fa-tools text-lg opacity-10"></i></div></div>
                        </div>
                    </div>
                </div>
            </div>
           @endcan
        </div>
       @can('Show Dashboard')
        {{-- Bagian 2: Grafik / Chart --}}
            <div class="row mt-4">
                <div class="col-lg-7 mb-lg-0 mb-4">
                    <div class="card z-index-2 h-100">
                        <div class="card-header pb-0 pt-3 bg-transparent">
                            <h6 class="text-capitalize">Tren Pengajuan Aset (12 Bulan Terakhir)</h6>
                        </div>
                        <div class="card-body p-3">
                            <div wire:ignore id="assetRequestTrendChart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header pb-0 pt-3 bg-transparent">
                            <h6 class="text-capitalize">Jumlah Aset per Jobsite</h6>
                        </div>
                        <div class="card-body p-3">
                            <div wire:ignore id="assetsByJobsiteChart"></div>
                        </div>
                    </div>
                </div>
            </div>
       
        {{-- Bagian 3: Tabel Pengajuan Aset Aktif --}}
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header pb-0">
                            <h6>Pengajuan Aset Sedang Berjalan</h6>
                        </div>
                        <div class="card-body px-0 pt-0 pb-2">
                            <div class="table-responsive p-0">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No. Pengajuan</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Pemohon</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Jobsite</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($activeRequests as $request)
                                            <tr>
                                                <td><p class="text-sm font-weight-bold mb-0 px-3">{{ $request->request_number }}</p></td>
                                                <td><p class="text-sm mb-0">{{ $request->requester->name ?? 'N/A' }}</p></td>
                                                <td><p class="text-sm mb-0">{{ $request->jobsite->name ?? 'N/A' }}</p></td>
                                                <td><span class="badge badge-sm bg-gradient-warning">{{ $request->status }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center py-3">Tidak ada pengajuan aset yang sedang berjalan.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
      const canvas = document.getElementById('rive-animation-canvas');
      let handsUpInput;

      const riveInstance = new rive.Rive({
        src: "/assets/riv/muza.riv", //
        canvas: canvas,
        autoplay: true,
        stateMachines: "State Machine 1",
        onLoad: () => {
          const inputs = riveInstance.stateMachineInputs("State Machine 1");
          const chinHoldTrigger = inputs.find(i => i.name === "Chin hold");
        const foreheadClickTrigger = inputs.find(i => i.name === "Forehead click");
        document.getElementById("rive-animation-canvas1").addEventListener("click", () => {
                foreheadClickTrigger.fire(); // picu animasi
            });
            chinHoldTrigger.fire()
        }
      });
    });
    document.addEventListener('livewire:init', () => {
        // Inisialisasi Chart Jumlah Aset per Jobsite (Bar Chart)
        const assetsByJobsiteChart = new ApexCharts(document.querySelector("#assetsByJobsiteChart"), {
            chart: { type: 'bar', height: 350 },
            plotOptions: { bar: { borderRadius: 4, horizontal: false, } },
            series: [{ name: 'Jumlah Aset', data: [] }],
            xaxis: { 
                    categories: [],
                    // PERBAIKAN: Pastikan sumbu dimulai dari 0
                    min: 0,
                   
                },
             yaxis: {
                labels: {
                    formatter: function (val) {
                        return parseInt(val); // ini untuk axis kiri
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return parseInt(val); // ini untuk angka di atas bar
                }
            },
            noData: { text: 'Memuat data...' }
        });
        assetsByJobsiteChart.render();

        // Inisialisasi Chart Tren Pengajuan Aset (Line Chart)
        const assetRequestTrendChart = new ApexCharts(document.querySelector("#assetRequestTrendChart"), {
            chart: { type: 'area', height: 350, zoom: { enabled: false } },
            stroke: { curve: 'smooth' },
            series: [{ name: 'Jumlah Pengajuan', data: [] }],
            xaxis: { type: 'category', categories: [] },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return parseInt(val); // ini untuk axis kiri
                    }
                }
            },
            noData: { text: 'Memuat data...' }
        });
        assetRequestTrendChart.render();

        // Listener untuk event 'updateAssetCharts' dari komponen Livewire
        Livewire.on('updateAssetCharts', (data) => {
            
            if (!data[0]) return;
            
            if (data[0].assetsByJobsite) {
                assetsByJobsiteChart.updateOptions({
                    series: [{ data: data[0].assetsByJobsite.series || [] }],
                    xaxis: { categories: data[0].assetsByJobsite.categories || [] }
                });
            }
            
            if (data[0].assetRequestTrend) {
                assetRequestTrendChart.updateOptions({
                    series: [{ data: data[0].assetRequestTrend.series || [] }],
                    xaxis: { categories: data[0].assetRequestTrend.labels || [] }
                });
            }
        });
    });
</script>
@endpush