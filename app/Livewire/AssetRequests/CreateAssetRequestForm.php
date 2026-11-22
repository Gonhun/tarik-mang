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

    public function mount()
    {
        $this->workflowWarning = \App\Support\Workflow::problem('REQUEST_ASSET', Auth::user());
        // Katalog tipe/merek/seri tidak disimpan di state (dimuat di render sesuai pilihan).
        $this->needed_date = Carbon::now()->addDays(14)->format('Y-m-d');
        $this->loadNeededAssetsForRequester();
    }
    public function loadNeededAssetsForRequester()
    {
        $this->items = []; // Kosongkan item
        $user = Auth::user()->load('position.assetStandards', 'assetsHeld.assetSeries');

        if (!$user || !$user->position) {
            $this->addItem();
            return;
        }

        $heldSeriesIds = $user->assetsHeld->whereNotNull('asset_series_id')->pluck('asset_series_id')->unique()->toArray();

        foreach ($user->position->assetStandards as $standard) {
            if ($standard->asset_series_id && !in_array($standard->asset_series_id, $heldSeriesIds)) {
                $this->items[] = [
                    'asset_type_id' => $standard->asset_type_id,
                    'asset_brand_id' => $standard->asset_brand_id,
                    'asset_series_id' => $standard->asset_series_id,
                    'quantity' => $standard->quantity,
                    'item_description' => $standard->specifications_notes,
                    'is_standard' => true,
                ];
            }
        }
        if (empty($this->items))
            ;//$this->addItem()
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
        if ($this->workflowWarning = \App\Support\Workflow::problem('REQUEST_ASSET', Auth::user())) {
            $this->dispatch('swal:error', ['title' => 'Workflow belum siap', 'text' => $this->workflowWarning]);
            return;
        }
        $validatedData = $this->validate();

        try {

            if ($this->ba_damage) {
                $this->ba_damage_path = $this->ba_damage->store('request_asset/ba_damage', 'public');
                $this->ba_damage_name = $this->ba_damage->getClientOriginalName();
            } else {
                $this->ba_damage_path = null;
                $this->ba_damage_name = null;
            }
            if ($this->ba_budget_outside) {
                // Hapus file lama jika ada saat proses update
                $this->ba_budget_outside_path = $this->ba_budget_outside->store('request_asset/ba_budget_outside', 'public');
                $this->ba_budget_outside_name = $this->ba_budget_outside->getClientOriginalName();
            } else {
                $this->ba_budget_outside_path = null;
                $this->ba_budget_outside_name = null;
            }
            $assetRequest = DB::transaction(function () use ($validatedData) {
                $requester = Auth::user();

                // 1. Simpan header
                // $request = AssetRequest::create([
                //     'request_number' => $this->generateRequestNumber(),
                //     'requester_user_id' => $requester->id,
                //     'jobsite_id' => $requester->jobsite_id,
                //     'request_date' => now(),
                //     'needed_date' => $this->needed_date,
                //     'purpose' => $this->purpose,
                //     'status' => 'Pending IT Item Validation',
                //     'ba_damage_path' => $this->ba_damage_path,
                //     'ba_damage_name' => $this->ba_damage_name,
                //     'ba_budget_outside_path' => $this->ba_budget_outside_path,
                //     'ba_budget_outside_name' => $this->ba_budget_outside_name,
                // ]);
                $request = AssetRequest::create([
                    'request_number' => $this->generateRequestNumber(),
                    'requester_user_id' => $requester->id,
                    'jobsite_id' => $requester->jobsite_id,
                    'request_date' => now(),
                    'needed_date' => $this->needed_date,
                    'purpose' => $this->purpose,
                    'status' => 'Pending IT Item Validation',
                    'ba_damage_path' => $this->ba_damage_path,
                    'ba_damage_name' => $this->ba_damage_name,
                    'ba_budget_outside_path' => $this->ba_budget_outside_path,
                    'ba_budget_outside_name' => $this->ba_budget_outside_name,
                ]);

                // 2. Simpan item detail (dipecah per unit: quantity N -> N baris dengan quantity 1)
                foreach ($validatedData['items'] as $itemData) {

                    $qty = (int) $itemData['quantity']; // jumlah yang diajukan

                    // hapus qty asli supaya tidak double
                    unset($itemData['quantity']);

                    for ($i = 0; $i < $qty; $i++) {
                        $request->items()->create(array_merge(
                            $itemData,
                            ['quantity' => 1]
                        ));
                    }
                }
                // 3. Workflow approval. Workflow belum diatur / approver tidak lengkap ->
                //    WorkflowNotConfigured dan seluruh pengajuan di-rollback (tidak ada yang lolos tanpa approver).
                $plan = \App\Support\Workflow::plan('REQUEST_ASSET', $requester->position_id, $requester->jobsite_id);
                // Tahap yang approvernya = pemohon langsung disetujui otomatis.
                $request->approvals()->createMany(\App\Support\Workflow::approvalRows($plan, $requester->id));

                return $request;
            });

            // 4. Email ke requester (setelah data benar-benar tersimpan)
            $requester = Auth::user();
            $requesterEmail = $requester->karyawans->Email ?? null;
            if ($requesterEmail) {
                try {
                    Mail::Mailer('smtp2')->to($requesterEmail)->send(new MailAssetRequest([
                        'type' => 'SUBMIT',
                        'author' => $requester->name,
                        'draft' => $assetRequest,
                    ]));
                } catch (\Throwable $mailError) {
                    Log::error('Gagal kirim email SUBMIT pengajuan aset: ' . $mailError->getMessage());
                }
            }

            // 5. Kirim notifikasi ke approver pertama
            // Approver pertama yang masih harus bertindak (tahap otomatis dilewati).
            $firstApprover = $assetRequest->approvals()->where('status', 'Pending')->get()->sortBy('step_order')->first();
            if ($firstApprover && $firstApprover->approver) {
                 $approverData = [
                    'type' => 'OUTSTANDING_APPROVER',
                    'approver' => $firstApprover->approver->name,
                    'draft' => $firstApprover
                ];
                $approverEmail = $firstApprover->approver->karyawans->Email ?? null;
                if ($approverEmail) {
                    try {
                        Mail::Mailer('smtp2')->to($approverEmail)->send(new MailAssetRequest($approverData));
                    } catch (\Throwable $mailError) {
                        Log::error('Gagal kirim email approver pengajuan aset: ' . $mailError->getMessage());
                    }
                }
            }

            return redirect()
                ->route('asset-requests.dashboard')
                ->with('message', 'Pengajuan aset berhasil dibuat dan sedang menunggu persetujuan.');

        } catch (\Exception $e) {
            // Pengajuan gagal: hapus file yang sudah terlanjur diupload.
            foreach ([$this->ba_damage_path, $this->ba_budget_outside_path] as $uploaded) {
                if ($uploaded) {
                    Storage::disk('public')->delete($uploaded);
                }
            }
            Log::error('Gagal membuat pengajuan aset: ' . $e->getMessage());
            session()->flash('error', $e->getMessage());
        }
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

    public function generateRequestNumber()
    {
        $jobsite = Auth::user()->jobsite->code;
        $prefix = "REQ/" . $jobsite . "/" . date('ym') . "/"; // Format REQ/2506/XXXX
        $lastRequest = AssetRequest::where('request_number', 'LIKE', $prefix . '%')->orderByDesc('id')->first();
        $nextSequence = 1;
        if ($lastRequest) {
            $lastSequence = (int) substr($lastRequest->request_number, -4);
            $nextSequence = $lastSequence + 1;
        }
        return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $user = Auth::user()->loadMissing(['position.assetStandards.assetType:id,name', 'position.assetStandards.assetSeries:id,name', 'jobsite:id,code,name']);

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
        ]);
    }
}