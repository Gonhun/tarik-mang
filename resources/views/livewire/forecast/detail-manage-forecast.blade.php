<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6>
                            <a href="/forecast/manage" class="btn btn-sm btn-secondary">
                                <i class="bx bx-arrow-back"></i> Kembali
                            </a>
                            Detail Forecast
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Created ON</th>
                                        <th>Posting Date</th>
                                        <th>Time Of Entry</th>
                                        <th>Document</th>
                                        <th>Material</th>
                                        <th>Material Desc</th>
                                        <th>Cost Element</th>
                                        <th>Cost Center</th>
                                        <th>Cost Element Name</th>
                                        <th>Plant</th>
                                        <th>Total Quality</th>
                                        <th>Value in Rep</th>
                                        <th>PO text</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($this->datas()->Count() > 0)
                                        @foreach ($this->datas() as $data)
                                            <tr>
                                                <td>{{ $data->created_on }}</td>
                                                <td>{{ $data->posting_date }}</td>
                                                <td>{{ $data->time_of_entry }}</td>
                                                <td>{{ $data->document_number }}</td>
                                                <td>{{ $data->material }}</td>
                                                <td>{{ $data->material_desc }}</td>
                                                <td>{{ $data->cost_element }}</td>
                                                <td>{{ $data->cost_center }}</td>
                                                <td>{{ $data->cost_element_name }}</td>
                                                <td>{{ $data->plant }}</td>
                                                <td>{{ $data->total_quality }}</td>
                                                <td>{{ $data->val_in_rep }}</td>
                                                <td>{{ $data->po_text }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5" class="text-center">No data available</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            {{-- <div class="d-flex justify-content-between align-items-center mb-3">
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
                            </div>
                            <div class="px-4 pt-3">
                                {{ $this->datas()->links('pagination::bootstrap-5', ['pageName' => 'detail_page']) }}
                            </div> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>