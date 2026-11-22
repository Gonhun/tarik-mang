<div wire:init="fetchGroupData">
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Grup WhatsApp</h5>
                    {{-- Tombol Refresh --}}
                    <button wire:click="fetchGroupData" class="btn btn-sm btn-outline-primary mb-0">
                        <span wire:loading.remove wire:target="fetchGroupData">
                            <i class="fas fa-sync-alt me-1"></i> Refresh Data
                        </span>
                        <span wire:loading wire:target="fetchGroupData">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Memuat...
                        </span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                {{-- Tampilkan pesan error jika ada --}}
                @if ($errorMessage)
                    <div class="alert alert-danger text-white">{{ $errorMessage }}</div>
                @endif

                {{-- Tampilkan indikator loading saat data diambil --}}
                <div wire:loading wire:target="fetchGroupData" class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Mengambil data grup...</p>
                </div>

                {{-- Tampilkan tabel hanya jika loading selesai --}}
                <div wire:loading.remove wire:target="fetchGroupData">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No.</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Nama Grup</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total Member</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">ID Grup (_serialized)</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Kirim Pesan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($groups as $group)
                                    <tr>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0 px-3">{{ $loop->iteration }}</p>
                                        </td>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0">{{ $group['name'] ?? 'N/A' }}</p>
                                        </td>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0">{{ $group['participants'] ?? 'N/A' }}</p>
                                        </td>
                                        <td>
                                            {{-- Mengakses data nested dengan aman --}}
                                            <p class="text-xs font-weight-bold mb-0">{{ $group['id']['_serialized'] ?? 'ID Tidak Tersedia' }}</p>
                                        </td>
                                        <td>
                                            <button wire:click="openSendMessageModal('{{ $group['id']['_serialized'] }}', '{{ addslashes($group['name']) }}')" class="btn btn-sm btn-info mb-0">
                                                Kirim Pesan
                                            </button>
                                        </td>
                                    </tr>
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
                </div>
            </div>
        </div>
    </div>
    @if($isSendMessageModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: 1.2rem;">
                <form wire:submit.prevent="sendMessage">
                    <div class="modal-header bg-gradient-primary text-white" style="border-top-left-radius: 1.2rem; border-top-right-radius: 1.2rem; background: linear-gradient(90deg, #0072ff 0%, #00c6ff 100%);">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-paper-plane me-2 text-warning"></i>
                            Kirim Pesan ke Grup: <span class="text-warning">{{ $targetGroupName }}</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeSendMessageModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pb-0">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-info text-dark me-2" style="font-size: 0.95rem;">
                                <i class="fas fa-users"></i> ID Grup
                            </span>
                            <span class="small text-muted">{{ $targetGroupId }}</span>
                        </div>
                        <div class="mb-3">
                            <label for="messageBody" class="form-label fw-semibold">
                                <i class="fas fa-comment-dots text-primary"></i> Isi Pesan <span class="text-danger">*</span>
                            </label>
                            <textarea wire:model.defer="messageBody" id="messageBody" class="form-control shadow-sm @error('messageBody') is-invalid @enderror" rows="5" placeholder="🌟 Tulis pesan inspiratif Anda di sini..."></textarea>
                            @error('messageBody') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-2">
                            <span class="me-1 fw-semibold text-primary"><i class="fas fa-smile"></i> Emoji:</span>
                            @php
                                $emojis = ['😀','😂','😍','👍','🙏','🎉','😎','🥳','😢','🔥','❤️','😇','🤔','🙌','👏','😅','😱','😜','🤩','😡','😴'];
                            @endphp
                            <div class="d-inline-flex flex-wrap gap-1">
                                @foreach($emojis as $emoji)
                                    <button type="button" class="btn btn-light btn-sm px-1 py-0 border-0 shadow-none" style="font-size: 1.35rem; line-height:1.1; transition: transform 0.1s;" onclick="addEmojiToMessageBody('{{ $emoji }}')" tabindex="-1" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">{{ $emoji }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0" style="border-bottom-left-radius: 1.2rem; border-bottom-right-radius: 1.2rem;">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeSendMessageModal">
                            <i class="fas fa-times me-1"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-gradient-primary text-white fw-bold" style="background: linear-gradient(90deg, #00c6ff 0%, #0072ff 100%); border: none;">
                            <span wire:loading.remove wire:target="sendMessage">
                                <i class="fas fa-paper-plane me-1"></i> Kirim
                            </span>
                            <span wire:loading wire:target="sendMessage">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Mengirim...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    <script>
        function addEmojiToMessageBody(emoji) {
            // Ambil textarea
            var textarea = document.getElementById('messageBody');
            if (!textarea) return;
            // Simpan posisi kursor
            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            var text = textarea.value;
            // Sisipkan emoji di posisi kursor
            textarea.value = text.substring(0, start) + emoji + text.substring(end);
            // Pindahkan kursor setelah emoji
            textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
            // Trigger input event agar Livewire sync
            textarea.dispatchEvent(new Event('input'));
            textarea.focus();
        }
    </script>
</div>
