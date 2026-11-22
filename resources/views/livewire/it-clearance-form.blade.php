<div>
    <div class="container my-5">
        <div class="card shadow-sm">
            <div class="card-body p-5">
                @if (session()->has('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif

                <div class="text-center mb-4">
                    <h3 class="fw-bold">FORM CHECKLIST CLEARING IT</h3>
                    <p class="text-muted">PT BINA SARANA SUKSES</p>
                </div>

                <hr>
                <h5 class="fw-bold text-primary mb-3">INFORMASI KARYAWAN</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <select wire:model.live="userId" class="form-select @error('userId') is-invalid @enderror">
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach($allUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('userId') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">NIK / ID Karyawan</label>
                        <input type="text" wire:model="nik" class="form-control" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Departemen</label>
                        <input type="text" wire:model="department" class="form-control" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jabatan</label>
                        <input type="text" wire:model="position" class="form-control" readonly>
                    </div>
                     <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Resign</label>
                        <input type="date" wire:model.defer="resignDate" class="form-control @error('resignDate') is-invalid @enderror">
                        @error('resignDate') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>

                <hr>
                <h5 class="fw-bold text-primary mb-3">CHECKLIST ASET & AKSES IT</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Item / Akses</th>
                                <th style="width: 15%;">IT (V/X/N/A)</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($checklistItems as $index => $item)
                                <tr wire:key="checklist-item-{{ $index }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $item['description'] }}</td>
                                    <td>
                                        <select wire:model.defer="checklistItems.{{ $index }}.status" class="form-select form-select-sm">
                                            <option value="V">V</option>
                                            <option value="X">X</option>
                                            <option value="N/A">N/A</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" wire:model.defer="checklistItems.{{ $index }}.notes" class="form-control form-control-sm">
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Pilih karyawan untuk menampilkan checklist.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label">Catatan Tambahan dari Tim IT:</label>
                    <textarea wire:model.defer="additionalNotes" class="form-control" rows="3"></textarea>
                </div>

                <hr>
                <div class="row mt-4">
                    <div class="col-md-4 text-center">
                        <label class="form-label">Dilakukan Oleh</label>
                        <select wire:model.defer="checkedByUserId" class="form-select @error('checkedByUserId') is-invalid @enderror">
                             <option value="">-- Pilih Staff IT --</option>
                             @foreach($itUsers as $user) <option value="{{ $user->id }}">{{ $user->name }}</option> @endforeach
                        </select>
                        <div class="mt-2"><small class="text-muted">Staff IT</small></div>
                        @error('checkedByUserId') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4 text-center">
                        <label class="form-label">Diketahui Oleh</label>
                         <select wire:model.defer="knownByUserId" class="form-select @error('knownByUserId') is-invalid @enderror">
                             <option value="">-- Pilih Kasi IT --</option>
                             @foreach($kasiItUsers as $user) <option value="{{ $user->id }}">{{ $user->name }}</option> @endforeach
                        </select>
                        <div class="mt-2"><small class="text-muted">Kasi IT</small></div>
                        @error('knownByUserId') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4 text-center">
                        <label class="form-label">Disetujui Oleh</label>
                         <select wire:model.defer="approvedByUserId" class="form-select @error('approvedByUserId') is-invalid @enderror">
                             <option value="">-- Pilih Kabag IC --</option>
                             @foreach($kabagIcUsers as $user) <option value="{{ $user->id }}">{{ $user->name }}</option> @endforeach
                        </select>
                        <div class="mt-2"><small class="text-muted">Kabag IC</small></div>
                        @error('approvedByUserId') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="text-end mt-5">
                    <button wire:click="saveClearance" class="btn btn-primary">
                        <span wire:loading.remove wire:target="saveClearance">Simpan Form</span>
                        <span wire:loading wire:target="saveClearance">Menyimpan...</span>
                    </button>
                    <a href="#" class="btn btn-secondary">Cetak</a>
                </div>
            </div>
        </div>
    </div>
</div>