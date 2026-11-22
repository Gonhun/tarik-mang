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

    /** Pesan bila workflow lisensi belum siap. */
    public $workflowWarning = null;

    public function mount($id = NULL)
    {
        $this->workflowWarning = \App\Support\Workflow::problem('REQUEST_LICENSE', Auth::user());
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
        // Workflow belum diatur / approver tidak lengkap -> WorkflowNotConfigured, pengajuan di-rollback.
        // LicenseRequest tidak punya kolom jabatan/site, jadi konteks diambil dari pemohon.
        $requester = $request->user ?? Auth::user();
        $plan = \App\Support\Workflow::plan('REQUEST_LICENSE', $requester->position_id, $requester->jobsite_id);

        $firstItem = $request->items()->first();
        if (!$firstItem) {
            throw new \App\Support\WorkflowNotConfigured('Pengajuan lisensi tidak memiliki item.');
        }

        foreach ($plan as $p) {
            // Tahap yang approvernya = pemohon langsung disetujui otomatis.
            $auto = (int) $p['user']->id === (int) $requester->id;
            $firstItem->approvals()->create([
                'license_request_item_id' => $firstItem->id,
                'step_order' => $p['step_order'],
                'user_id' => $p['user']->id,
                'status' => $auto ? 'Approved' : 'Pending',
                'remarks' => $auto ? \App\Support\Workflow::AUTO_NOTE : null,
                'approved_at' => $auto ? now() : null,
            ]);
        }
    }

    public function save()
    {
        // Jangan proses sama sekali bila workflow lisensi belum siap.
        if ($problem = \App\Support\Workflow::problem('REQUEST_LICENSE', Auth::user())) {
            $this->workflowWarning = $problem;
            $this->dispatch('swal:error', ['title' => 'Workflow belum siap', 'text' => $problem]);
            return;
        }
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
            // Approval dibuat SEBELUM commit: bila workflow gagal, pengajuan ikut dibatalkan.
            $this->generateApproval($request);
            DB::commit();

            session()->flash('success', 'Pengajuan pembaruan lisensi sudah disimpan');
            return redirect()->route('licenses.main')->with('success', 'Pengajuan Renew lisensi berhasil dibuat dan sedang menunggu persetujuan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Renew lisensi gagal: ' . $e->getMessage(), ['line' => $e->getLine()]);
            session()->flash('error', $e->getMessage());
        }
    }

}
