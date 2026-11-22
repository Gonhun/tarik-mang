<script>
    Livewire.on('swal:error', event => {
        const detail = event[0] || event;
        function playCustomSound() {
            const audio = new Audio('https://pranx.com/sound-effects/sounds/quack-duck.mp3');
            audio.volume = 0.6;
            audio.play().catch(e => {
                console.warn('Gagal memutar suara:', e);
            });
        }
        Swal.fire({
            icon: 'error',
            title: `<span style="color:#e74c3c;font-weight:bold;letter-spacing:1px;text-shadow:1px 2px 6px #ffbcbc;">
                        ${detail.title}
                    </span>`,
            html: `
                <div style="font-size:1.08em;margin-bottom:10px;animation: shake 0.7s;">
                    <i class="bx bx-error" style="color:#e74c3c;font-size:2em;animation: pulse 1.2s infinite alternate;"></i>
                    <p style="margin-top:10px;font-weight:500;">${detail.text}</p>
                </div>
                <div style="margin-top:18px;">
                    <span style="color:#3085d6;font-weight:600;">Jika Anda memerlukan bantuan, silakan hubungi administrator.</span>
                    <a href="mailto:admin@yourdomain.com" style="color:#30D630;text-decoration:underline;font-weight:700;margin-left:7px;">
                        Hubungi Administrator
                    </a>
                </div>
                <style>
                    @keyframes shake {
                        0% { transform: translateX(0); }
                        20% { transform: translateX(-8px); }
                        40% { transform: translateX(8px); }
                        60% { transform: translateX(-6px); }
                        80% { transform: translateX(6px); }
                        100% { transform: translateX(0); }
                    }
                    @keyframes pulse {
                        0% { filter: drop-shadow(0 0 0 #e74c3c);}
                        100% { filter: drop-shadow(0 0 8px #e74c3c);}
                    }
                </style>
            `,
            background: 'linear-gradient(135deg, #fff 80%, #ffeaea 100%)',
            showClass: {
                popup: 'animate__animated animate__tada animate__faster'
            },
            timer: 4000,
            timerProgressBar: true,
            confirmButtonColor: '#30D630',
            customClass: {
                popup: 'swal2-border-radius'
            },
            didOpen: () => {
                playCustomSound();
            }
        });
    });

    Livewire.on('swal:confirmDelete', event => {
        const detail = event[0] || event;
        function playCustomSound() {
            const audio = new Audio('https://pranx.com/sound-effects/sounds/family-guy-stewie-say-whaaat.mp3');
            audio.volume = 0.6;
            audio.play().catch(e => {
                console.warn('Gagal memutar suara:', e);
            });
        }
        Swal.fire({
            title: `<span style="color:#e67e22;font-weight:bold;letter-spacing:1px;text-shadow:1px 2px 6px #ffe5b4;">
                        <i class="bx bx-error-circle" style="color:#e67e22;font-size:1.2em;margin-right:7px;animation: swing 1s infinite alternate;"></i>
                        ${detail.title}
                    </span>`,
            html: `
                <div style="font-size:1.05em;margin-bottom:10px;">
                    <p style="font-weight:500;">${detail.text}</p>
                    <div style="margin-top:12px;">
                        <span style="color:#e67e22;font-weight:600;">Tindakan ini tidak dapat dibatalkan.</span>
                    </div>
                </div>
                <style>
                    @keyframes swing {
                        0% { transform: rotate(-10deg);}
                        100% { transform: rotate(10deg);}
                    }
                </style>
            `,
            icon: 'warning',
            background: 'linear-gradient(135deg, #fff 80%, #fff7e6 100%)',
            showCancelButton: true,
            confirmButtonColor: '#30D630',
            cancelButtonColor: '#e67e22',
            confirmButtonText: '<span style="font-weight:bold;">Ya, Hapus</span>',
            cancelButtonText: '<span style="font-weight:bold;">Batal</span>',
            customClass: {
                popup: 'swal2-border-radius'
            },
            didOpen: () => {
               // playCustomSound();
            },
            showClass: {
                popup: 'animate__animated animate__shakeX animate__faster'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (detail.method && detail.id) {
                    Swal.fire({
                        icon: 'success',
                        title: '<span style="color:#30D630;font-weight:bold;">Menghapus...</span>',
                        showConfirmButton: false,
                        timer: 900,
                        background: 'linear-gradient(135deg, #fff 80%, #eaffea 100%)',
                        customClass: {
                            popup: 'swal2-border-radius'
                        }
                    });
                    Livewire.dispatch(detail.method, { id: detail.id });
                } else {
                    console.error('method atau id tidak tersedia', { method: detail.method, id: detail.id });
                }
            }
        });
    });

    Livewire.on('swal:question', event => {
        const detail = event[0] || event;
        function playCustomSound() {
            const audio = new Audio('https://pranx.com/sound-effects/sounds/family-guy-stewie-say-whaaat.mp3');
            audio.volume = 0.6;
            audio.play().catch(e => {
                console.warn('Gagal memutar suara:', e);
            });
        }
        Swal.fire({
            title: `<span style="color:#3498db;font-weight:bold;letter-spacing:1px;text-shadow:1px 2px 6px #d6eaff;">
                        <i class="bx bx-help-circle" style="color:#3498db;font-size:1.2em;margin-right:7px;animation: swing 1s infinite alternate;"></i>
                        ${detail.title}
                    </span>`,
            html: `
                <div style="font-size:1.05em;margin-bottom:10px;">
                    <p style="font-weight:500;">${detail.text}</p>
                    <div style="margin-top:12px;">
                        <span style="color:#3498db;font-weight:600;">Silakan konfirmasi pilihan Anda.</span>
                    </div>
                </div>
                <style>
                    @keyframes swing {
                        0% { transform: rotate(-10deg);}
                        100% { transform: rotate(10deg);}
                    }
                </style>
            `,
            icon: 'question',
            background: 'linear-gradient(135deg, #fff 80%, #e6f3ff 100%)',
            showCancelButton: true,
            confirmButtonColor: '#3498db',
            cancelButtonColor: '#e67e22',
            confirmButtonText: `<span style="font-weight:bold;">${detail.confirmButtonText || 'Ya'}</span>`,
            cancelButtonText: `<span style="font-weight:bold;">${detail.cancelButtonText || 'Batal'}</span>`,
            customClass: {
                popup: 'swal2-border-radius'
            },
            didOpen: () => {
               // playCustomSound();
            },
            showClass: {
                popup: 'animate__animated animate__shakeX animate__faster'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (detail.method && detail.id) {
                    Swal.fire({
                        icon: 'success',
                        title: '<span style="color:#3498db;font-weight:bold;">Memproses...</span>',
                        showConfirmButton: false,
                        timer: 900,
                        background: 'linear-gradient(135deg, #fff 80%, #e6f3ff 100%)',
                        customClass: {
                            popup: 'swal2-border-radius'
                        }
                    });
                    Livewire.dispatch(detail.method, { id: detail.id });
                } else {
                    console.error('method atau id tidak tersedia', { method: detail.method, id: detail.id });
                }
            }
        });
    });

    Livewire.on('swal:success', event => {
        const detail = event[0] || event;
        function playCustomSound() {
            const audio = new Audio('https://pranx.com/sound-effects/sounds/liljonokay.mp3');
            audio.volume = 0.6;
            audio.play().catch(e => {
                console.warn('Gagal memutar suara:', e);
            });
        }
        Swal.fire({
            icon: 'success',
            title: `<span style="color:#30D630;font-weight:bold;letter-spacing:1px;text-shadow:1px 2px 6px #baffb4;">
                        <i class="bx bx-check-circle" style="color:#30D630;font-size:1.2em;margin-right:7px;animation: pop 0.7s;"></i>
                        ${detail.title}
                    </span>`,
            html: `
                <div style="font-size:1.05em;margin-bottom:10px;">
                    <p style="font-weight:500;">${detail.text}</p>
                </div>
                <style>
                    @keyframes pop {
                        0% { transform: scale(0.7);}
                        80% { transform: scale(1.2);}
                        100% { transform: scale(1);}
                    }
                </style>
            `,
            timer: 1800,
            showConfirmButton: false,
            background: 'linear-gradient(135deg, #fff 80%, #eaffea 100%)',
            customClass: {
                popup: 'swal2-border-radius'
            },
            didOpen: () => {
               // playCustomSound();
            },
            showClass: {
                popup: 'animate__animated animate__bounceIn animate__faster'
            }
        });
    });
</script>