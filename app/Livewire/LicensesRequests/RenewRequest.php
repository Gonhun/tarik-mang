<?php

namespace App\Livewire\LicensesRequests;

use App\Models\LicenseRequest;
use App\Models\SoftwareLicenses;
use App\Models\User;
use Auth;
use DB;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;
use Log;

class RenewRequest extends Component
{
    use WithFileUploads;

    public $license_id, $data_license;
    public $software_name, $software_category, $software_manufacture, $software_seat, $software_cost, $renew_desc, $license_type;
    public $ba_file;

    public function mount($id = NULL)
    {
        $this->license_id = $id;
        $this->data_license = SoftwareLicenses::with(['softwareCategory', 'softwareManufacturer'])->where('id', '=', $this->license_id)->first();
        $this->license_type = 'Renew';
        if ($this->data_license) {
            $this->software_name = $this->data_license->name;
            $this->software_category = $this->data_license->softwareCategory->name;
            $this->software_manufacture = $this->data_license->softwareManufacturer->name;
            $this->software_seat = 1;
            $this->software_cost = $this->data_license->purchase_cost ?? 0;
        }
    }

    public function render()
    {
        return view('livewire.licenses-requests.renew-request');
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

    protected function generateApproval(LicenseRequest $request)
    {
        $workflow = findWorkflowForContext('REQUEST_LICENSE', $request->position_id, $request->jobsite_id);
        if (!$workflow || $workflow->steps->isEmpty()) {
            throw new Exception('Alur persetujuan belum didefinisikan. Hubungi Administrator.');
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
                    throw new Exception("Konfigurasi error: Approver untuk jabatan '{$roleName}' tidak dapat ditemukan. Pengajuan dibatalkan.");
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
                throw new Exception("Konfigurasi error: Approver untuk jabatan '{$roleName}' tidak dapat ditemukan. Pengajuan dibatalkan.");
            }
        }
    }

    public function save()
    {
        try {
            DB::beginTransaction();
            $requester = Auth::user();
            $request = LicenseRequest::create([
                'request_no' => $this->generateRequestNumber(),
                'user_id' => $requester->id,
                'jobsite_id' => $requester->jobsite_id,
                'license_type' => $this->license_type,
                'purpose' => $this->renew_desc,
                'request_date' => now(),
                'status' => 'draft'
            ]);

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

            if ($this->data_license) {
                $request->items()->create([
                    'software_category_id' => $this->data_license->category_id,
                    'software_name' => $this->software_name,
                    'quantity' => $this->software_seat,
                    'remarks' => $this->renew_desc,
                    'status' => 'Pending',
                    'created_at' => now(),
                ]);
            }
            $request->save();
            DB::commit();
            
            $this->generateApproval($request);
            session()->flash('success', 'Pengajuan pembaruan lisensi sudah disimpan');
            return redirect()->route('licenses.main')->with('success', 'Pengajuan Renew lisensi berhasil dibuat dan sedang menunggu persetujuan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage(). 'on line' .$e->getLine());
        }
    }

}
