<?php

namespace App\Livewire\AssetRequests;

use App\Mail\MailAssetRequest;
use App\Mail\MailRequest;
use Exception;
use Livewire\Component;
use App\Models\{Asset, AssetBrand, AssetTransaction, AssetRequest, AssetRequestApproval, AssetRequestItem, AssetSeries, AssetType, Position, PositionAssetStandard, Role, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\{Auth, DB, Log};
use Livewire\WithFileUploads;
use Livewire\Attributes\Locked;
use App\Services\AssetRequestService;
use Illuminate\Support\Facades\Storage;
use Mail;
class CreateAssetRequestForm extends Component
{
    use WithFileUploads;
    public $purpose = '', $needed_date, $itsite, $remarks, $request_active;
    public $items = [];
    public $ba_damage, $ba_budget_outside;
    public $ba_damage_path, $ba_damage_name, $ba_budget_outside_path, $ba_budget_outside_name;
    /** Pesan bila workflow pengajuan aset belum siap (form tidak bisa dikirim). */
    public $workflowWarning = null;

    /** Boleh mengajukan untuk karyawan lain di departemen yang sama. */
    public const ON_BEHALF_PERMISSION = AssetRequestService::ON_BEHALF_PERMISSION;

    /** Penerima aset bila mengajukan untuk orang lain (null = diri sendiri). Hanya diubah lewat selectBeneficiary(). */
    #[Locked]
    public $forUserId = null;
    public $beneficiarySearch = '';

    public function mount()
    {
        $this->workflowWarning = \App\Support\Workflow::problem('REQUEST_ASSET', $this->requester());
        // Katalog tipe/merek/seri tidak disimpan di state (dimuat di render sesuai pilihan).
        $this->needed_date = Carbon::now()->addDays(14)->format('Y-m-d');
        $this->loadNeededAssetsForRequester();
    }
    public function loadNeededAssetsForRequester()
    {
        $user = $this->requester();
        $this->items = app(AssetRequestService::class)->standardItems($user);
        if (!$user->position) {
            $this->addItem();
        }
    }

    // ------------------------------------------------------------------
    // Mengajukan untuk orang lain
    // ------------------------------------------------------------------

    public function canSubmitForOthers(): bool
    {
        return AssetRequestService::canSubmitForOthers(Auth::user());
    }

    /** Karyawan aktif satu departemen dengan pengisi form (selain dirinya). */
    protected function eligibleBeneficiaries()
    {
        return AssetRequestService::eligibleBeneficiaries(Auth::user());
    }

    /**
     * Pemohon pada data pengajuan = penerima aset: karyawan yang dipilih, atau diri sendiri.
     * Selalu dicek ulang (permission & departemen) agar tidak bisa dimanipulasi dari browser.
     */
    protected function requester(): User
    {
        return AssetRequestService::resolveRequester(Auth::user(), $this->forUserId) ?? Auth::user();
    }

    public function selectBeneficiary($userId)
    {
        $user = $this->canSubmitForOthers() ? $this->eligibleBeneficiaries()->find($userId) : null;
        if (!$user) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dipilih', 'text' => 'Anda hanya bisa mengajukan untuk karyawan aktif di departemen Anda.']);
            return;
        }
        $this->forUserId = $user->id;
        $this->requesterChanged();
    }

    public function submitForSelf()
    {
        $this->forUserId = null;
        $this->requesterChanged();
    }

    /** Ganti penerima: workflow & item standar mengikuti jabatan penerima. */
    protected function requesterChanged(): void
    {
        $this->beneficiarySearch = '';
        $this->resetValidation();
        $this->workflowWarning = \App\Support\Workflow::problem('REQUEST_ASSET', $this->requester());
        $this->loadNeededAssetsForRequester();
    }

    public function addItem()
    {
        $this->items[] = ['asset_type_id' => '', 'asset_brand_id' => '', 'asset_series_id' => '', 'quantity' => 1, 'item_description' => '', 'is_standard' => false, 'gr_number' => NULL, 'price' => NULL];
    }
    /** Ganti tipe -> merek & seri lama tidak berlaku. Ganti merek -> seri lama tidak berlaku. */
    public function updatedItems($value, $key)
    {
        [$index, $field] = array_pad(explode('.', $key), 2, null);
        if (!isset($this->items[$index]) || !empty($this->items[$index]['is_standard'])) {
            return;
        }
        if ($field === 'asset_type_id') {
            $this->items[$index]['asset_brand_id'] = '';
            $this->items[$index]['asset_series_id'] = '';
        } elseif ($field === 'asset_brand_id') {
            $this->items[$index]['asset_series_id'] = '';
        }
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }
    protected function rules(): array
    {
        return [
            'purpose' => 'required|string|min:10|max:1000',
            'needed_date' => 'required|date|after_or_equal:today',
            'remarks' => 'nullable|string|max:1000',

            // Validasi untuk item-item yang diajukan
            'items' => 'required|array|min:1',
            'items.*.asset_type_id' => 'required|exists:asset_types,id',
            'items.*.asset_brand_id' => 'required|exists:asset_brands,id',
            'items.*.asset_series_id' => 'required|exists:asset_series,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
            'items.*.item_description' => 'nullable|string|max:500',
            'items.*.gr_number' => 'nullable|string|max:50',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.is_standard' => 'required|boolean',
            // Anda bisa menambahkan validasi untuk PIC jika di form Anda PIC dipilih manual
            // Contoh: 'kabag_pic_id' => 'nullable|exists:users,id',
            'ba_damage' => 'nullable|file|mimes:pdf,doc,docx,png,jpg|max:2048',
            'ba_budget_outside' => 'nullable|file|mimes:pdf,doc,docx,png,jpg|max:2048',
        ];
    }

    /**
     * Pesan error kustom untuk validasi (opsional).
     *
     * @return array
     */
    protected function messages(): array
    {
        return [
            'purpose.required' => 'Tujuan pengajuan wajib diisi.',
            'items.required' => 'Minimal satu item aset harus diajukan.',
            'items.min' => 'Minimal satu item aset harus diajukan.',
            'items.*.asset_type_id.required' => 'Tipe Aset untuk item #:position wajib dipilih.',
            'items.*.asset_brand_id.required' => 'Merek Aset untuk item #:position wajib dipilih.',
            'items.*.asset_series_id.required' => 'Seri Aset untuk item #:position wajib dipilih.',
            'items.*.quantity.required' => 'Jumlah untuk item #:position wajib diisi.',
            'items.*.is_standard.required' => 'Item harus standard/non-standard',
        ];
    }
    public function submitRequest()
    {
        $requester = $this->requester();
        if ($this->forUserId && (int) $requester->id !== (int) $this->forUserId) {
            $this->dispatch('swal:error', ['title' => 'Penerima tidak valid', 'text' => 'Karyawan yang dipilih tidak lagi bisa diajukan atas nama Anda. Pilih ulang penerima.']);
            return;
        }
        if ($this->workflowWarning = \App\Support\Workflow::problem('REQUEST_ASSET', $requester)) {
            $this->dispatch('swal:error', ['title' => 'Workflow belum siap', 'text' => $this->workflowWarning]);
            return;
        }
        $validatedData = $this->validate();

        try {
            app(AssetRequestService::class)->submit($requester, Auth::user(), $validatedData, $this->ba_damage ?: null, $this->ba_budget_outside ?: null);
        } catch (\Exception $e) {
            Log::error('Gagal membuat pengajuan aset: ' . $e->getMessage());
            session()->flash('error', $e->getMessage());
            return;
        }

        return redirect()
            ->route('asset-requests.dashboard')
            ->with('message', 'Pengajuan aset berhasil dibuat dan sedang menunggu persetujuan.');
    }
    public function resetFormAndLoadStandards()
    {
        // Reset form fields
        $this->purpose = '';
        $this->needed_date = '';
        $this->items = [];
        $this->remarks = '';
        // Load standard items if any
        $this->loadNeededAssetsForRequester();
    }


    /**
     * Membuat tahapan approval yang diperlukan untuk transaksi mutasi.
     * Ini adalah "Master Validasi" Anda.
     */
    // protected function generateApprovalWorkflow(AssetRequest $request, User $requester)
    // {
    //     $usere=Auth::user();
    //     // Definisikan urutan peran approver Anda di sini
    //     $approvalRoles = [
    //         'Staff IT', 
    //         'Staff DTC', 
    //         'Kabag', 
    //         'Project Manager', 
    //         'KASI CC', 
    //         'PJO IT', 
    //         'KADEP IT', 
    //         'DIRECTORS IT', 
    //         'DIRECTORS'
    //     ];

    //     $stepOrder = 1;

    //     foreach ($approvalRoles as $roleName) {
    //         if (
    //             $roleName === 'Project Manager' &&
    //             ($usere->jobsite->code === 'JKT' || $usere->position->name === 'Project Manager')
    //         ) {
    //             continue; // Lewati loop ini
    //         }

    //         $approverUser = $this->findApproverForRole($roleName, $requester);

    //         if ($approverUser) {
    //             $request->approvals()->create([
    //                 'step_order' => $stepOrder++,
    //                 'approver_role_name' => $roleName,
    //                 'approver_user_id' => $approverUser->id,
    //                 'status' => 'Pending',
    //             ]);
    //         } else {
    //             throw new \Exception("Konfigurasi error: Approver untuk peran '{$roleName}' tidak ditemukan. Pengajuan dibatalkan.");
    //         }


    //     }
    // }

    /**
     * Helper untuk menemukan user approver berdasarkan peran dan konteks pemohon.
     * Anda perlu menyesuaikan logika ini dengan struktur organisasi Anda.
     */
    protected function findApproverForRole(string $roleName, User $requester): ?User
    {

        // Atasan langsung pemohon
        if ($roleName === 'Kabag') {
            return $requester->manager;
        }
        if ($roleName === 'Staff IT') {
            return User::where('id', $this->itsite)
                ->first();
        }
        $role = Position::where('name', $roleName)->first();
        if (!$role)
            return null;

        // Untuk peran berbasis jobsite
        if (!in_array($roleName, ['KASI CC', 'PJO IT', 'KADEP IT', 'DIRECTORS IT', 'DIRECTORS'])) {

            if ($role && $requester->jobsite_id) {
                $user = User::where('jobsite_id', $requester->jobsite_id)
                    ->where('position_id', $role->id)
                    ->first();
                return $user;
            }
        }
        return User::where('position_id', $role->id)->first();

    }

    /**
     * Method untuk mengirim notifikasi WA (memanggil helper global)
     */
    protected function sendNotificationToApprover(AssetRequestApproval $approval)
    {
        try {
            if ($approval->approver && $approval->approver->whatsapp) {
                $requesterName = $approval->assetRequest->requester->name;
                $message = "Yth. Bapak/Ibu {$approval->approver->name},\n\n";
                $message .= "Pengajuan aset No. *{$approval->assetRequest->request_number}* dari Sdr/i *{$requesterName}* memerlukan persetujuan Anda sebagai *{$approval->approver_role_name}*.\n\n";
                $message .= "Silakan periksa di sistem AMS.\nams.binsaranasukses.co.id\nTerima kasih.";

                $approverData = [
                    'type' => 'OUTSTANDING_APPROVER',
                    'approver' => $approval->approver,
                    'draft' => $approval
                ];
                $approverEmail = $approval->approver->karyawans->Email ?? null;
                if ($approverEmail) {
                    Mail::Mailer('smtp2')->to($approverEmail)->send(new MailRequest($approverData));
                }

                $draftData = [
                    'type' => 'SUBMIT',
                    'author' => $approval->assetRequest->requester->name,
                    'draft' => $approval
                ];
                // dd($user,$data);
                $requesterEmail = $approval->assetRequest->requester->karyawans->Email ?? null;
                if ($requesterEmail) {
                    Mail::Mailer('smtp2')->to($requesterEmail)->send(new MailRequest($draftData));
                }

                // Mail::to($approval->approver->karyawans)->send(new MailRequest($draftData));
                // Panggil helper global
                // sendMessageWithWhatsApp($approval->approver->whatsapp, $message);
                // sendMessageWithWhatsAppV2(
                //     $approval->approver->whatsapp,
                //     $approval->approver->name,
                //     '9b3f1404-1315-4963-9525-9c6914fc8e35', // message template ID (contoh)
                //     [
                //         ['key' => '1', "value_text" => $approval->approver->name ],
                //         ['key' => '2', "value_text" => $approval->assetRequest->request_number ],
                //         ['key' => '3', "value_text" => $requesterName ],
                //         ['key' => '4', "value_text" => $approval->approver_role_name ],
                //         ['key' => '5', "value_text" => "ams.binsaranasukses.co.id/show/" . $approval->assetRequest->id ],
                //     ] // body params untuk template
                // );

                Log::info("Notifikasi WA dikirim ke " . $approval->approver->name . " (" . $approval->approver->whatsapp . ") untuk approval ID # " . $approval->id . " dengan pesan: " . $message);
            }
        } catch (Exception $e) {
            Log::error("Gagal kirim notifikasi: $e");
        }
    }

    protected function generateRequestNumber(?User $requester = null)
    {
        return app(AssetRequestService::class)->generateRequestNumber($requester ?? $this->requester());
    }

    public function render()
    {
        $user = $this->requester()->loadMissing(['position.assetStandards.assetType:id,name', 'position.assetStandards.assetSeries:id,name', 'jobsite:id,code,name']);
        $canSubmitForOthers = $this->canSubmitForOthers();

        // Pencarian penerima (hanya karyawan aktif satu departemen).
        $beneficiaryOptions = collect();
        $term = trim($this->beneficiarySearch);
        if ($canSubmitForOthers && mb_strlen($term) >= 4) {
            $beneficiaryOptions = $this->eligibleBeneficiaries()
                ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('nik', 'like', "%{$term}%"))
                ->with(['position:id,name', 'jobsite:id,code'])
                ->orderBy('name')->limit(10)
                ->get(['id', 'name', 'nik', 'position_id', 'jobsite_id']);
        }

        // Katalog hanya yang relevan dengan pilihan saat ini.
        $typeIds = collect($this->items)->pluck('asset_type_id')->filter()->unique();
        $brandIds = collect($this->items)->pluck('asset_brand_id')->filter()->unique();
        $catalog = [
            'types' => AssetType::orderBy('name')->get(['id', 'name']),
            'brands' => $typeIds->isEmpty() ? collect() : AssetBrand::whereIn('asset_type_id', $typeIds)->orderBy('name')->get(['id', 'name', 'asset_type_id']),
            'series' => $brandIds->isEmpty() ? collect() : AssetSeries::whereIn('asset_brand_id', $brandIds)->orderBy('name')->get(['id', 'name', 'asset_brand_id', 'specifications']),
        ];

        // Alur persetujuan yang akan dipakai (pratinjau untuk pemohon).
        $approvalPreview = [];
        try {
            $approvalPreview = \App\Support\Workflow::plan('REQUEST_ASSET', $user->position_id, $user->jobsite_id);
        } catch (\App\Support\WorkflowNotConfigured $e) {
            // Pesan sudah ditampilkan lewat $workflowWarning.
        }

        // Standar aset jabatan & yang sudah dipegang (untuk penjelasan).
        $heldSeriesIds = Asset::where('holder_user_id', $user->id)->whereNotNull('asset_series_id')->pluck('asset_series_id')->unique()->all();
        $standards = ($user->position?->assetStandards ?? collect())->map(fn ($st) => [
            'label' => trim(($st->assetType->name ?? '-') . ' ' . ($st->assetSeries->name ?? '')),
            'quantity' => $st->quantity,
            'fulfilled' => $st->asset_series_id && in_array($st->asset_series_id, $heldSeriesIds),
        ]);

        return view('livewire.asset-requests.create-asset-request-form', [
            'catalog' => $catalog,
            'approvalPreview' => $approvalPreview,
            'standards' => $standards,
            'totalUnits' => collect($this->items)->sum(fn ($i) => max(0, (int) ($i['quantity'] ?? 0))),
            'hasJobdesc' => !empty($user->position?->job_description_name),
            'user' => $user,
            'forOther' => (int) $user->id !== (int) Auth::id(),
            'canSubmitForOthers' => $canSubmitForOthers,
            'beneficiaryOptions' => $beneficiaryOptions,
            'myDepartment' => $canSubmitForOthers ? Auth::user()->department?->name : null,
        ]);
    }
}