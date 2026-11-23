<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <a href="{{ route('asset-requests.dashboard') }}" class="small text-muted"><i class="bx bx-chevron-left"></i>Pengajuan aset</a>
            <h4 class="mb-1 mt-1">Ajukan aset</h4>
            <p class="text-muted mb-0">Item standar jabatan {{ $forOther ? $user->name : 'Anda' }} sudah terisi otomatis. Lengkapi keperluan lalu kirim.</p>
        </div>
    </div>

    {{-- Ajukan untuk diri sendiri / karyawan lain satu departemen --}}
    @if ($canSubmitForOthers)
        <div class="card mb-4">
            {{-- q = teks yang diketik; baru dikirim ke server setelah minimal 4 karakter. --}}
            <div class="card-body" x-data="{ q: '', min: 4,
                    get len() { return this.q.trim().length },
                    push() { const v = this.len >= this.min ? this.q.trim() : ''; if (v !== this.$wire.beneficiarySearch) this.$wire.set('beneficiarySearch', v) } }">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="min-w-0">
                        <span class="small text-muted d-block">Ajukan untuk</span>
                        <span class="fw-semibold">{{ $user->name }}</span>
                        <span class="text-muted small">· {{ $user->nik ?? '-' }} · {{ $user->position->name ?? 'Jabatan belum diatur' }} · {{ $user->jobsite->code ?? '-' }}</span>
                        @if ($forOther)
                            <span class="badge bg-label-info ms-1">Atas nama karyawan lain</span>
                        @endif
                    </div>
                    @if ($forOther)
                        <button type="button" wire:click="submitForSelf" @click="q = ''" wire:loading.attr="disabled" wire:target="submitForSelf" class="btn btn-sm btn-outline-secondary">
                            <span wire:loading.remove wire:target="submitForSelf"><i class="bx bx-user me-1"></i></span>
                            <span wire:loading wire:target="submitForSelf" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Ajukan untuk saya sendiri
                        </button>
                    @endif
                </div>

                <div class="mt-3 position-relative">
                    <label for="beneficiary-search" class="form-label small mb-1">
                        {{ $forOther ? 'Ganti penerima' : 'Ajukan untuk karyawan lain' }}
                        <span class="text-muted">(departemen {{ $myDepartment ?? 'Anda' }})</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input id="beneficiary-search" type="search" x-model="q" @input.debounce.400ms="push()" @search="push()"
                               class="form-control" placeholder="Ketik nama atau NIK, minimal 4 karakter" autocomplete="off"
                               aria-describedby="beneficiary-hint">
                        <span class="input-group-text" wire:loading.flex wire:target="beneficiarySearch, selectBeneficiary" aria-hidden="true">
                            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                        </span>
                    </div>

                    {{-- Petunjuk sebelum 4 karakter --}}
                    <small id="beneficiary-hint" class="mt-1 text-muted" style="display: block" x-show="len > 0 && len < min" x-cloak aria-live="polite">
                        Ketik <span x-text="min - len"></span> karakter lagi untuk mulai mencari.
                    </small>

                    {{-- Sedang mencari / memilih --}}
                    <div class="list-group mt-1 shadow-sm" wire:loading.block wire:target="beneficiarySearch">
                        <div class="list-group-item small text-muted d-flex align-items-center gap-2">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mencari karyawan…
                        </div>
                    </div>
                    <div class="list-group mt-1 shadow-sm" wire:loading.block wire:target="selectBeneficiary">
                        <div class="list-group-item small text-muted d-flex align-items-center gap-2">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memuat data penerima…
                        </div>
                    </div>

                    @if (mb_strlen(trim($beneficiarySearch)) >= 4)
                        <div class="list-group mt-1 shadow-sm" wire:loading.remove wire:target="beneficiarySearch, selectBeneficiary">
                            @forelse ($beneficiaryOptions as $opt)
                                <button type="button" wire:key="beneficiary-{{ $opt->id }}" wire:click="selectBeneficiary({{ $opt->id }})" @click="q = ''"
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2">
                                    <span class="min-w-0">
                                        <span class="fw-medium">{{ $opt->name }}</span>
                                        <small class="text-muted d-block">{{ $opt->nik ?? '-' }} · {{ $opt->position->name ?? 'Jabatan belum diatur' }}</small>
                                    </span>
                                    <span class="badge bg-label-secondary">{{ $opt->jobsite->code ?? '-' }}</span>
                                </button>
                            @empty
                                <div class="list-group-item small text-muted">Tidak ada karyawan aktif di departemen Anda yang cocok.</div>
                            @endforelse
                        </div>
                    @endif
                    <small class="text-muted d-block mt-1">Item standar, nomor pengajuan, dan alur persetujuan mengikuti jabatan & site penerima. Aset nantinya diserahterimakan ke penerima.</small>
                </div>
            </div>
        </div>
    @endif

    @if (!$user->position)
        <div class="alert alert-danger d-flex gap-2" role="alert"><i class="bx bx-error-alt fs-4"></i><div><strong>Jabatan {{ $forOther ? $user->name : 'Anda' }} belum diatur.</strong> Hubungi tim IT/HR agar jabatan diisi sebelum mengajukan aset.</div></div>
    @elseif (!$hasJobdesc)
        <div class="alert alert-danger d-flex gap-2" role="alert"><i class="bx bx-error-alt fs-4"></i><div><strong>Jobdesk jabatan {{ $user->position->name }} belum diunggah.</strong> Pengajuan aset memerlukan jobdesk jabatan. Hubungi tim IT/HR.</div></div>
    @elseif (empty($items))
        {{-- Tidak ada kebutuhan standar --}}
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-3 align-items-start">
                    <span class="avatar flex-shrink-0"><span class="avatar-initial rounded bg-label-success"><i class="bx bx-check-circle fs-4"></i></span></span>
                    <div>
                        <h5 class="mb-1">Standar aset jabatan {{ $forOther ? $user->name : 'Anda' }} sudah terpenuhi</h5>
                        <p class="text-muted mb-3">Pengajuan aset mengikuti standar jabatan <strong>{{ $user->position->name }}</strong>. Saat ini tidak ada kebutuhan yang belum terpenuhi.</p>
                        @if ($standards->isNotEmpty())
                            <ul class="list-unstyled small mb-3">
                                @foreach ($standards as $st)
                                    <li><i class="bx {{ $st['fulfilled'] ? 'bx-check text-success' : 'bx-minus text-muted' }}"></i> {{ $st['label'] }} × {{ $st['quantity'] }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="small text-muted">Belum ada standar aset untuk jabatan ini. Hubungi tim IT bila {{ $forOther ? 'karyawan ini' : 'Anda' }} membutuhkan aset.</p>
                        @endif
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('my.asset') }}" class="btn btn-sm btn-outline-primary">Lihat aset saya</a>
                            <a href="{{ route('asset-requests.dashboard') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        @if ($workflowWarning)
            <div class="alert alert-warning d-flex gap-2" role="alert"><i class="bx bx-error fs-4 flex-shrink-0"></i><div><strong>Pengajuan belum bisa dikirim.</strong><br>{{ $workflowWarning }}</div></div>
        @endif
        @if (session()->has('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif

        <div class="row g-4">
            {{-- Kiri: item & keperluan --}}
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center pb-2">
                        <h5 class="card-title mb-0">1. Aset yang dibutuhkan</h5>
                        <button type="button" wire:click="resetFormAndLoadStandards" wire:confirm="Kembalikan daftar item ke standar jabatan? Item tambahan akan dihapus." class="btn btn-sm btn-link px-0">
                            <i class="bx bx-reset"></i> Reset ke standar
                        </button>
                    </div>
                    <div class="card-body">
                        @error('items') <div class="alert alert-danger py-2 small">{{ $message }}</div> @enderror

                        @foreach ($items as $index => $item)
                            @php
                                $std = !empty($item['is_standard']);
                                $brands = $catalog['brands']->where('asset_type_id', $item['asset_type_id']);
                                $series = $catalog['series']->where('asset_brand_id', $item['asset_brand_id']);
                                $typeName = $catalog['types']->firstWhere('id', $item['asset_type_id'])->name ?? null;
                                $seriesRow = $catalog['series']->firstWhere('id', $item['asset_series_id']);
                            @endphp
                            <div class="border rounded p-3 mb-3 {{ $std ? 'bg-lighter' : '' }}" wire:key="request-item-{{ $index }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold">
                                        Item {{ $index + 1 }}
                                        <span class="badge bg-label-{{ $std ? 'success' : 'warning' }} ms-1">{{ $std ? 'Standar jabatan' : 'Tambahan' }}</span>
                                    </span>
                                    @if (count($items) > 1)
                                        <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-sm btn-text-danger p-1" aria-label="Hapus item {{ $index + 1 }}"><i class="bx bx-trash"></i></button>
                                    @endif
                                </div>

                                @if ($std)
                                    {{-- Item standar: ringkas, tidak bisa diubah --}}
                                    <div class="small">
                                        <span class="fw-medium">{{ $typeName ?? '-' }} · {{ $seriesRow->name ?? '-' }}</span>
                                        <span class="text-muted"> × {{ $item['quantity'] }}</span>
                                        @if (!empty($item['item_description']))
                                            <span class="d-block text-muted">{{ $item['item_description'] }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="row g-2">
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small mb-1" for="it_type_{{ $index }}">Tipe <span class="text-danger">*</span></label>
                                            <select id="it_type_{{ $index }}" wire:model.live="items.{{ $index }}.asset_type_id" class="form-select form-select-sm @error('items.'.$index.'.asset_type_id') is-invalid @enderror">
                                                <option value="">-- Pilih tipe --</option>
                                                @foreach ($catalog['types'] as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="form-label small mb-1" for="it_brand_{{ $index }}">Merek <span class="text-danger">*</span></label>
                                            <select id="it_brand_{{ $index }}" wire:model.live="items.{{ $index }}.asset_brand_id" class="form-select form-select-sm @error('items.'.$index.'.asset_brand_id') is-invalid @enderror" @disabled(empty($item['asset_type_id']))>
                                                <option value="">-- Pilih merek --</option>
                                                @foreach ($brands as $brand)
                                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="form-label small mb-1" for="it_series_{{ $index }}">Seri <span class="text-danger">*</span></label>
                                            <select id="it_series_{{ $index }}" wire:model.live="items.{{ $index }}.asset_series_id" class="form-select form-select-sm @error('items.'.$index.'.asset_series_id') is-invalid @enderror" @disabled(empty($item['asset_brand_id']))>
                                                <option value="">-- Pilih seri --</option>
                                                @foreach ($series as $s)
                                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-4 col-md-2">
                                            <label class="form-label small mb-1" for="it_qty_{{ $index }}">Jumlah <span class="text-danger">*</span></label>
                                            <input id="it_qty_{{ $index }}" type="number" min="1" max="1000" wire:model.live.debounce.500ms="items.{{ $index }}.quantity" class="form-control form-control-sm @error('items.'.$index.'.quantity') is-invalid @enderror">
                                        </div>
                                        <div class="col-8 col-md-10">
                                            <label class="form-label small mb-1" for="it_desc_{{ $index }}">Spesifikasi / catatan</label>
                                            <input id="it_desc_{{ $index }}" type="text" wire:model="items.{{ $index }}.item_description" class="form-control form-control-sm" placeholder="{{ $seriesRow->specifications ?? 'Opsional' }}">
                                        </div>
                                    </div>
                                    @foreach (['asset_type_id', 'asset_brand_id', 'asset_series_id', 'quantity'] as $f)
                                        @error('items.'.$index.'.'.$f) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    @endforeach
                                @endif
                            </div>
                        @endforeach

                        <button type="button" wire:click="addItem" wire:loading.attr="disabled" wire:target="addItem" class="btn btn-sm btn-outline-primary">
                            <i class="bx bx-plus me-1"></i>Tambah item lain
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header pb-2"><h5 class="card-title mb-0">2. Keperluan</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="purpose" class="form-label">Untuk apa aset ini dibutuhkan? <span class="text-danger">*</span></label>
                                <textarea wire:model="purpose" id="purpose" rows="3" class="form-control @error('purpose') is-invalid @enderror" placeholder="Contoh: karyawan baru di site, pengganti laptop rusak, kebutuhan proyek..."></textarea>
                                @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="needed_date" class="form-label">Dibutuhkan tanggal <span class="text-danger">*</span></label>
                                <input type="date" wire:model="needed_date" id="needed_date" min="{{ now()->format('Y-m-d') }}" class="form-control @error('needed_date') is-invalid @enderror">
                                <small class="text-muted">Standar pengadaan ±14 hari.</small>
                                @error('needed_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="remarks" class="form-label">Catatan lain <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="text" wire:model="remarks" id="remarks" class="form-control @error('remarks') is-invalid @enderror">
                                @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <details class="mt-4" @if ($ba_damage || $ba_budget_outside || $errors->has('ba_damage') || $errors->has('ba_budget_outside')) open @endif>
                            <summary class="fw-medium" style="cursor: pointer">Lampiran berita acara <span class="text-muted fw-normal">(opsional — jika pengganti aset rusak atau di luar budget)</span></summary>
                            <div class="row g-3 mt-1">
                                @foreach (['ba_damage' => 'BA kerusakan', 'ba_budget_outside' => 'BA di luar budget'] as $prop => $label)
                                    <div class="col-12 col-md-6">
                                        <label for="{{ $prop }}" class="form-label small">{{ $label }}</label>
                                        <input type="file" id="{{ $prop }}" wire:model="{{ $prop }}" accept=".pdf,.doc,.docx,.png,.jpg" class="form-control form-control-sm @error($prop) is-invalid @enderror">
                                        <div wire:loading wire:target="{{ $prop }}" class="small text-muted mt-1"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Mengunggah...</div>
                                        @error($prop) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        @if ($this->$prop)
                                            <div class="small mt-1 d-flex gap-2">
                                                <span class="text-truncate">{{ $this->$prop->getClientOriginalName() }}</span>
                                                @if (method_exists($this->$prop, 'isPreviewable') && $this->$prop->isPreviewable())
                                                    <a href="{{ $this->$prop->temporaryUrl() }}" target="_blank">Lihat</a>
                                                @endif
                                                <a href="#" class="text-danger" wire:click.prevent="$set('{{ $prop }}', null)">Hapus</a>
                                            </div>
                                        @endif
                                        <small class="text-muted">PDF/DOC/JPG/PNG, maks. 2 MB.</small>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    </div>
                </div>
            </div>

            {{-- Kanan: ringkasan & kirim --}}
            <div class="col-lg-4">
                <div class="card position-sticky" style="top: 1rem">
                    <div class="card-header pb-2"><h5 class="card-title mb-0">Ringkasan</h5></div>
                    <div class="card-body">
                        <dl class="row small mb-3">
                            <dt class="col-5 text-muted fw-normal">Pemohon</dt><dd class="col-7">{{ $user->name }}</dd>
                            <dt class="col-5 text-muted fw-normal">Site</dt><dd class="col-7">{{ $user->jobsite->code ?? '-' }}</dd>
                            <dt class="col-5 text-muted fw-normal">Jumlah item</dt><dd class="col-7">{{ count($items) }} jenis · {{ $totalUnits }} unit</dd>
                        </dl>

                        <h6 class="small text-uppercase text-muted mb-2">Alur persetujuan</h6>
                        <ol class="small ps-3 mb-3">
                            <li class="mb-1">Validasi item oleh <strong>tim IT site</strong></li>
                            @forelse ($approvalPreview as $p)
                                <li class="mb-1">{{ $p['role'] }} <span class="text-muted">· {{ $p['user']->name }}</span></li>
                            @empty
                                <li class="text-danger">Belum ada alur persetujuan</li>
                            @endforelse
                        </ol>

                        <button type="button" wire:click="submitRequest" class="btn btn-primary w-100" wire:loading.attr="disabled" wire:target="submitRequest,ba_damage,ba_budget_outside" @disabled($workflowWarning)>
                            <span wire:loading.remove wire:target="submitRequest"><i class="bx bx-send me-1"></i>Kirim pengajuan</span>
                            <span wire:loading wire:target="submitRequest"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Mengirim...</span>
                        </button>
                        <small class="d-block text-muted text-center mt-2">{{ $forOther ? 'Anda dan ' . $user->name . ' akan menerima email konfirmasi.' : 'Anda akan menerima email konfirmasi.' }}</small>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
