<div>
    <style>
        /* Container harus memiliki overflow agar sticky bekerja */
        .table-responsive {
            overflow-x: auto;
            display: block;
        }

        /* 1. Targetkan hanya baris pertama di thead dan semua baris di tbody */
        /* Kolom 1: Material Code */
        thead tr:first-child th:nth-child(1),
        tbody tr td:nth-child(1) {
            position: sticky !important;
            left: 0;
            background-color: #fafafa !important;
            z-index: 10 !important;
            min-width: 120px;
        }

        /* Kolom 2: Material Name */
        thead tr:first-child th:nth-child(2),
        tbody tr td:nth-child(2) {
            position: sticky !important;
            left: 120px;
            /* Harus sama dengan lebar kolom 1 */
            background-color: #fafafa !important;
            z-index: 10 !important;
            min-width: 180px;
            border-right: 2px solid #ddd;
            /* Garis pembatas agar rapi */
        }

        /* 2. Matikan sticky untuk baris kedua (Januari dst) agar tidak ikut beku */
        thead tr:nth-child(2) th {
            position: static !important;
        }

        /* 3. Menjaga header tetap di atas (Sticky Top) */
        thead th {
            position: sticky;
            top: 0;
        }
    </style>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex Justify-content-between pb-0">
                        <h5 class="mb-0">Forecast</h5>
                        <div class="ms-auto align-items-baseline d-flex">
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <select id="jobsites" class="form-select form-select-sm mb-2"
                                        aria-label="Select Jobsite" wire:model.live="site_filter">
                                        <option value="">-- Select Jobsite --</option>
                                        @foreach ($jobsites as $jobsite)
                                            <option value="{{ $jobsite->id }}">
                                                {{ $jobsite->code }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <input type="text" id="txtYear" class="form-control form-control-sm mb-2"
                                        placeholder="Isi Tahun" wire:model="year_filter" />
                                </div>
                                <div class="col-auto">
                                    <select id="month_filter" class="form-select form-select-sm mb-2"
                                        aria-label="Select Month" wire:model.live="month_filter">
                                        <option value="">-- Select Month --</option>
                                        @foreach ($months as $month)
                                            <option value="{{ $month }}">
                                                {{ $month }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-body px-0 pt-0 pb-2">
                            <div class="table-responsive p-1">
                                <table class="table table-bordered align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase align-middle text-secondary text-xxs font-weight-bolder opacity-7"
                                                rowspan="2">Material Code
                                            </th>
                                            <th class="text-uppercase align-middle text-secondary text-xxs font-weight-bolder opacity-7"
                                                rowspan="2">Material Name
                                            </th>
                                            <th class="text-uppercase text-center text-secondary text-xxs font-weight-bolder opacity-7"
                                                colspan="10">@if ($this->month_filter)
                                                    {{ $this->month_filter }}
                                                @endif
                                            </th>
                                            {{-- <th
                                                class="text-uppercase text-center text-secondary text-xxs font-weight-bolder opacity-7"
                                                colspan="7">Feb</th>
                                            <th class="text-uppercase text-center text-secondary text-xxs font-weight-bolder opacity-7"
                                                colspan="7">Mar</th>
                                            <th class="text-uppercase text-center text-secondary text-xxs font-weight-bolder opacity-7"
                                                colspan="7">Apr</th>
                                            <th class="text-uppercase text-center text-secondary text-xxs font-weight-bolder opacity-7"
                                                colspan="7">May</th>
                                            <th class="text-uppercase text-center text-secondary text-xxs font-weight-bolder opacity-7"
                                                colspan="7">Jun</th> --}}
                                        </tr>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                style="white-space: nowrap; width: auto;">
                                                Stok awal</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                style="white-space: nowrap; width: auto;">
                                                Avg usage</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                style="white-space: nowrap; width: auto;">
                                                Max usage</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                style="white-space: nowrap; width: auto;">
                                                Periode</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                style="white-space: nowrap; width: auto;">
                                                Ss</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                style="white-space: nowrap; width: auto;">
                                                Min</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                style="white-space: nowrap; width: auto;">
                                                Max</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                style="white-space: nowrap; width: auto;">
                                                SOH</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                style="white-space: nowrap; width: auto;">
                                                Status</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                style="white-space: nowrap; width: auto;">
                                                Qty Order</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($this->datas())
                                            @foreach ($this->datas() as $data)
                                                <tr>
                                                    <td>
                                                        <p class="text-xs font-weight-bold mb-0">
                                                            <a href="{{ route('forecast.detail', base64_encode($data->material_code . ',' . $data->jobsite_id . ',' . $this->month_filter)) }}"
                                                                target="_blank" class="btn btn-sm btn-outline-secondary mb-0">
                                                                {{ $data->material_code ?? 'N/A' }}
                                                            </a>
                                                        </p>
                                                    </td>
                                                    <td>
                                                        <p class="text-xs font-weight-bold mb-0">
                                                            {{ $data->material_name ?? 'N/A' }}
                                                        </p>
                                                    </td>
                                                    @if($this->month_filter)
                                                        <td>
                                                            <p class="text-xs font-weight-bold mb-0">
                                                                {{ $data->{$this->month_filter} ?? '0' }}
                                                            </p>
                                                        </td>
                                                        <td>
                                                            <p class="text-xs font-weight-bold mb-0 ">
                                                                {{ $data->{"avg_usage_" . $this->month_filter} ?? '0' }}
                                                            </p>
                                                        </td>
                                                        <td>
                                                            <p class="text-xs font-weight-bold mb-0">{{ $data->max_usage ?? '0' }}
                                                            </p>
                                                        </td>
                                                        <td>
                                                            <p class="text-xs font-weight-bold mb-0">
                                                                {{ number_format($data->period, 0) ?? '0' }}
                                                            </p>
                                                        </td>
                                                        <td>
                                                            <p class="text-xs font-weight-bold mb-0">
                                                                {{ number_format($data->{"ss_" . $this->month_filter}, 0) ?? '0' }}
                                                            </p>
                                                        </td>
                                                        <td>
                                                            <p class="text-xs font-weight-bold mb-0">
                                                                {{ number_format($data->{"min_stock_" . $this->month_filter}, 0) ?? '0' }}
                                                            </p>
                                                        </td>
                                                        <td>
                                                            <p class="text-xs font-weight-bold mb-0">
                                                                {{ number_format($data->{"max_stock_" . $this->month_filter}, 0) ?? '0' }}
                                                            </p>
                                                        </td>
                                                        <td>
                                                            <p class="text-xs font-weight-bold mb-0">
                                                                {{ number_format($data->soh, 0) ?? '0' }}
                                                            </p>
                                                        </td>
                                                        <td>
                                                            <p class="text-xs font-weight-bold mb-0">
                                                                {{ $data->{"status_" . $this->month_filter} ?? 'N/A' }}
                                                            </p>
                                                        </td>
                                                        <td>
                                                            <p class="text-xs font-weight-bold mb-0">
                                                                {{ number_format($data->{"qty_order_" . $this->month_filter}, 0) ?? '0' }}
                                                            </p>
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td scope="row" colspan="16">No found any data</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="px-4 pt-3">
                                        <label for="data_show" class="form-label mb-0 me-2">Tampilkan</label>
                                        <select wire:model.live="data_show" id="data_show"
                                            class="form-select form-select-sm w-auto d-inline-block">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                        <span class="ms-1">data per halaman</span>
                                    </div>

                                    {{-- Optional: tampilkan total data --}}
                                </div>
                                <div class="px-4 pt-3">
                                    {{ $this->datas()->links('pagination::bootstrap-5', ['pageName' => 'forecast']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>