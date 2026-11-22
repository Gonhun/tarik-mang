<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Fortigate API</h5>

                    {{-- Tombol Refresh --}}
                    @if ($step != 0)
                        <button wire:click="$set('step', 0)" class="btn btn-sm btn-outline-primary mb-3">
                            <span wire:loading.remove wire:target="fetchGroupData">
                                <i class="fas fa-sync-alt me-1"></i> Back
                            </span>
                            <span wire:loading wire:target="fetchGroupData">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Memuat...
                            </span>
                        </button>
                    @endif
                    
                </div>
            </div>
            <div class="card-body">
                {{-- Tampilkan pesan error jika ada --}}
                @if ($errorMessage)
                    <div class="alert alert-danger">{{ $errorMessage }}</div>
                @endif

                {{-- Tampilkan indikator loading saat data diambil --}}
                <div wire:loading wire:target="fetchGroupData" class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Mengambil data grup...</p>
                </div>
                <div wire:loading.remove wire:target="fetchGroupData">
                    @if ($step==0)
                            <div class="table-responsive">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No.</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Name</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Show</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0 px-3">1</p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">Monitoring</p>
                                            </td>
                                            <td>
                                                <button wire:click="fetchGroupData('monitor')" class="btn btn-sm btn-primary">
                                                    Show
                                                </button>
                                            </td>
                                            
                                        </tr>
                                        <tr>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0 px-3">2</p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">Logs</p>
                                            </td>
                                            <td>
                                                <button wire:click="fetchGroupData('log')" class="btn btn-sm btn-primary">
                                                    Show
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                    @elseif($step==1)
                        <div class="mb-3 row">
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="filterName" placeholder="Filter by Name" onkeyup="filterTable()" autocomplete="off">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="filterPath" placeholder="Filter by Path" onkeyup="filterTable()" autocomplete="off">
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="filterSupported" onchange="filterTable()">
                                    <option value="">All Supported</option>
                                    <option value="Supported">Supported</option>
                                    <option value="Not Supported">Not Supported</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0" id="groupTable">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No.</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Supported</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Path</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Name</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Action</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Request</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($groups as $index => $group)
                                        @if (!empty($group))
                                        {{-- Baris utama --}}
                                            <tr class="align-top border-bottom">
                                                <td>
                                                    <span class="text-sm font-semibold px-3">{{ $index + 1 }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $group['supported'] ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $group['supported'] ? 'Supported' : 'Not Supported' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-sm font-medium text-dark">{{ $group['path'] ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-sm">{{ $group['name'] ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-sm text-primary">{{ $group['action'] ?? '0' }}</span>
                                                </td>
                                                <td>
                                                    @if(isset($group['request']['parameters']) && is_array($group['request']['parameters']))
                                                        <ul class="list-unstyled mb-0 text-sm">
                                                            @foreach($group['request']['parameters'] as $param)
                                                                <li class="mb-1">
                                                                    <span class="fw-bold">{{ $param['name'] ?? 'N/A' }}</span>
                                                                    <span class="badge bg-info text-dark ms-1">{{ $param['type'] ?? 'N/A' }}</span><br>
                                                                    <small class="text-muted fst-italic">{{ $param['summary'] ?? '' }}</small>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-sm text-muted">No Params</span>
                                                    @endif
                                                </td>
                                                
                                            </tr>

                                            {{-- Baris tambahan untuk summary --}}
                                            @if(!empty($group['summary']))
                                            <tr class="border-bottom bg-light">
                                                <td colspan="6">
                                                    <div class="p-2 text-muted text-sm fst-italic border-start border-3 border-primary">
                                                        <strong>Summary:</strong> {{ $group['summary'] }}
                                                    </div>
                                                </td>
                                            </tr>
                                            @endif

                                        @endif
                                        
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4">
                                                @if(!$errorMessage) {{-- Hanya tampilkan jika tidak ada error --}}
                                                    <p class="text-muted mb-0">Tidak ada data grup yang ditemukan.</p>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                    
                                </tbody>
                            </table>
                        </div>
                        
                    @endif
                </div>
                
            </div>
        </div>
    </div>
    
</div>
@push('scripts')
<script>
    function filterTable() {
        var inputName = document.getElementById("filterName").value.toLowerCase();
        var inputPath = document.getElementById("filterPath").value.toLowerCase();
        var inputSupported = document.getElementById("filterSupported").value;
        var table = document.getElementById("groupTable");
        var trs = table.getElementsByTagName("tr");
        for (var i = 1; i < trs.length; i++) {
            var tds = trs[i].getElementsByTagName("td");
            if (tds.length < 6) continue; // skip summary row or empty
            var supported = tds[1].innerText.trim();
            var path = tds[2].innerText.toLowerCase();
            var name = tds[3].innerText.toLowerCase();
            var show = true;
            if (inputName && name.indexOf(inputName) === -1) show = false;
            if (inputPath && path.indexOf(inputPath) === -1) show = false;
            if (inputSupported && supported !== inputSupported) show = false;
            trs[i].style.display = show ? "" : "none";
            // Hide summary row if main row is hidden
            if (trs[i+1] && trs[i+1].getElementsByTagName("td").length === 1 && trs[i+1].getElementsByTagName("td")[0].colSpan == 6) {
                trs[i+1].style.display = show ? "" : "none";
            }
        }
    }
    </script>
@endpush