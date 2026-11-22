<div>
    <div class="container-fluid py-4">
        <div class="card shadow border-0" style="min-height: 70vh;">
            <div class="card-header pb-2 bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0 font-weight-bold text-dark d-flex align-items-center">
                        <i class="bx bx-bot me-2 text-primary"></i>
                        AI Assistant <small class="text-muted fw-normal ms-2">AMS / ERP</small>
                    </h5>
                    <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                        <span class="badge bg-gradient-info text-uppercase px-3 py-2" style="font-size: 0.85rem;">
                            Gemini Flash
                        </span>
                    </div>
                </div>
                @if (session()->has('message'))
                    <div class="alert alert-success text-white mt-3 mb-0 small" role="alert">
                        <i class="bx bx-check-circle me-2"></i>
                        {{ session('message') }}
                    </div>
                @endif
            </div>
            <div class="card-body row gx-0 gy-3 flex-grow-1" style="height:100%;">
                <!-- Sidebar: Chat History -->
                <div class="col-12 col-md-4 col-lg-3 border-end bg-white px-0 py-3 d-flex flex-column" style="min-width:220px;max-width:320px;">
                    <div class="px-3 mb-3 d-flex align-items-center justify-content-between">
                        <span class="fw-semibold text-dark text-xs">Riwayat Chat</span>
                        <button wire:click="newChat" type="button" class="btn btn-sm btn-outline-primary mb-0 rounded-pill shadow-none">
                            <i class="bx bx-plus me-1"></i> Chat Baru
                        </button>
                    </div>
                    <div class="flex-grow-1 overflow-auto px-1" style="min-height:120px;">
                        @forelse($chatHistory as $chat)
                            <div 
                                wire:click="selectChat({{ $chat->id }})" 
                                class="chat-history-item px-3 py-2 mb-1 rounded-3 
                                    {{ $chat->id === $chatId ? 'bg-gradient-primary text-white' : 'bg-light text-dark' }} 
                                    d-flex flex-column cursor-pointer"
                                style="transition: background .2s; cursor:pointer;"
                            >
                                <span class="truncate fw-semibold" style="max-width:185px;">
                                    {{ $chat->title ?? 'Chat #' . $chat->id }}
                                </span>
                                @if($chat->updated_at)
                                    <span class="text-xs {{ $chat->id === $chatId ? 'text-white-50' : 'text-secondary' }}">
                                        {{ $chat->updated_at->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                        @empty
                            <div class="text-center text-xs text-muted px-3 py-2">Belum ada riwayat.</div>
                        @endforelse
                    </div>
                </div>
                <!-- Main chat area -->
                <div class="col-12 col-md-8 col-lg-9 d-flex flex-column p-3" style="overflow: hidden;">
                    <!-- Chat history conversation -->
                    <div class="flex-grow-1 overflow-auto pe-2" id="chat-container" style="scroll-behavior: smooth; min-height:150px;">
                        @forelse($messages as $msg)
                            @if(($msg['role'] ?? null) === 'user')
                                <!-- USER MESSAGE -->
                                <div class="d-flex justify-content-end mb-3">
                                    <div class="bg-gradient-primary text-white px-3 py-2 rounded-4 shadow" style="max-width: 75%;">
                                        <div class="text-sm">
                                            {!! nl2br(e($msg['content'])) !!}
                                        </div>
                                        @if(data_get($msg, 'file'))
                                            <div class="mt-2 text-xs bg-dark bg-opacity-10 px-2 py-1 rounded-3 d-inline-block">
                                                <i class="bx bx-paperclip me-1"></i> {{ data_get($msg, 'file') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <!-- AI MESSAGE -->
                                <div class="d-flex justify-content-start mb-3">
                                    <div class="bg-light px-3 py-2 rounded-4 border shadow-sm" style="max-width: 75%;">
                                        <div class="text-sm ai-response text-dark">
                                            {!! nl2br(e($msg['content'])) !!}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="text-center text-secondary py-5">
                                <i class="bx bx-bot fa-3x mb-3 text-primary" style="font-size:3rem;"></i>
                                <p class="mb-1 fw-semibold">Selamat Datang di AI Assistant</p>
                                <small class="text-muted">
                                    Ajukan pertanyaan seputar Asset, ERP, SQL, Laravel, atau topik lainnya secara profesional.
                                </small>
                            </div>
                        @endforelse

                        @if($loading)
                            <div class="d-flex justify-content-start mb-3">
                                <div class="bg-light px-3 py-2 rounded-3 border text-muted">
                                    <span class="text-xs"><i class="bx bx-loader-alt bx-spin me-1"></i>AI sedang mengetik...</span>
                                </div>
                            </div>
                        @endif
                    </div>
                    <hr class="horizontal dark my-2">
                    <!-- INPUT AREA -->
                    <form wire:submit.prevent="send" class="row g-2 align-items-end">
                        <!-- File upload -->
                        <div class="col-12 col-md-2">
                            <label class="form-label mb-1 text-xs text-muted d-block">Lampiran</label>
                            <input type="file" wire:model="file" class="form-control form-control-sm rounded-3">
                            @error('file') 
                                <div class="text-danger text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Text -->
                        <div class="col-12 col-md flex-grow-1">
                            <label class="form-label mb-1 text-xs text-muted d-block">Pesan</label>
                            <textarea 
                                wire:model.defer="message" 
                                wire:keydown.enter.prevent="send"
                                rows="2"
                                class="form-control rounded-3 shadow-none"
                                placeholder="Ketik pertanyaan atau instruksi secara profesional... (Tekan Enter untuk kirim)"></textarea>
                            @error('message') 
                                <div class="text-danger text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Send -->
                        <div class="col-auto">
                            <button type="submit" wire:click="send" class="btn bg-gradient-primary mb-0 shadow-sm rounded-circle px-3" title="Kirim">
                                <i class="bx bx-send"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Auto Scroll -->
    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.on('scrollBottom', () => {
                const container = document.getElementById('chat-container');
                if (container) container.scrollTop = container.scrollHeight;
            });
        });
    </script>
    <style>
    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .card-body > .col-md-4,
        .card-body > .col-md-8,
        .card-body > .col-lg-3,
        .card-body > .col-lg-9 {
            max-width: 100%!important;
            flex: 0 0 100%!important;
        }
        .card-body {
            flex-direction: column!important;
        }
        .chat-history-item span.truncate {
            max-width: 75vw!important;
        }
    }
    @media (max-width: 600px) {
        .card {
            border-radius: 0!important;
        }
        .card-header {
            padding-left: 0.75rem; padding-right: 0.75rem;
        }
        .card-body {
            padding: 0!important;
        }
        .chat-history-item span.truncate {
            max-width: 65vw!important;
        }
    }
    </style>
</div>
