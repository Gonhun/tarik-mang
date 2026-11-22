<?php

namespace App\Livewire\Admin;

use App\Support\WhatsAppGateway;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * Integrasi WhatsApp via gateway: hubungkan nomor (scan QR / kode pairing),
 * cek status, kirim pesan uji, dan kirim pesan ke grup.
 */
#[Title('WhatsApp')]
class Whatsapp extends Component
{
    /** QR WhatsApp berlaku ±40 detik; minta baru sebelum kedaluwarsa. */
    private const QR_REFRESH_SECONDS = 30;

    #[Locked] public string $state = 'loading';
    #[Locked] public array $profile = [];
    #[Locked] public array $server = [];
    #[Locked] public ?string $qr = null;
    #[Locked] public ?string $pairingCode = null;
    /** Kode kustom yang diminta ke gateway, untuk dibandingkan dengan kode yang benar-benar dibuat. */
    #[Locked] public ?string $requestedPairingCode = null;
    #[Locked] public int $qrAt = 0;
    public string $pairPhone = '';

    #[Locked] public array $groups = [];
    #[Locked] public bool $groupsLoaded = false;
    public string $groupSearch = '';

    public string $testNumber = '';
    public string $testMessage = 'Tes notifikasi dari AMS IT OPS.';

    #[Locked] public string $targetGroupId = '';
    #[Locked] public string $targetGroupName = '';
    public string $messageBody = '';
    public bool $isSendMessageModalOpen = false;

    public string $errorMessage = '';

    public function mount(): void
    {
        $this->testNumber = (string) (Auth::user()->whatsapp ?? '');
    }

    /** Dipanggil wire:init: status & profil. */
    public function loadStatus(): void
    {
        $this->errorMessage = '';
        WhatsAppGateway::forgetState();
        $this->state = WhatsAppGateway::state();

        if (in_array($this->state, ['open', 'close', 'connecting'], true)) {
            try {
                $this->server = WhatsAppGateway::server();
                $this->profile = $this->state === 'open' ? $this->extractProfile(WhatsAppGateway::info()) : [];
            } catch (Throwable $e) {
                $this->errorMessage = $e->getMessage();
            }
        }
        if ($this->state === 'open') {
            $this->qr = $this->pairingCode = null;
        }
    }

    protected function extractProfile(?array $info): array
    {
        if (!$info) {
            return [];
        }
        $jid = (string) ($info['ownerJid'] ?? '');

        return [
            'name' => $info['profileName'] ?? null,
            'number' => $jid ? '+' . explode('@', $jid)[0] : ($info['number'] ?? null),
            'picture' => $info['profilePicUrl'] ?? null,
            'since' => $info['updatedAt'] ?? null,
        ];
    }

    /** Mulai menghubungkan: buat instance bila belum ada, lalu tampilkan QR. */
    public function startConnect(): void
    {
        $this->errorMessage = '';
        try {
            if ($this->state === 'missing') {
                WhatsAppGateway::create();
            }
            $this->requestQr();
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    protected function requestQr(?string $phone = null): void
    {
        $this->requestedPairingCode = $phone ? WhatsAppGateway::customPairingCode() : null;
        $result = WhatsAppGateway::connect($phone, $this->requestedPairingCode);
        $this->qr = $result['base64'];
        $this->pairingCode = $result['pairingCode'] ?? null;
        $this->qrAt = time();
        $this->state = 'connecting';
        WhatsAppGateway::forgetState();

        if (!$this->qr && !$this->pairingCode) {
            // Tidak ada QR → kemungkinan sudah terhubung.
            $this->loadStatus();
        }
    }

    /** Alternatif tanpa kamera: kode pairing untuk dimasukkan di HP. */
    /** Bila nomor diisi tanpa JavaScript (mis. tempel), tetap dinormalkan. */
    public function updatedPairPhone(): void
    {
        $this->resetErrorBag('pairPhone');
    }

    public function pairWithPhone(): void
    {
        // Normalkan juga di server (0812… / 812… / +62 812… → 62812…).
        $this->pairPhone = PhoneFormat($this->pairPhone);
        $this->validate(['pairPhone' => ['required', 'regex:/^62\d{8,13}$/']], [
            'pairPhone.required' => 'Isi nomor WhatsApp yang akan dihubungkan.',
            'pairPhone.regex' => 'Nomor tidak valid. Contoh: 0812xxxxxxx atau 62812xxxxxxx.',
        ]);
        $this->errorMessage = '';
        try {
            if ($this->state === 'missing') {
                WhatsAppGateway::create();
            }
            $this->requestQr($this->pairPhone);
            if (!$this->pairingCode) {
                $this->errorMessage = 'Server tidak mengirim kode pairing. Gunakan scan QR.';
            }
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /** wire:poll selama QR tampil: deteksi sudah terhubung & perbarui QR. */
    public function pollConnection(): void
    {
        if ($this->state !== 'connecting') {
            return;
        }
        WhatsAppGateway::forgetState();
        $state = WhatsAppGateway::state();

        if ($state === 'open') {
            $this->loadStatus();
            $this->dispatch('swal:success', ['title' => 'Terhubung', 'text' => 'WhatsApp berhasil terhubung. Notifikasi akan dikirim dari nomor ini.']);
            return;
        }

        if (!$this->pairingCode && time() - $this->qrAt >= self::QR_REFRESH_SECONDS) {
            try {
                $this->requestQr();
            } catch (Throwable $e) {
                $this->errorMessage = $e->getMessage();
            }
        }
    }

    public function cancelConnect(): void
    {
        $this->qr = $this->pairingCode = null;
        $this->loadStatus();
    }

    public function logout(): void
    {
        try {
            WhatsAppGateway::logout();
            $this->profile = [];
            $this->groups = [];
            $this->groupsLoaded = false;
            $this->loadStatus();
            $this->dispatch('swal:success', ['title' => 'Terputus', 'text' => 'Nomor WhatsApp sudah dilepas. Scan QR lagi untuk menghubungkan nomor.']);
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function restart(): void
    {
        try {
            WhatsAppGateway::restart();
            $this->loadStatus();
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function sendTest(): void
    {
        $this->validate([
            'testNumber' => 'required|min:9|max:30',
            'testMessage' => 'required|max:1000',
        ], [
            'testNumber.required' => 'Isi nomor tujuan.',
            'testMessage.required' => 'Isi pesan uji.',
        ]);

        try {
            WhatsAppGateway::sendText($this->testNumber, $this->testMessage . "\n\n— dikirim oleh " . Auth::user()->name);
            $this->dispatch('swal:success', ['title' => 'Terkirim', 'text' => 'Pesan uji terkirim ke ' . WhatsAppGateway::normalizeTarget($this->testNumber) . '.']);
        } catch (Throwable $e) {
            $this->addError('testNumber', $e->getMessage());
        }
    }

    public function loadGroups(): void
    {
        $this->errorMessage = '';
        try {
            $this->groups = WhatsAppGateway::groups();
            $this->groupsLoaded = true;
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function openSendMessageModal(string $groupId): void
    {
        $group = collect($this->groups)->firstWhere('id', $groupId);
        if (!$group) {
            return;
        }
        $this->targetGroupId = $group['id'];
        $this->targetGroupName = $group['subject'];
        $this->messageBody = '';
        $this->resetValidation();
        $this->isSendMessageModalOpen = true;
    }

    public function closeSendMessageModal(): void
    {
        $this->isSendMessageModalOpen = false;
    }

    public function sendMessage(): void
    {
        $this->validate(['messageBody' => 'required|string|max:4000'], ['messageBody.required' => 'Isi pesan tidak boleh kosong.']);

        try {
            WhatsAppGateway::sendText($this->targetGroupId, $this->messageBody . "\n\n— dikirim oleh " . Auth::user()->name);
            $this->isSendMessageModalOpen = false;
            $this->dispatch('swal:success', ['title' => 'Terkirim', 'text' => "Pesan terkirim ke grup {$this->targetGroupName}."]);
        } catch (Throwable $e) {
            $this->addError('messageBody', $e->getMessage());
        }
    }

    public function render()
    {
        $term = mb_strtolower(trim($this->groupSearch));
        $groups = $term === ''
            ? $this->groups
            : array_values(array_filter($this->groups, fn ($g) => str_contains(mb_strtolower($g['subject']), $term) || str_contains($g['id'], $term)));

        return view('livewire.admin.whatsapp', [
            'filteredGroups' => $groups,
            'stateLabel' => WhatsAppGateway::STATE_LABELS[$this->state] ?? ($this->state === 'loading' ? 'Memeriksa…' : $this->state),
            'instanceName' => WhatsAppGateway::instance(),
            'apiUrl' => config('services.whatsapp.url'),
        ]);
    }
}
