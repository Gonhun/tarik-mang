<div>
    <div class="container-fluid py-4">
        @if ($requestsAwaitingMyAction->isNotEmpty())
            <div class="card border-0 shadow mb-4 position-relative overflow-hidden" style="background: linear-gradient(90deg, #fffbe6 0%, #fff7d6 100%);">
                {{-- Illustration --}}
                <div class="position-absolute top-0 end-0 d-none d-md-block" style="z-index:0; width: 180px;  pointer-events: none;">
                    <img src="{{ asset('assets/img/illustrations/man-with-laptop.png') }}" alt="Reminder Illustration" class="img-fluid" style="max-height: 160px;">
                </div>
                <div class="card-header d-flex align-items-center" style="background: transparent; border-bottom: none; z-index:1; position:relative;">
                    <div class="d-flex align-items-center">
                        <span class="me-3" style="font-size: 2.5rem; color: #f59e42;">
                            <img src="https://cdn-icons-png.flaticon.com/512/1827/1827349.png" alt="Bell" style="width: 2.5rem; height: 2.5rem;">
                        </span>
                        <div>
                            <h4 class="mb-0 fw-bold" style="color: #f59e42; letter-spacing: 0.5px;">
                                🚨 Tindakan Diperlukan!
                            </h4>
                            <small class="text-muted" style="font-size: 1.05em;">
                                Ada <span class="fw-bold text-warning">{{ $requestsAwaitingMyAction->count() }}</span> pengajuan yang menunggu aksi Anda.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="list-group list-group-flush" style="z-index:1; position:relative;">
                    @foreach ($requestsAwaitingMyAction as $request)
                        <a href="{{ route('asset-requests.show', $request->id) }}" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 py-3" 
                           style="transition: background 0.2s; background: rgba(255,255,255,0.85); border-radius: 0.5rem; margin-bottom: 0.5rem; box-shadow: 0 2px 8px rgba(245,158,66,0.07);">
                            <div class="d-flex align-items-center">
                                <span class="me-3 d-none d-md-block">
                                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Request" style="width: 38px; height: 38px;">
                                </span>
                                <div>
                                    <span class="fw-bold" style="font-size: 1.15rem; color: #f59e42;">
                                        <i class="fas fa-hashtag me-1"></i>{{ $request->request_number }}
                                    </span>
                                    <div class="d-flex flex-wrap align-items-center mt-1">
                                        <span class="badge bg-light text-dark me-2 mb-1" style="font-size: 0.95em;">
                                            <i class="fas fa-user me-1"></i>{{ $request->requester->name ?? 'N/A' }}
                                        </span>
                                        <span class="badge bg-light text-dark mb-1" style="font-size: 0.95em;">
                                            <i class="fas fa-calendar-alt me-1"></i>{{ $request->request_date->isoFormat('D MMM YYYY') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <span class="btn btn-sm btn-warning fw-bold px-3 py-2 shadow-sm" style="font-size: 1em; border-radius: 1.5rem;">
                                <img src="https://cdn-icons-png.flaticon.com/512/992/992651.png" alt="Action" style="width: 18px; height: 18px; margin-right: 6px; margin-top: -2px;"> Proses Sekarang
                            </span>
                        </a>
                    @endforeach
                </div>
                <div class="px-4 pb-3 pt-2" style="z-index:1; position:relative;">
                    <div class="alert alert-warning mb-0 d-flex align-items-center" style="background: #fff3cd; border: none;">
                        <img src="https://cdn-icons-png.flaticon.com/512/595/595067.png" alt="Attention" style="width: 28px; height: 28px;" class="me-2">
                        <span class="fw-semibold" style="color: #b8860b;">Segera proses pengajuan agar workflow berjalan lancar!</span>
                    </div>
                </div>
            </div>
        @endif
        <div class="card">
            <div class="card-header pb-0">
                @if($isItUser)
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0"></h5>
                    <a href="{{ route('asset-requests.create') }}" class="btn btn-sm btn-primary mb-0">
                        <i class="fas fa-plus me-1"></i> Buat Pengajuan Baru
                    </a>
                </div>
                    {{-- Tampilan Header untuk User IT dengan Tab --}}
                    <div class="nav-wrapper position-relative end-0">
                        <ul class="nav nav-pills nav-fill p-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link mb-0 px-0 py-1 {{ $activeTab === 'requests' ? 'active' : '' }}" 
                                   wire:click.prevent="changeTab('requests')" href="#" role="tab">
                                    <i class="fas fa-tasks me-1"></i> Pengajuan Aktif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link mb-0 px-0 py-1 {{ $activeTab === 'history' ? 'active' : '' }}" 
                                   wire:click.prevent="changeTab('history')" href="#" role="tab">
                                    <i class="fas fa-archive me-1"></i> History Pengajuan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link mb-0 px-0 py-1 {{ $activeTab === 'logs' ? 'active' : '' }}" 
                                   wire:click.prevent="changeTab('logs')" href="#" role="tab">
                                    <i class="fas fa-history me-1"></i> Log Audit
                                </a>
                            </li>
                        </ul>
                    </div>
                @else
                    {{-- Tampilan Header untuk User Biasa --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Riwayat Pengajuan Aset Saya</h5>
                        <a href="{{ route('asset-requests.create') }}" class="btn btn-sm btn-primary mb-0">
                            <i class="fas fa-plus me-1"></i> Buat Pengajuan Baru
                        </a>
                    </div>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Cari...">
                </div>

                {{-- KONTEN TAB --}}
                @if($isItUser)
                    {{-- Tampilan untuk IT --}}
                    <div wire:key="it-view">
                        @if ($activeTab === 'requests')
                            @include('livewire.asset-requests.partials.request-table', ['requests' => $requests])
                        @elseif ($activeTab === 'history')
                            @include('livewire.asset-requests.partials.history-request-table', ['historyRequests' => $historyRequests])
                        @elseif ($activeTab === 'logs')
                            @include('livewire.asset-requests.partials.audit-log-table', ['auditLogs' => $auditLogs])
                        @endif
                    </div>
                @else
                    {{-- Tampilan untuk User Biasa --}}
                    <div wire:key="user-view">
                        @include('livewire.asset-requests.partials.request-table', ['requests' => $requests])
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>