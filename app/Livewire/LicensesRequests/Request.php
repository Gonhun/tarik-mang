<?php

namespace App\Livewire\LicensesRequests;

use App\Mail\MailLicenseRequest;
use App\Models\LicenseRequest;
use App\Models\LicenseRequestApproval;
use App\Models\SoftwareCategory;
use App\Models\SoftwareManufacturer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mail;

class Request extends Component
{
    use WithFileUploads;
    public $items = [];
    public $categorys, $manufactures, $needed_date, $purpose, $ba_file_name, $ba_file_path;
    public $ba_file;
    public function mount()
    {
        $this->categorys = SoftwareCategory::orderBy('name')->get();
        $this->manufactures = SoftwareManufacturer::orderBy('name')->get();
        $this->needed_date = Carbon::now()->addDays(14)->format('Y-m-d');
        $this->addItem();
    }
    public function render()
    {
        return view('livewire.licenses-requests.request');
    }
    public function addItem()
    {
        $this->items[] = [
            'software_category_id' => '',
            'software_manufacture_id' => '',
            'software_name' => '',
            'quantity' => 1,
            'remarks' => '',
            'no_pr' => NULL,
            'no_po' => NULL,
            'no_accounting' => NULL,
            'price' => NULL,
        ];
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
            'ba_file.*' => 'nullable|file|mimes:pdf,doc,docx,png,jpg|max:2048',
            // Validasi untuk item-item yang diajukan
            'items' => 'required|array|min:1',
            'items.*.software_category_id' => 'required|exists:software_category,id',
            'items.*.software_manufacture_id' => 'required|exists:software_manufacturer,id',
            'items.*.software_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.remarks' => 'nullable|string|min:20|max:500',
            'items.*.price' => 'required|numeric|min:10',
        ];
    }
    protected function messages(): array
    {
        return [
            'purpose.required' => 'Tujuan pengajuan wajib diisi.',
            'items.required' => 'Minimal satu item software harus diajukan.',
            'items.min' => 'Minimal satu item aset harus diajukan.',
            'items.*.software_category_id.required' => 'Kategori Software untuk item .',
            'items.*.software_manufacture_id.required' => 'Pabrikan Software untuk item.',
            'items.*.software_name.required' => 'Nama Software untuk item #:position wajib diisi.',
            'items.*.quantity.required' => 'Jumlah Software untuk item #:position wajib diisi.',
            'items.*.remarks.max' => 'Catatan untuk item #:position maksimal 500 karakter.',
            'items.*.remarks.min' => 'Catatan untuk item #:position minimal 20 karakter.',
            'items.*.price.required' => 'Harga per item untuk item #:position wajib diisi.',
            'items.*.price.numeric' => 'Harga per item untuk item #:position harus berupa angka.',
            'items.*.price.min' => 'Harga per item untuk item #:position minimal 10.',
        ];
    }
    public function submitRequest()
    {
        $validatedData = $this->validate();
        try {
            $assetRequest = DB::transaction(function () use ($validatedData) {
                $requester = Auth::user();
                $request = LicenseRequest::create([
                    'request_no' => $this->generateRequestNumber(),
                    'user_id' => $requester->id,
                    'jobsite_id' => $requester->jobsite_id,
                    'request_date' => now(),
                    'needed_date' => $validatedData['needed_date'],
                    'purpose' => $validatedData['purpose'],
                ]);

                // $request = new LicenseRequest([
                //     'request_no' => $this->generateRequestNumber(),
                //     'user_id' => $requester->id,
                //     'jobsite_id' => $requester->jobsite_id,
                //     'request_date' => now(),
                //     'needed_date' => $validatedData['needed_date'],
                //     'purpose' => $validatedData['purpose'],
                // ]);

                $draftData = [
                    'type' => 'SUBMIT',
                    'author' => $request->user->name,
                    'draft' => $request
                ];
                // dd($draftData['draft']);
                $isSuccess = Mail::Mailer('smtp2')->to($requester->karyawans->Email)->send(new MailLicenseRequest($draftData));

                if ($this->ba_file) {
                    // Normalisasi: bungkus ke array jika hanya 1 file, agar foreach tidak error
                    $files = is_array($this->ba_file) ? $this->ba_file : [$this->ba_file];

                    foreach ($files as $file) {
                        $name = $file->getClientOriginalName();
                        $path = $file->storeAs('request_license/ba_license', $name, 'public');
                        $request->attachments()->create([
                            'doc_file_path' => $path,
                            'doc_file_name' => $name,
                        ]);
                    }
                }

                foreach ($validatedData['items'] as $itemData) {
                    $request->items()->create($itemData);
                }
                $this->generateApproval($request);


                //4. Kirim notifikasi ke approver pertama
                $firstApprover = $request->items()->first()->approvals()->where('step_order', 1)->first();
                if ($firstApprover && $firstApprover->user_id) {
                    $approverData = [
                        'type' => 'OUTSTANDING_APPROVER',
                        'approver' => $firstApprover->approver->name,
                        'draft' => $firstApprover->request
                    ];
                    // dd($draftData['draft']);
                    if ($firstApprover && $firstApprover->approver->karyawans->Email) {
                        $isApproverSuccess = Mail::Mailer('smtp2')->to($firstApprover->approver->karyawans->Email)->send(new MailLicenseRequest($approverData));
                    }
                }

                return $request;
            });
            $this->resetForm();
            return redirect()->route('requests.license.main')->with('success', 'Pengajuan lisensi berhasil dibuat dan sedang menunggu persetujuan.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    protected function generateRequestNumber()
    {
        $jobsite = Auth::user()->jobsite->code;
        $prefix = "REQ/LIC/" . $jobsite . "/" . date('ym') . "/"; // Format REQ/2506/XXXX
        $lastRequest = LicenseRequest::where('request_no', 'LIKE', $prefix . '%')->orderBy('id', 'desc')->first();
        $nextSequence = 1;
        if ($lastRequest) {

            $lastSequence = (int) substr($lastRequest->request_no, -4);
            $nextSequence = $lastSequence + 1;
        }
        return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }
    public function resetForm()
    {
        $this->items = [];
        $this->purpose = '';
        $this->needed_date = Carbon::now()->addDays(14)->format('Y-m-d');
    }
    protected function generateApproval(LicenseRequest $request)
    {
        $workflow = findWorkflowForContext('REQUEST_LICENSE', $request->position_id, $request->jobsite_id);
        if (!$workflow || $workflow->steps->isEmpty()) {
            throw new \Exception('Alur persetujuan belum didefinisikan. Hubungi Administrator.');
        }
        foreach ($workflow->steps as $step) {
            $approverPosition = $step->approverPositionLevel;
            $approverDepartment = $step->department_id;

            $roleName = $approverPosition->name;

            // Logging step info
            Log::info('LicenseRequest Approval Step', [
                'step_id' => $step->id,
                'step_order' => $step->step_order,
                'roleName' => $roleName,
                'approverDepartment' => $approverDepartment,
                'user_id' => $step->user_id,
            ]);

            // Skip Project Manager if jobsite is SMD or JKT
            if ($roleName === 'Kepala Proyek' && in_array($request->jobsite->code, ['SMD', 'JKT'])) {
                Log::info('Skipping Kepala Proyek for SMD/JKT', [
                    'jobsite_code' => $request->jobsite->code,
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
                    $request->items()->first()->approvals()->create([
                        'license_request_item_id' => $request->items()->first()->id,
                        'step_order' => $step->step_order,
                        'user_id' => $approverUser->id,
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
                    ->where('jobsite_id', $request->jobsite_id);
            } else {
                $userQuery
                    ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                    ->where('jobsite_id', $request->jobsite_id);
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
                $request->items()->first()->approvals()->create([
                    'license_request_item_id' => $request->items()->first()->id,
                    'step_order' => $step->step_order,
                    'user_id' => $approverUser->id,
                    'status' => 'Pending',
                ]);
                Log::info('Approval created', [
                    'step_id' => $step->id,
                    'user_id' => $approverUser->id,
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
    }

    public function sendNotificationToApprover(LicenseRequestApproval $approval)
    {
        if ($approval->approver && $approval->approver->karyawans->Email) {
            // $requesterName = $approval->request->request->user->name;
            // $message = "Yth. Bapak/Ibu {$approval->approver->name},\n\n";
            // $message .= "Pengajuan Lisensi No. *{$approval->request->request->request_number}* dari Sdr/i *{$requesterName}* memerlukan persetujuan Anda sebagai *{$approval->approver_role_name}*.\n\n";
            // $message .= "Silakan periksa di sistem AMS.\n https://ams.binasaranasukses.co.id/license/request \nTerima kasih.";

            // // Panggil helper global
            // sendMessageWithWhatsApp("082311521525", $message);
            $draftData = [
                'type' => 'OUTSTANDING_APPROVER',
                'approver' => $approval->approver->name,
                'draft' => $approval->request
            ];
            // dd($draftData['draft']);
            $isSuccess = Mail::Mailer('smtp2')->to($approval->approver->karyawans->Email)->send(new MailLicenseRequest($draftData));
            if ($isSuccess) {
                $this->dispatch('swal:success', [
                    'title' => 'Berhasil!',
                    'text' => "Pesan berhasil dikirim ke '{$approval->approver->name}'",
                ]);
            } else {
                $this->dispatch('swal:error', [
                    'title' => 'Error!',
                    'text' => "Gagal Mengirim Pesan Follow Up!",
                ]);
            }


        } else {
            Log::error("Gagal kirim notifikasi: Approver atau nomor WhatsApp tidak ditemukan untuk approval ID #{$approval->id}");
        }
    }
}
