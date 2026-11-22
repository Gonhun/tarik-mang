<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex Justify-content-between pb-0">
                        <nav>
                            <div class="nav nav-tabs" id="coaTab" role="tablist">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="tab"
                                    data-bs-target="#allsite-tab-pane" type="button" role="tab"
                                    aria-controls="allsite-tab-pane" aria-selected="false">Summary Budget All
                                    Site</button>
                                <button class="nav-link" id="site-tab" data-bs-toggle="tab"
                                    data-bs-target="#site-tab-pane" type="button" role="tab"
                                    aria-controls="site-tab-pane" aria-selected="true">Summary Budget Per
                                    Site</button>
                            </div>
                        </nav>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="tab-content" id="summaryContent">
                            <div class="tab-pane fade show active" id="allsite-tab-pane" role="tabpanel"
                                aria-labelledby="all-tab" tabindex="0">

                                <h5 class="mb-0">Summary Budget</h5>
                                <div class="ms-auto align-items-baseline d-flex">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto">
                                            <input type="text" id="txtYear" class="form-control form-control-sm mb-2"
                                                placeholder="Isi Tahun" wire:model="year_filter" />
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive p-1">
                                    <table class="table table-bordered align-items-center mb-0 flex-nowrap">
                                        <thead>
                                            <tr>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    rowspan="2">
                                                    Site
                                                </th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">Jan</th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">Feb</th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">Mar</th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">Apr</th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">May</th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">Jun</th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">Jul</th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">Aug</th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">Sep</th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">Okt</th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">Nov</th>
                                                <th class="text-center align-middle text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                                    colspan="2">Dec</th>
                                            </tr>
                                            <tr>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Plan</th>
                                                <th
                                                    class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                    Actual</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($this->dataSumarryAlls() as $data)
                                                <tr>
                                                    <td class="align-middle text-center text-sm">
                                                        {{ $data->site_code }}
                                                    </td>
                                                    <td class="align-middle text-center text-sm w-auto">
                                                        Rp {{ number_format($data->plan_jan, 2) }}
                                                    </td>
                                                    <td class="align-middle text-center text-sm w-auto">
                                                        Rp {{ number_format($data->act_jan, 2) }}
                                                    </td>
                                                    <td class="align-middle text-center text-sm w-auto">
                                                        Rp {{ number_format($data->plan_feb, 2) }}
                                                    </td>
                                                    <td class="align-middle text-center text-sm w-auto">
                                                        Rp {{ number_format($data->act_feb, 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
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
                                        {{ $this->dataSumarryAlls()->links('pagination::bootstrap-5', data: ['pageName' => 'all_sites']) }}
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="site-tab-pane" role="tabpanel" aria-labelledby="site-tab"
                                tabindex="0">
                                <div class="ms-auto align-items-baseline d-flex">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto">
                                            <select id="jobsites" class="form-select form-select-sm mb-2"
                                                aria-label="Select Jobsite" wire:model="site_filter">
                                                @foreach ($jobsites as $jobsite)
                                                    <option value="{{ $jobsite->id }}">
                                                        {{ $jobsite->code }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <select id="depts" class="form-select form-select-sm mb-2"
                                                aria-label="Select Dept" wire:model="dept_filter">
                                                @foreach ($departements as $depts)
                                                    <option value="{{ $depts->id }}">{{ $depts->code }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <input type="text" id="txtYear" class="form-control form-control-sm mb-2"
                                                placeholder="Isi Tahun" wire:model="year_filter" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>