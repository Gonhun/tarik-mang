<div>
    <style>
        th:nth-child(-n+3),
        td:nth-child(-n+3) {
            position: sticky;
            left: 0;
            background-color: #fafafa;
            z-index: 2;
        }

        th:nth-child(2) {
            left: 80px;
        }

        th:nth-child(3) {
            left: 160px;
        }

        td:nth-child(2) {
            left: 80px;
        }

        td:nth-child(3) {
            left: 160px;
        }
    </style>
    <div class="card">
        <div class="card-header d-flex Justify-content-between pb-0">
            <h5 class="mb-0">Pivot Chart of Account</h5>
            <div class="ms-auto align-items-baseline d-flex">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <select id="jobsites" class="form-select form-select-sm mb-2" aria-label="Select Jobsite"
                            wire:model="site_filter">
                            @foreach ($jobsites as $jobsite)
                                <option value="{{ $jobsite->id }}">
                                    {{ $jobsite->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <select id="depts" class="form-select form-select-sm mb-2" aria-label="Select Dept"
                            wire:model="dept_filter">
                            @foreach ($departements as $depts)
                                <option value="{{ $depts->id }}">{{ $depts->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <input type="text" id="txtYear" class="form-control form-control-sm mb-2"
                            placeholder="Isi Tahun" wire:model="year_filter" />
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary btn-sm mb-2" id="btnAdd" wire:click="datas">
                            <i class="bx bx-bxs-cog me-1"></i> Generate Pivot
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive p-1">
                <table class="table table-bordered align-items-center mb-0 flex-nowrap">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" rowspan="2">Action</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" rowspan="2">Cost Element
                            </th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" rowspan="2">
                                Description
                            </th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Jan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Feb</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Mar</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Apr</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">May</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Jun</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Jul</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Aug</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Sep</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Okt</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Nov</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Dec</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" colspan="2">Total</th>
                        </tr>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($this->datas())
                            @foreach ($this->datas() as $data)
                            <tr>
                                <td class="w-auto">
                                    <button class="btn btn-sm btn-outline-success shadow" type="button"
                                        wire:click.prevent="openModal({{ $data->id == null ? '0' : $data->id }},'{{ $data->cost_element_number }}', '{{ $data->description }}')"
                                        data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $data->cost_element_number ?? 'N/A' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $data->description ?? 'N/A' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_jan ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_jan ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_feb ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_feb ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_mar ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_mar ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_apr ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_apr ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_may ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_may ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_jun ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_jun ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_jul ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_jul ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_aug ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_aug ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_sep ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_sep ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_oct ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_oct ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_nov ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_nov ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->plan_dec ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">Rp {{ $data->act_dec ?? '0' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">
                                        Rp {{ 
                                            ($data->plan_jan ?? 0) +
                                            ($data->plan_feb ?? 0) +
                                            ($data->plan_mar ?? 0) +
                                            ($data->plan_apr ?? 0) +
                                            ($data->plan_may ?? 0) +
                                            ($data->plan_jun ?? 0) +
                                            ($data->plan_jul ?? 0) +
                                            ($data->plan_aug ?? 0) +
                                            ($data->plan_sep ?? 0) +
                                            ($data->plan_oct ?? 0) +
                                            ($data->plan_nov ?? 0) +
                                            ($data->plan_dec ?? 0) 
                                    }}
                                    </p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">
                                        Rp {{ 
                                            ($data->act_jan ?? 0) +
                                            ($data->act_feb ?? 0) +
                                            ($data->act_mar ?? 0) +
                                            ($data->act_apr ?? 0) +
                                            ($data->act_may ?? 0) +
                                            ($data->act_jun ?? 0) +
                                            ($data->act_jul ?? 0) +
                                            ($data->act_aug ?? 0) +
                                            ($data->act_sep ?? 0) +
                                            ($data->act_oct ?? 0) +
                                            ($data->act_nov ?? 0) +
                                            ($data->act_dec ?? 0) 
                                    }}
                                    </p>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td scope="row" colspan="16">No found any data</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($this->isModalOpen)
        <!-- Modal Tambah Coa Baru -->
        <div class="modal fade show d-block" id="modalTambahCoa" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTambahCoaLabel">Tambah Coa Baru</h5>
                        <button type="button" wire:click.prevent="closeModal" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="store">
                            <div class="row">
                                <div class="col-sm-4 mb-3">
                                    <label for="costElement" class="form-label">Cost Element</label>
                                    <input type="text" class="form-control" id="costElement" name="costElement" wire:model="cost_element_number" @if ($this->cost_element_number) disabled @endif>
                                </div>
                                <div class="col-sm-8 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="description" name="description" wire:model="description" @if ($this->cost_element_number) disabled @endif>
                                </div>
                            </div>
                            <div class="row">
                                <span>Detail Cost Plan & Actual
                                    <hr />
                                </span>
                            </div>
                            <div class="row">
                                <div class="col-sm-3 mb-3">
                                    <label for="planJan" class="form-label">Plan January</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planJan" name="planJan" wire:model="planJan">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actJan" class="form-label">Actual January</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actJan" name="actJan" wire:model="actJan">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="planFeb" class="form-label">Plan February</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planFeb" name="planFeb" wire:model="planFeb">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actFeb" class="form-label">Actual February</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actFeb" name="actFeb" wire:model="actFeb">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3 mb-3">
                                    <label for="planMar" class="form-label">Plan March</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planMar" name="planMar" wire:model="planMar">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actMar" class="form-label">Actual March</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actMar" name="actMar" wire:model="actMar">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="planApr" class="form-label">Plan April</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planApr" name="planApr" wire:model="planApr">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actApr" class="form-label">Actual April</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actApr" name="actApr" wire:model="actApr">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3 mb-3">
                                    <label for="planMay" class="form-label">Plan May</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planMay" name="planMay" wire:model="planMay">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actMay" class="form-label">Actual May</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actMay" name="actMay" wire:model="actMay">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="planJun" class="form-label">Plan June</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planJun" name="planJun" wire:model="planJun">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actJun" class="form-label">Actual June</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actJun" name="actJun" wire:model="actJun">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3 mb-3">
                                    <label for="planJul" class="form-label">Plan July</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planJul" name="planJul" wire:model="planJul">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actJul" class="form-label">Actual July</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actJul" name="actJul" wire:model="actJul">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="planAug" class="form-label">Plan August</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planAug" name="planAug" wire:model="planAug">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actAug" class="form-label">Actual August</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actAug" name="actAug" wire:model="actAug">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3 mb-3">
                                    <label for="planSep" class="form-label">Plan September</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planSep" name="planSep" wire:model="planSep">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actSep" class="form-label">Actual September</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actSep" name="actSep" wire:model="actSep">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="planOct" class="form-label">Plan October</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planOct" name="planOct" wire:model="planOct">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actOct" class="form-label">Actual October</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actOct" name="actOct" wire:model="actOct">
                                    </div>
                                </div>  
                            </div>
                            <div class="row">
                                <div class="col-sm-3 mb-3">
                                    <label for="planNov" class="form-label">Plan November</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planNov" name="planNov" wire:model="planNov">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actNov" class="form-label">Actual November</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actNov" name="actNov" wire:model="actNov">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="planDec" class="form-label">Plan December</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="planDec" name="planDec" wire:model="planDec">
                                    </div>
                                </div>
                                <div class="col-sm-3 mb-3">
                                    <label for="actDec" class="form-label">Actual December</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="actDec" name="actDec" wire:model="actDec">
                                    </div>
                                </div>  
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <span>Simpan</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>