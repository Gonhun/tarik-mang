@php
    $listTargets = 'search,statusFilter,gotoPage,nextPage,previousPage';
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat('D MMM YYYY') : '-';
    $legacy = ['Reported' => 'secondary', 'PendingAssessment' => 'secondary', 'AwaitingParts' => 'info', 'BeyondEconomicRepair' => 'danger', 'RepairComplete' => 'success', 'AssessmentComplete' => 'secondary'];
    $statusColor = fn ($s) => $statuses[$s][1] ?? $legacy[$s] ?? 'secondary';
@endphp
<div>
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="mb-1">Laporan kerusakan aset</h4>
                <p class="text-muted mb-0">Lacak perbaikan sampai selesai. Rusak berat otomatis diajukan disposal.</p>
            </div>
            <button wire:click="create" wire:loading.attr="disabled" wire:target="create" class="btn btn-danger btn-sm">
                <span wire:loading.remove wire:target="create"><i class="bx bx-plus me-1"></i>Buat laporan</span>
                <span wire:loading wire:target="create"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Membuka...</span>
            </button>
        </div>

        {{-- Filter status --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" wire:click="$set('statusFilter', 'open')" class="btn btn-sm {{ $statusFilter === 'open' ? 'btn-warning' : 'btn-outline-warning' }}">
                Aktif <span class="badge bg-label-warning ms-1">{{ $openCount }}</span>
            </button>
            <button type="button" wire:click="$set('statusFilter', '')" class="btn btn-sm {{ $statusFilter === '' ? 'btn-dark' : 'btn-outline-secondary' }}">
                Semua <span class="badge bg-label-secondary ms-1">{{ $statusCounts->sum() }}</span>
            </button>
            @foreach ($statusCounts as $status => $total)
                @php $c = $statusColor($status); @endphp
                <button type="button" wire:click="$set('statusFilter', @js($status))" class="btn btn-sm {{ $statusFilter === $status ? 'btn-' . $c : 'btn-outline-' . $c }}">
                    {{ $statuses[$status][0] ?? $status }} <span class="badge bg-label-{{ $c }} ms-1">{{ $total }}</span>
                </button>
            @endforeach
        </div>

        <div class="card">
            <div class="card-body border-bottom py-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-8">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="Deskripsi, no. aset, SN, pelapor..." aria-label="Cari laporan">
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <span wire:loading wire:target="{{ $listTargets }}" class="spinner-border spinner-border-sm text-primary" role="status" aria-label="Memuat"></span>
                    </div>
                </div>
            </div>

            <div class="position-relative">
                <div wire:loading.flex wire:target="{{ $listTargets }}" class="position-absolute top-0 start-0 w-100 h-100 align-items-start justify-content-center pt-5" style="background: rgba(255,255,255,.65); z-index: 5;">
                    <span class="badge bg-white text-primary shadow-sm px-3 py-2"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Memuat data...</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Aset</th><th>Kerusakan</th><th>Pelapor</th><th>Status</th><th class="text-end"></th></tr>
                        </thead>
                        <tbody>
                            @forelse ($damageReports as $report)
                                @php $isFinal = in_array($report->damage_status, \App\Livewire\Master\Asset\Damages\ManageDamageReports::FINAL_STATUSES); @endphp
                                <tr wire:key="dmg-{{ $report->id }}">
                                    <td>
                                        @if ($report->asset)
                                            <a href="{{ route('assets.show', $report->asset->asset_number) }}" class="fw-semibold">{{ $report->asset->asset_number }}</a>
                                            <small class="d-block text-muted">{{ $report->asset->assetType->name ?? '-' }} · {{ $report->asset->assetSeries->name ?? '-' }}</small>
                                            <small class="d-block text-muted">{{ $report->asset->jobsite->code ?? '-' }} · SN {{ $report->asset->serial_number ?: '-' }}</small>
                                        @else
                                            <span class="text-danger small">Aset tidak ditemukan</span>
                                        @endif
                                    </td>
                                    <td style="max-width: 320px">
                                        <span class="d-block text-truncate" title="{{ $report->damage_description }}">{{ $report->damage_description }}</span>
                                        <small class="text-muted">#{{ $report->id }} · {{ $fmt($report->report_date) }}</small>
                                        @if ($report->repair_cost)
                                            <small class="text-muted"> · Rp {{ number_format($report->repair_cost, 0, ',', '.') }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $report->reporter->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-label-{{ $statusColor($report->damage_status) }}">{{ $statuses[$report->damage_status][0] ?? $report->damage_status }}</span>
                                        @if ($report->repaired_date)
                                            <small class="d-block text-muted">Selesai {{ $fmt($report->repaired_date) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @unless ($isFinal)
                                            <button wire:click="edit({{ $report->id }})" wire:loading.attr="disabled" wire:target="edit({{ $report->id }})" class="btn btn-sm btn-outline-primary">
                                                <span wire:loading.remove wire:target="edit({{ $report->id }})"><i class="bx bx-edit-alt me-1"></i>Perbarui</span>
                                                <span wire:loading wire:target="edit({{ $report->id }})"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                            </button>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-5"><i class="bx bx-wrench fs-1 d-block mb-2"></i>Tidak ada laporan yang cocok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-body border-top">{{ $damageReports->links() }}</div>
        </div>
    </div>

    {{-- Modal laporan --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="damageTitle" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="damageTitle">{{ $damage_report_id ? 'Perbarui laporan #' . $damage_report_id : 'Laporan kerusakan baru' }}</h5>
                        <button type="button" class="btn-close" wire:click="closeModal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Riwayat singkat laporan yang sedang diperbarui --}}
                        @if ($editingReport)
                            <div class="d-flex flex-wrap gap-3 small text-muted mb-3">
                                <span><i class="bx bx-calendar"></i> Dilaporkan {{ $fmt($editingReport->report_date) }}</span>
                                <span><i class="bx bx-user"></i> Dibuat {{ $editingReport->creator->name ?? '-' }}</span>
                                <span><i class="bx bx-history"></i> Status aset sebelum rusak: <strong>{{ $editingReport->asset_status_before_report ?: '-' }}</strong></span>
                                <span><i class="bx bx-edit"></i> Terakhir diubah {{ $editingReport->updated_at?->locale('id')->diffForHumans() }}{{ $editingReport->updater ? ' oleh ' . $editingReport->updater->name : '' }}</span>
                            </div>
                        @endif
                        {{-- Aset --}}
                        @if ($damage_report_id || $asset_id && $selectedAsset)
                            <div class="border rounded p-3 mb-3 d-flex justify-content-between align-items-start gap-2">
                                <div class="small">
                                    <span class="fw-semibold">{{ $selectedAsset->asset_number ?? '-' }}</span>
                                    <span class="badge bg-label-secondary ms-1">{{ $selectedAsset->status ?? '' }}</span>
                                    <span class="d-block text-muted">{{ $selectedAsset->assetType->name ?? '-' }} · {{ $selectedAsset->assetSeries->name ?? '-' }} · SN {{ $selectedAsset->serial_number ?? '-' }}</span>
                                    <span class="d-block text-muted">{{ $selectedAsset->jobsite->code ?? '-' }}{{ $selectedAsset?->holder ? ' · dipegang ' . $selectedAsset->holder->name : '' }}</span>
                                </div>
                                @unless ($damage_report_id)
                                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$set('asset_id', null)">Ganti</button>
                                @endunless
                            </div>
                        @else
                            <div class="row g-2 mb-2">
                                <div class="col-12 col-sm-5">
                                    <label for="dmg_site" class="form-label">Site <span class="text-danger">*</span></label>
                                    <select id="dmg_site" wire:model.live="from_jobsite_id" class="form-select">
                                        <option value="">-- Pilih site --</option>
                                        @foreach ($allJobsites as $j)
                                            <option value="{{ $j->id }}">{{ $j->code }} · {{ $j->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-sm-7">
                                    <label for="dmg_asset_search" class="form-label">Cari aset <span class="text-danger">*</span></label>
                                    <input type="search" id="dmg_asset_search" wire:model.live.debounce.400ms="searchAsset" class="form-control" placeholder="No. aset, SN, seri..." @disabled(!$from_jobsite_id)>
                                </div>
                            </div>
                            @error('asset_id') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                            <div class="border rounded mb-3 position-relative" style="max-height: 220px; overflow-y: auto;">
                                <div wire:loading.flex wire:target="from_jobsite_id,searchAsset" class="position-absolute top-0 start-0 w-100 h-100 align-items-center justify-content-center" style="background: rgba(255,255,255,.7)">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                </div>
                                @if (!$from_jobsite_id)
                                    <p class="text-muted small text-center py-3 mb-0">Pilih site terlebih dahulu.</p>
                                @else
                                    @forelse ($assetOptions as $a)
                                        <button type="button" wire:click="$set('asset_id', {{ $a->id }})" class="list-group-item list-group-item-action border-0 border-bottom w-100 text-start px-3 py-2">
                                            <span class="fw-medium">{{ $a->asset_number }}</span>
                                            <span class="badge bg-label-secondary ms-1">{{ $a->status }}</span>
                                            <small class="d-block text-muted text-truncate">{{ $a->assetType->name ?? '-' }} · {{ $a->assetSeries->name ?? '-' }} · SN {{ $a->serial_number ?: '-' }}{{ $a->holder ? ' · ' . $a->holder->name : '' }}</small>
                                        </button>
                                    @empty
                                        <p class="text-muted small text-center py-3 mb-0">Tidak ada aset yang bisa dilaporkan (aset dengan laporan aktif tidak ditampilkan).</p>
                                    @endforelse
                                @endif
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label">Pelapor <span class="text-danger">*</span></label>
                                @if ($reporter && !$reporterSearch)
                                    <div class="input-group">
                                        <span class="form-control text-truncate">{{ $reporter->name }} <small class="text-muted">({{ $reporter->nik }})</small></span>
                                        @unless ($damage_report_id)
                                            <button class="btn btn-outline-secondary" type="button" wire:click="$set('reporterSearch', ' ')">Ganti</button>
                                        @endunless
                                    </div>
                                @else
                                    <input type="search" wire:model.live.debounce.400ms="reporterSearch" class="form-control" placeholder="Ketik nama / NIK (min. 2 huruf)">
                                    @if ($reporterOptions->isNotEmpty())
                                        <div class="list-group mt-1 shadow-sm">
                                            @foreach ($reporterOptions as $u)
                                                <button type="button" class="list-group-item list-group-item-action py-1" wire:click="chooseReporter({{ $u->id }})">{{ $u->name }} <small class="text-muted">{{ $u->nik }}</small></button>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                                @error('reported_by_user_id') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6 col-sm-3">
                                <label for="dmg_date" class="form-label">Tgl. lapor <span class="text-danger">*</span></label>
                                <input type="date" id="dmg_date" wire:model="report_date" max="{{ now()->format('Y-m-d') }}" class="form-control @error('report_date') is-invalid @enderror" @disabled($damage_report_id)>
                                @error('report_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6 col-sm-3">
                                <label for="dmg_status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select id="dmg_status" wire:model.live="damage_status" class="form-select @error('damage_status') is-invalid @enderror">
                                    @foreach ($statuses as $value => [$label])
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('damage_status') <div class="col-12"><div class="alert alert-danger small py-2 mb-0">{{ $message }}</div></div> @enderror
                            @if ($damage_status === 'Beyond Economic Repair')
                                <div class="col-12"><div class="alert alert-warning small py-2 mb-0"><i class="bx bx-info-circle"></i> Menyimpan dengan status ini membuat pengajuan disposal (perlu approval) dan aset menjadi <strong>Pre Disposed</strong>. Laporan tidak bisa diubah lagi.</div></div>
                            @elseif ($damage_status === 'Closed')
                                <div class="col-12"><div class="alert alert-success small py-2 mb-0"><i class="bx bx-info-circle"></i> Aset dikembalikan ke status sebelum rusak. Isi catatan & tanggal selesai perbaikan.</div></div>
                            @endif

                            <div class="col-12">
                                <label for="dmg_desc" class="form-label">Deskripsi kerusakan <span class="text-danger">*</span></label>
                                <textarea id="dmg_desc" wire:model="damage_description" rows="3" class="form-control @error('damage_description') is-invalid @enderror"></textarea>
                                @error('damage_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="dmg_assess" class="form-label">Catatan penilaian</label>
                                <textarea id="dmg_assess" wire:model="assessment_notes" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="dmg_repair" class="form-label">Catatan perbaikan @if ($damage_status === 'Closed')<span class="text-danger">*</span>@endif</label>
                                <textarea id="dmg_repair" wire:model="repair_notes" rows="2" class="form-control @error('repair_notes') is-invalid @enderror"></textarea>
                                @error('repair_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6">
                                <label for="dmg_cost" class="form-label">Biaya perbaikan (Rp)</label>
                                <input type="number" min="0" step="any" id="dmg_cost" wire:model="repair_cost" class="form-control @error('repair_cost') is-invalid @enderror">
                                @error('repair_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6">
                                <label for="dmg_done" class="form-label">Tgl. selesai @if ($damage_status === 'Closed')<span class="text-danger">*</span>@endif</label>
                                <input type="date" id="dmg_done" wire:model="repaired_date" class="form-control @error('repaired_date') is-invalid @enderror">
                                @error('repaired_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @foreach (['photo1' => ['Foto 1', $existingPhoto1Path], 'photo2' => ['Foto 2', $existingPhoto2Path]] as $prop => [$label, $existing])
                                <div class="col-12 col-sm-6">
                                    <label for="dmg_{{ $prop }}" class="form-label">{{ $label }} <span class="text-muted fw-normal">(opsional)</span></label>
                                    <input type="file" id="dmg_{{ $prop }}" wire:model="{{ $prop }}" accept=".jpg,.jpeg,.png,.pdf" class="form-control @error($prop) is-invalid @enderror">
                                    <div wire:loading wire:target="{{ $prop }}" class="small text-muted mt-1"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Mengunggah...</div>
                                    @error($prop) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @if ($this->$prop && method_exists($this->$prop, 'isPreviewable') && $this->$prop->isPreviewable())
                                        <img src="{{ $this->$prop->temporaryUrl() }}" class="img-thumbnail mt-2" style="max-height: 90px" alt="Pratinjau {{ $label }}">
                                    @elseif ($existing)
                                        <a href="{{ Storage::url($existing) }}" target="_blank" class="d-inline-block mt-2" title="Buka {{ strtolower($label) }}">
                                            @if (preg_match('/\.(jpe?g|png)$/i', $existing))
                                                <img src="{{ Storage::url($existing) }}" class="img-thumbnail" style="max-height: 90px" alt="{{ $label }} tersimpan">
                                            @else
                                                <span class="small"><i class="bx bx-file"></i> {{ $label }} tersimpan</span>
                                            @endif
                                        </a>
                                        <small class="d-block text-muted">Unggah file baru untuk mengganti.</small>
                                    @endif
                                </div>
                            @endforeach
                            <div class="col-12">
                                <label for="dmg_doc" class="form-label">Dokumen pendukung <span class="text-muted fw-normal">(opsional, mis. BA kerusakan / invoice servis)</span></label>
                                <input type="file" id="dmg_doc" wire:model="other_document" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" class="form-control @error('other_document') is-invalid @enderror">
                                <div wire:loading wire:target="other_document" class="small text-muted mt-1"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Mengunggah...</div>
                                @error('other_document') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if ($existingOtherDocumentPath)
                                    <a href="{{ Storage::url($existingOtherDocumentPath) }}" target="_blank" class="small d-inline-block mt-1"><i class="bx bx-paperclip"></i> {{ $existingOtherDocumentName ?: 'Dokumen tersimpan' }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeModal">Batal</button>
                        <button type="button" wire:click="store" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store,photo1,photo2,other_document">
                            <span wire:loading.remove wire:target="store"><i class="bx bx-save me-1"></i>Simpan laporan</span>
                            <span wire:loading wire:target="store"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
