<div>
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">Form Checkout License {{$softwaredata->name}}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if($softwaredata->seats - $softwareUsed > 0)
                            <div class="row">
                                <div class="col-md-12 d-flex">
                                    <strong style="width:120px;">Manufacture</strong>
                                    <span class="mx-2">:</span>
                                    <span>{{$softwaredata->softwareManufacturer->name}}</span>
                                </div>
                                <div class="col-md-12 d-flex">
                                    <strong style="width:120px;">Category</strong>
                                    <span class="mx-2">:</span>
                                    <span>{{$softwaredata->softwareCategory->name}}</span>
                                </div>
                                <div class="col-md-12 d-flex">
                                    <strong style="width:120px;">License Key</strong>
                                    <span class="mx-2">:</span>
                                    <span><code>{{$softwaredata->product_key}}</code></span>
                                </div>
                                <div class="col-md-12 d-flex">
                                    <strong style="width:120px;">Seats Count</strong>
                                    <span class="mx-2">:</span>
                                    <span><code>{{$softwaredata->seats}}</code></span>
                                </div>
                                <div class="col-md-12 d-flex">
                                    <strong style="width:120px;">Seats Free</strong>
                                    <span class="mx-2">:</span>
                                    <span><code>{{$softwaredata->seats - $softwareUsed}}</code></span>
                                </div>
                                <div class="col-md-12 d-flex justify-content-center my-3">
                                    <button class="btn btn-sm btn-outline-secondary shadow mx-2" type="button" wire:click="changelocationCheckout(true)">
                                        <i class="bx bxs-user-account"></i> User
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary shadow mx-2" type="button" wire:click="changelocationCheckout(false)">
                                        <i class="bx bx-barcode"></i> Asset
                                    </button>
                                </div>
                                
                                    @if ($touser)
                                        <div class="row justify-content-center text-center">
                                            <div class="col-md-4">
                                                <label for="departement_id" class="form-label">Departement <span class="text-danger">*</span></label>
                                                <div wire:loading wire:target="departement_id" class="text-center mb-2">
                                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                    Memuat data departemen...
                                                </div>
                                                <select wire:model="departement_id" wire:change="changeDataId" id="departement_id" class="form-select @error('category_id') is-invalid @enderror" wire:loading.attr="disabled">
                                                    <option value="">-- Pilih Departement --</option>
                                                    @foreach ($licenseDepartements as $dept)
                                                        <option value="{{ $dept->id }}">{{ $dept->code }} - {{ $dept->name }} </option>
                                                    @endforeach
                                                </select>
                                                @error('departement_id') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-8">
                                                <label for="user_id" class="form-label">Users <span class="text-danger">*</span></label>
                                                <div wire:loading wire:target="user_id" class="text-center mb-2">
                                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                    Memuat data user...
                                                </div>
                                                
                                                <select wire:model="user_id" id="user_id" class="form-select @error('category_id') is-invalid @enderror" wire:loading.attr="disabled">
                                                   
                                                    <option value="">-- Pilih User --</option>
                                                    @foreach ($licenseUsers as $user)
                                                        <option value="{{ $user->id }}">{{ $user->nik }} - {{ $user->name }} </option>
                                                    @endforeach
                                                </select>
                                                @error('user_id') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                    @else
                                        <div class="row justify-content-center text-center">
                                            <div class="col-md-4">
                                                <label for="type_id" class="form-label">Asset Type <span class="text-danger">*</span></label>
                                                <div wire:loading wire:target="type_id,asset_id" class="text-center">
                                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                    Memuat data...
                                                </div>
                                                <select wire:model="type_id" wire:change="changeDataId" id="type_id" class="form-select @error('category_id') is-invalid @enderror" wire:loading.attr="disabled">
                                                    <option value="">-- Pilih Type --</option>
                                                    @foreach ($listTypeAsset as $type)
                                                        <option value="{{ $type->id }}">{{ $type->code ?? '' }} - {{ $type->name }} </option>
                                                    @endforeach
                                                </select>
                                                @error('type_id') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-8">
                                                <label for="asset_id" class="form-label">No Asset <span class="text-danger">*</span></label>
                                                <div wire:loading wire:target="type_id,asset_id" class="text-center">
                                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                    Memuat data...
                                                </div>
                                                <select wire:model="asset_id" id="asset_id" class="form-select @error('category_id') is-invalid @enderror" wire:loading.attr="disabled">
                                                    <option value="">-- Pilih No Asset --</option>
                                                    @foreach ($listAsset as $asset)
                                                        <option value="{{ $asset->id }}">{{ $asset->asset_number }}</option>
                                                    @endforeach
                                                </select>
                                                @error('asset_id') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                    @endif
                                    <div class="mb-3">
                                        <label for="checkout_notes" class="form-label">Catatan</label>
                                        <textarea class="form-control" id="checkout_notes" wire:model.defer="checkout_notes" rows="3" placeholder="Tambahkan catatan jika diperlukan"></textarea>
                                    </div>
                            </div>
                            @else
                                <div class="text-center p-4">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Semua Software License sudah terpakai.....
                                </div>
                            @endif
                            <div>
                            
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            @if($softwaredata->seats - $softwareUsed > 0)
                            
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">Simpan</span>
                                <span wire:loading wire:target="store">Memproses...</span>
                            </button>
                            @endif
                            
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>