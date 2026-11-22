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
    public $items = [], $itne = [];
    public $allTypes = [], $allBrands = [], $allSeries = [];
    public $ba_damage, $ba_budget_outside;
    public $ba_damage_path, $ba_damage_name, $ba_budget_outside_path, $ba_budget_outside_name;
    public function mount()
    {
        $this->allTypes = AssetType::orderBy('name')->get();
        $this->allBrands = AssetBrand::orderBy('name')->get();
        $this->allSeries = AssetSeries::orderBy('name')->get();
        $this->needed_date = Carbon::now()->addDays(14)->format('Y-m-d');
        // $this->request_active = AssetRequest::where('requester_user_id',Auth::id())->where('status','like','Pending%')->exists();

        $query = User::where('position_id', 6)
            ->where('id', '<>', Auth::id());

        if (Auth::user()->position_id != 6) {
            $query->where('jobsite_id', Auth::user()->jobsite_id);
        }
        $this->itne = $query->get();

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
    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }
    protected function rules(): array
    {
        return [
            'purpose' => 'required|string|min:10|max:1000',
            'needed_date' => 'required|date|after_or_equal:request_date',
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
                $request = new AssetRequest([
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

                $draftData = [
                    'type' => 'SUBMIT',
                    'author' => $request->requester->name,
                    'draft' => $request
                ];
                // dd($requester->karyawans->Email);
                if($requester && $requester->karyawans->Email){
                    $isRequesterSuccess = Mail::Mailer('smtp2')->to($requester->karyawans->Email)->send(new MailAssetRequest($draftData));
                }

                // 2. Simpan item detail
                foreach ($validatedData['items'] as $itemData) {
                   dd( $itemData);
                    $request->items()->create($itemData);
                }
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
                // 3. Workflow approval
                $workflow = findWorkflowForContext('REQUEST_ASSET', $requester->position_id, $requester->jobsite_id);
                if (!$workflow || $workflow->steps->isEmpty()) {
                    throw new \Exception('Alur persetujuan belum didefinisikan. Hubungi Administrator.');
                }

                foreach ($workflow->steps as $step) {
                    $approverPosition = $step->approverPositionLevel;
                    $approverDepartment = $step->department_id;

                    $roleName = $approverPosition->name;

                    // Logging step info
                    Log::info('AssetRequest Approval Step', [
                        'step_id' => $step->id,
                        'step_order' => $step->step_order,
                        'roleName' => $roleName,
                        'approverDepartment' => $approverDepartment,
                        'user_id' => $step->user_id,
                    ]);

                    // Skip Project Manager if jobsite is SMD or JKT
                    if ($roleName === 'Kepala Proyek' && in_array($requester->jobsite->code, ['SMD', 'JKT'])) {
                        Log::info('Skipping Kepala Proyek for SMD/JKT', [
                            'jobsite_code' => $requester->jobsite->code,
                            'roleName' => $roleName,
                        ]);
                        continue;
                    }

                    // Base query
                    $userQuery = User::query();
                    if ($step->user_id) {
                        $userQuery->where('id', $step->user_id);
                        $approverUser = $userQuery->first();
                        if ($approverUser) {
                            $request->approvals()->create([
                                'step_order' => $step->step_order,
                                'approver_role_name' => $roleName,
                                'approver_user_id' => $approverUser->id,
                                'status' => 'Pending',
                            ]);
                            Log::info('Approval created', [
                                'step_id' => $step->id,
                                'approver_user_id' => $approverUser->id,
                                'roleName' => $roleName,
                            ]);
                            continue;
                        } else {
                            Log::error("Konfigurasi error: Approver untuk jabatan '{$roleName}' tidak dapat ditemukan. Pengajuan dibatalkan.", [
                                'step_id' => $step->id,
                                'roleName' => $roleName,
                            ]);
                            throw new \Exception("Konfigurasi error: Approver untuk jabatan '{$roleName}' tidak dapat ditemukan. Pengajuan dibatalkan.");
                        }

                    }
                    if (!empty($approverDepartment)) {

                        $userQuery->where('department_id', $approverDepartment)
                            ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                            ->where('jobsite_id', $requester->jobsite_id);
                    } else {
                        $userQuery
                            ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                            ->where('jobsite_id', $requester->jobsite_id);
                    }

                    $approverUser = $userQuery->first();

                    Log::info('ApproverUser after main query', [
                        'step_id' => $step->id,
                        'approverUser' => $approverUser ? $approverUser->name : null,
                    ]);

                    if (!$approverUser) {
                        $fallbackQuery = User::query();
                        if (!empty($approverDepartment)) {
                            $fallbackQuery->where('department_id', $approverDepartment)
                                ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                                ->whereHas('jobsite', fn($q) => $q->where('code', 'JKT'));
                        } else {
                            $fallbackQuery
                                ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                                ->whereHas('jobsite', fn($q) => $q->where('code', 'JKT'));
                        }


                        $approverUser = $fallbackQuery->first();

                        Log::info('ApproverUser after fallback query', [
                            'step_id' => $step->id,
                            'approverUser' => $approverUser ? $approverUser->name : null,
                        ]);
                    }

                    if ($approverUser) {
                        $request->approvals()->create([
                            'step_order' => $step->step_order,
                            'approver_role_name' => $roleName,
                            'approver_user_id' => $approverUser->id,
                            'status' => 'Pending',
                        ]);
                        Log::info('Approval created', [
                            'step_id' => $step->id,
                            'approver_user_id' => $approverUser->id,
                            'roleName' => $roleName,
                        ]);
                    } else {
                        Log::error("Konfigurasi error: Approver untuk jabatan '{$roleName}' tidak dapat ditemukan. Pengajuan dibatalkan.", [
                            'step_id' => $step->id,
                            'roleName' => $roleName,
                        ]);
                        throw new \Exception("Konfigurasi error: Approver untuk jabatan '{$roleName}' tidak dapat ditemukan. Pengajuan dibatalkan.");
                    }
                }

                return $request;
            });

            // 4. Kirim notifikasi ke approver pertama
            $firstApprover = $assetRequest->approvals()->where('step_order', 1)->first();
            if ($firstApprover && $firstApprover->approver) {
                 $approverData = [
                    'type' => 'OUTSTANDING_APPROVER',
                    'approver' => $firstApprover->approver->name,
                    'draft' => $firstApprover
                ];
                if($firstApprover && $firstApprover->approver->karyawans->Email){
                    $isApproverSuccess = Mail::Mailer('smtp2')->to($firstApprover->approver->karyawans->Email)->send(new MailAssetRequest($approverData));
                }
            }

            return redirect()
                ->route('asset-requests.dashboard')
                ->with('message', 'Pengajuan aset berhasil dibuat dan sedang menunggu persetujuan.');

        } catch (\Exception $e) {
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
                $isApproverSuccess = Mail::Mailer('smtp2')->to("arrachmansandry@gmail.com")->send(new MailRequest($approverData));

                $draftData = [
                    'type' => 'SUBMIT',
                    'author' => $approval->assetRequest->requester->name,
                    'draft' => $approval
                ];
                // dd($user,$data);
                $isRequesterSuccess = Mail::Mailer('smtp2')->to("arrachmansandry@gmail.com")->send(new MailRequest($draftData));

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
        return view('livewire.asset-requests.create-asset-request-form');
    }
}