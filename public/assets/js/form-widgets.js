/*
 * Widget form berbasis Alpine (ikut Livewire 3), dipakai di modal yang dirender ulang oleh Livewire.
 *
 * searchSelect — dropdown dengan kolom pencarian. <select> asli tetap ada (disembunyikan) dan
 *   tetap dipegang Livewire (wire:model / wire:change), widget hanya membaca opsinya dan
 *   menulis nilainya kembali lewat event change. Pakai komponen Blade <x-search-select>.
 *
 * cameraScan — isi input dari QR / barcode via kamera (html5-qrcode harus sudah dimuat).
 */
document.addEventListener('alpine:init', () => {
    // Elemen DOM & instance pihak ketiga disimpan di closure, BUKAN di state Alpine:
    // state Alpine dibungkus Proxy reaktif, dan getter DOM (select.options, .value, dst.)
    // melempar "Illegal invocation" bila dipanggil lewat Proxy.
    // resets: properti Livewire yang langsung dikosongkan saat pilihan ini berganti (mis. Tipe -> Merek & Seri).
    Alpine.data('searchSelect', (placeholder = 'Cari...', resets = []) => {
      let sel = null, observer = null, unhook = null, model = null;
      return {
        open: false,
        q: '',
        active: 0,
        options: [],
        label: '',
        isPlaceholder: true,
        disabled: false,
        invalid: false,
        placeholder,
        value: '',

        init() {
            sel = this.$el.querySelector('select');
            if (!sel) return;
            // Nama properti Livewire dari wire:model / wire:model.live / wire:model.defer.
            model = Array.from(sel.attributes).find((a) => a.name.startsWith('wire:model'))?.value || null;
            this.refresh();
            // wire:model mengisi nilai <select> SETELAH widget ini dibuat (mis. modal Edit baru muncul),
            // dan request yang memunculkan modal sudah lewat sebelum hook commit di bawah terpasang.
            // Jadi baca ulang setelah Alpine/Livewire selesai menginisialisasi elemen.
            this.$nextTick(() => this.refresh());
            setTimeout(() => this.refresh(), 0);
            requestAnimationFrame(() => this.refresh());
            sel.addEventListener('change', () => this.refresh());
            // Opsi berubah saat Livewire merender ulang (mis. Merek bergantung pada Tipe).
            observer = new MutationObserver(() => this.refresh());
            observer.observe(sel, { childList: true, subtree: true, attributes: true, characterData: true });
            // Nilai yang di-set Livewire lewat wire:model tidak memicu event, jadi sinkronkan setelah tiap request.
            if (window.Livewire?.hook) {
                unhook = Livewire.hook('commit', ({ succeed }) => succeed(() => setTimeout(() => this.refresh(), 0)));
            }
        },

        destroy() {
            observer?.disconnect();
            if (typeof unhook === 'function') unhook();
        },

        /** Nilai properti Livewire yang diikat ke <select> ini; undefined bila tidak ada wire:model / $wire. */
        wireValue() {
            if (!model || !this.$wire) return undefined;
            try { return this.$wire.get(model); } catch (e) { return undefined; }
        },

        /** Dipanggil dari x-effect: membaca $wire membuat label ikut berubah setiap nilai Livewire berubah. */
        syncFromWire() {
            this.wireValue();
            this.refresh();
        },

        refresh() {
            if (!sel?.isConnected) return;
            this.options = Array.from(sel.options)
                .filter((o) => o.value !== '')
                .map((o) => ({ value: o.value, text: o.text.trim(), disabled: o.disabled }));
            // Sumber kebenaran = state Livewire ($wire), bukan DOM: saat modal Edit baru muncul,
            // nilai <select> bisa belum diisi wire:model ketika widget membacanya.
            let cur = sel.selectedOptions[0];
            const wireValue = this.wireValue();
            if (wireValue !== undefined) {
                const v = wireValue === null ? '' : String(wireValue);
                cur = Array.from(sel.options).find((o) => o.value === v)
                    || (v === '' ? sel.options[0] : null);
            }
            this.value = cur ? cur.value : '';
            this.isPlaceholder = !cur || cur.value === '';
            this.label = cur ? cur.text.trim() : '';
            this.disabled = sel.disabled;
            this.invalid = sel.classList.contains('is-invalid');
        },

        get filtered() {
            const q = this.q.trim().toLowerCase();
            if (!q) return this.options;
            const words = q.split(/\s+/);
            return this.options.filter((o) => {
                const t = o.text.toLowerCase();
                return words.every((w) => t.includes(w));
            });
        },

        toggle() {
            this.open ? this.close() : this.show();
        },

        show() {
            if (this.disabled) return;
            this.refresh();
            this.q = '';
            const i = this.filtered.findIndex((o) => o.value === sel.value);
            this.active = i < 0 ? 0 : i;
            this.open = true;
            this.$nextTick(() => {
                this.$refs.search?.focus();
                this.scrollActive();
            });
        },

        close(focusButton = false) {
            this.open = false;
            if (focusButton) this.$refs.button?.focus();
        },

        choose(opt) {
            if (!opt || opt.disabled) return;
            if (sel.value !== opt.value) {
                // Kosongkan pilihan turunan saat itu juga (tanpa menunggu server); ikut terkirim di request berikutnya.
                if (this.$wire) resets.forEach((prop) => this.$wire.set(prop, null, false));
                sel.value = opt.value;
                sel.dispatchEvent(new Event('input', { bubbles: true }));
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }
            this.refresh();
            this.close(true);
        },

        move(step) {
            const n = this.filtered.length;
            if (!n) return;
            this.active = (this.active + step + n) % n;
            this.scrollActive();
        },

        scrollActive() {
            this.$nextTick(() => this.$refs.list?.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' }));
        },
      };
    });

    Alpine.data('cameraScan', () => {
      let scanner = null;
      return {
        scanning: false,
        error: '',
        readerId: 'cam-reader-' + Math.random().toString(36).slice(2, 9),

        async toggle() {
            this.scanning ? await this.stop() : await this.start();
        },

        async start() {
            this.error = '';
            if (typeof Html5Qrcode === 'undefined') {
                this.error = 'Pemindai belum termuat. Periksa koneksi lalu muat ulang halaman.';
                return;
            }
            this.scanning = true;
            await this.$nextTick();
            try {
                const F = Html5QrcodeSupportedFormats;
                scanner = new Html5Qrcode(this.readerId, {
                    formatsToSupport: [F.QR_CODE, F.DATA_MATRIX, F.CODE_128, F.CODE_39, F.CODE_93, F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.ITF, F.CODABAR],
                    experimentalFeatures: { useBarCodeDetectorIfSupported: true },
                    verbose: false,
                });
                // Area bidik lebar agar barcode batang SN yang panjang ikut terbaca.
                const box = (w, h) => ({ width: Math.floor(Math.min(w * 0.9, 360)), height: Math.floor(Math.min(h * 0.55, 200)) });
                await scanner.start({ facingMode: 'environment' }, { fps: 12, qrbox: box }, (text) => this.found(text));
            } catch (e) {
                this.error = 'Kamera tidak bisa dibuka: ' + (e?.message || e) + '. Pastikan izin kamera diberikan dan halaman dibuka lewat HTTPS. Anda tetap bisa mengetik SN.';
                await this.stop();
            }
        },

        async stop() {
            const s = scanner;
            scanner = null;
            if (s) {
                try { await s.stop(); } catch (e) {}
                try { s.clear(); } catch (e) {}
            }
            this.scanning = false;
        },

        async found(text) {
            const input = this.$refs.input;
            if (input) {
                input.value = text.trim();
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (navigator.vibrate) navigator.vibrate(60);
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.frequency.value = 880;
                gain.gain.value = 0.15;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                setTimeout(() => { osc.stop(); ctx.close(); }, 120);
            } catch (e) {}
            await this.stop();
            input?.focus();
        },

        destroy() {
            this.stop();
        },
      };
    });
});
