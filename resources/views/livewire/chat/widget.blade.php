           
            <div id="chat-widget-container" class="chat-widget-container-modern">
                <style>
                    .chat-widget-container-modern {
                        position: fixed;
                        bottom: 24px;
                        right: 24px;
                        z-index: 1000;
                        border-radius: 18px;
                        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
                        background: rgba(255,255,255,0.95);
                        transition: box-shadow 0.2s, background 0.2s;
                        overflow: visible;
                        min-width: 0;
                    }
                    .chat-widget-modern-card {
                        width: 370px;
                        max-width: 95vw;
                        max-height: 70vh;
                        min-width: 260px;
                        display: flex;
                        flex-direction: column;
                        border-radius: 18px;
                        overflow: hidden;
                        background: #fff;
                        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
                        border: none;
                    }
                    .chat-widget-modern-header {
                        background: linear-gradient(90deg, #6366f1 0%, #60a5fa 100%);
                        color: #fff;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 0.75rem 1rem 0.75rem 1.2rem;
                        font-weight: 600;
                        border-bottom: 1px solid #e5e7eb;
                        cursor: grab;
                        user-select: none;
                    }
                    .chat-widget-modern-header .avatar {
                        width: 32px;
                        height: 32px;
                        border-radius: 50%;
                        background: #fff;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-right: 0.7rem;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                    }
                    .chat-widget-modern-close {
                        background: transparent;
                        border: none;
                        color: #fff;
                        font-size: 1.2rem;
                        opacity: 0.8;
                        transition: opacity 0.2s;
                    }
                    .chat-widget-modern-close:hover {
                        opacity: 1;
                    }
                    .chat-widget-modern-body {
                        overflow-y: auto;
                        flex-grow: 1;
                        background: #f3f4f6;
                        padding: 1rem 0.7rem 0.7rem 0.7rem;
                    }
                    .chat-widget-modern-footer {
                        background: #fff;
                        padding: 0.7rem 0.7rem 0.7rem 0.7rem;
                        border-top: 1px solid #e5e7eb;
                    }
                    .chat-widget-modern-message {
                        max-width: 75%;
                        padding: 0.7rem 1rem;
                        border-radius: 16px;
                        font-size: 0.97rem;
                        margin-bottom: 0.2rem;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                        word-break: break-word;
                    }
                    .chat-widget-modern-message.user {
                        background: linear-gradient(90deg, #a7f3d0 0%, #6ee7b7 100%);
                        align-self: flex-end;
                    }
                    .chat-widget-modern-message.bot {
                        background: #fff;
                        align-self: flex-start;
                    }
                    .chat-widget-modern-time {
                        font-size: 0.72rem;
                        color: #9ca3af;
                        text-align: right;
                        margin-top: 2px;
                    }
                    .chat-widget-modern-typing {
                        font-style: italic;
                        color: #6366f1;
                    }
                    .chat-widget-modern-input-group {
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                    }
                    .chat-widget-modern-input {
                        flex: 1;
                        border-radius: 12px;
                        border: 1px solid #e5e7eb;
                        padding: 0.5rem 1rem;
                        font-size: 1rem;
                        background: #f9fafb;
                        transition: border 0.2s;
                    }
                    .chat-widget-modern-input:focus {
                        border: 1.5px solid #6366f1;
                        outline: none;
                    }
                    .chat-widget-modern-attach {
                        background: #f3f4f6;
                        border: none;
                        border-radius: 50%;
                        width: 36px;
                        height: 36px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1.2rem;
                        color: #6366f1;
                        cursor: pointer;
                        transition: background 0.2s;
                    }
                    .chat-widget-modern-attach:hover {
                        background: #e0e7ff;
                    }
                    .chat-widget-modern-send {
                        background: linear-gradient(90deg, #6366f1 0%, #60a5fa 100%);
                        border: none;
                        border-radius: 50%;
                        width: 36px;
                        height: 36px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: #fff;
                        font-size: 1.2rem;
                        cursor: pointer;
                        transition: background 0.2s;
                    }
                    .chat-widget-modern-send:disabled {
                        opacity: 0.6;
                        cursor: not-allowed;
                    }
                    .chat-widget-modern-preview {
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                        margin-bottom: 0.5rem;
                    }
                    .chat-widget-modern-preview img {
                        max-height: 60px;
                        border-radius: 8px;
                    }
                    .chat-widget-modern-preview .btn {
                        font-size: 1.1rem;
                        padding: 0.2rem 0.5rem;
                    }
                    @media (max-width: 600px) {
                        .chat-widget-modern-card {
                            width: 98vw !important;
                            min-width: 0 !important;
                            max-width: 98vw !important;
                            max-height: 90vh !important;
                            border-radius: 0.8rem;
                        }
                        .chat-widget-container-modern {
                            right: 2vw !important;
                            bottom: 2vw !important;
                        }
                    }
                </style>
                @if ($isOpen)
                    <div class="chat-widget-modern-card shadow">
                        <div class="chat-widget-modern-header" id="chat-widget-drag-handle">
                            <div class="d-flex align-items-center">
                                <span class="avatar me-2" style="background: #fff;">
                                    <img src="{{ asset('assets/img/LogoBSS.png') }}" alt="Bot" style="width: 24px; height: 24px;">
                                </span>
                                <span>Virtual Assistant</span>
                            </div>
                            <button wire:click="toggleChat" type="button" class="chat-widget-modern-close" aria-label="Close">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                        <div id="chat-box-messages" class="chat-widget-modern-body d-flex flex-column">
                          
                            @forelse ($messages as $msg)
                                <div class="d-flex {{ $msg['sender_type'] == 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                                    <div class="chat-widget-modern-message {{ $msg['sender_type'] == 'user' ? 'user' : 'bot' }}">
                                        @if ($msg['message_type'] == 'text')
                                            <p class="mb-0">{!! nl2br(e($msg['content'])) !!}</p>
                                        @elseif ($msg['message_type'] == 'image' && $msg['file_path'])
                                            <a href="{{ Storage::url($msg['file_path']) }}" target="_blank" title="{{ $msg['file_name'] }}">
                                                <img src="{{ Storage::url($msg['file_path']) }}" alt="{{ $msg['file_name'] ?? 'Uploaded Image' }}" class="img-fluid rounded" style="max-height: 120px;">
                                            </a>
                                            @if($msg['content'])
                                                <p class="mb-0 mt-1 text-muted" style="font-size: 0.85rem;">{!! nl2br(e($msg['content'])) !!}</p>
                                            @endif
                                        @endif
                                        <div class="chat-widget-modern-time">
                                            {{ \Carbon\Carbon::parse($msg['created_at'])->format('H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-muted">Mulai percakapan...</p>
                            @endforelse
                            @if($isLoadingResponse)
                                <div class="d-flex justify-content-start">
                                    <div class="chat-widget-modern-message bot chat-widget-modern-typing">
                                        <span>
                                            <span class="me-1">Bot sedang mengetik</span>
                                            <span class="spinner-grow spinner-grow-sm align-middle" style="width: 0.8em; height: 0.8em;"></span>
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="chat-widget-modern-footer">
                            <form wire:submit.prevent="sendMessage" id="chatForm" autocomplete="off">
                                @if ($newFile && Str::startsWith($newFile->getMimeType(), 'image/'))
                                    <div class="chat-widget-modern-preview">
                                        <img src="{{ $newFile->temporaryUrl() }}" class="img-thumbnail">
                                        <button type="button" wire:click="$set('newFile', null)" class="btn btn-sm btn-danger" title="Hapus gambar">&times;</button>
                                    </div>
                                @endif
                                <div class="chat-widget-modern-input-group">
                                    <input wire:model.defer="newMessage" type="text" class="chat-widget-modern-input" placeholder="Ketik pesan..." @if($isLoadingResponse) disabled @endif>
                                    <label for="chatFileAttachment" class="chat-widget-modern-attach" data-bs-toggle="tooltip" title="Kirim Gambar">
                                        <i class="bx bx-paperclip"></i>
                                        <div wire:loading wire:target="newFile" class="spinner-border spinner-border-sm text-primary ms-1" role="status" style="width: 1em; height: 1em;">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </label>
                                    <input type="file" wire:model="newFile" id="chatFileAttachment" style="display: none;" accept="image/*">
                                    <button type="submit" class="chat-widget-modern-send" @if($isLoadingResponse) disabled @endif>
                                        <span wire:loading.remove wire:target="sendMessage, newFile"><i class="bx bx-send"></i></span>
                                        <span wire:loading wire:target="sendMessage, newFile">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                        </span>
                                    </button>
                                </div>
                                @error('newMessage') <span class="text-danger d-block mt-1" style="font-size: 0.8rem;">{{ $message }}</span> @enderror
                                @error('newFile') <span class="text-danger d-block mt-1" style="font-size: 0.8rem;">{{ $message }}</span> @enderror
                            </form>
                        </div>
                    </div>
                @else
                    <button wire:click="toggleChat" class="btn btn-primary rounded-circle shadow d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; font-size: 2rem; background: linear-gradient(135deg, #6366f1 0%, #60a5fa 100%);" data-bs-toggle="tooltip" title="Buka Chat">
                        <i class="bx bx-message-dots"></i>
                    </button>
                @endif
            </div>

@push('scripts')
<script>
(function() {
    const chatWidget = document.getElementById('chat-widget-container');
    const dragHandle = document.getElementById('chat-widget-drag-handle');
    let isDragging = false, offsetX = 0, offsetY = 0;

    // Responsive: adjust width on resize
    function adjustChatWidgetWidth() {
        if (!chatWidget) return;
        if (window.innerWidth < 600) {
            chatWidget.style.width = '98vw';
            chatWidget.style.minWidth = '0';
            chatWidget.style.right = '2vw';
            chatWidget.style.bottom = '2vw';
        } else {
            chatWidget.style.width = '';
            chatWidget.style.minWidth = '';
            chatWidget.style.right = '24px';
            chatWidget.style.bottom = '24px';
        }
    }
    window.addEventListener('resize', adjustChatWidgetWidth);
    adjustChatWidgetWidth();

    // Drag & move
    if (dragHandle) {
        dragHandle.addEventListener('mousedown', function(e) {
            isDragging = true;
            const rect = chatWidget.getBoundingClientRect();
            offsetX = e.clientX - rect.left;
            offsetY = e.clientY - rect.top;
            document.body.style.userSelect = 'none';
        });
        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            let x = e.clientX - offsetX;
            let y = e.clientY - offsetY;

            // Responsive boundaries
            const minX = 0;
            const minY = 0;
            const maxX = window.innerWidth - chatWidget.offsetWidth;
            const maxY = window.innerHeight - chatWidget.offsetHeight;

            x = Math.max(minX, Math.min(x, maxX));
            y = Math.max(minY, Math.min(y, maxY));

            chatWidget.style.left = x + 'px';
            chatWidget.style.top = y + 'px';
            chatWidget.style.right = '';
            chatWidget.style.bottom = '';
            chatWidget.style.position = 'fixed';
        });
        document.addEventListener('mouseup', function() {
            isDragging = false;
            document.body.style.userSelect = '';
        });
    }

    // Scroll to bottom
    function scrollToBottomChat() {
        const chatBox = document.getElementById('chat-box-messages');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }

    // Livewire events
    document.addEventListener('livewire:load', function () {
        scrollToBottomChat();
        // Bootstrap tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });

    Livewire.on('message-sent', () => {
        scrollToBottomChat();
    });
    Livewire.on('chat-opened', () => {
        setTimeout(() => {
            scrollToBottomChat();
        }, 50);
    });

    Livewire.on('fileInputReset', () => {
        const fileInput = document.getElementById('chatFileAttachment');
        if(fileInput) {
            fileInput.value = null;
        }
    });
})();
</script>
@endpush