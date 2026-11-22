<div>
    <div class="card">
        <div class="card-header d-flex Justify-content-between pb-0">
            <h5 class="mb-0">Pivot Chart of Account</h5>
            <div class="ms-auto align-items-baseline d-flex">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <select id="jobsites" class="form-select form-select-sm mb-2" aria-label="Select Jobsite">
                            @foreach ($jobsites as $jobsite)
                                <option value="{{ $jobsite->id }}" @selected($jobsite->id == Auth::user()->jobsite_id)>
                                    {{ $jobsite->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <input type="text" id="txtYear" class="form-control form-control-sm mb-2"
                            placeholder="Isi Tahun" />
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary btn-sm mb-2" id="btnAdd" wire:click="openModal()">
                            <i class="bx bx-bxs-cog me-1"></i> Generate Pivot
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive p-1">
                <table class="table table-bordered align-items-center mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Action</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dept</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Site</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Cost Element
                            </th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Description
                            </th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Feb</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Mar</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Apr</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">May</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jun</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jul</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aug</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Sep</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Okt</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nov</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dec</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>