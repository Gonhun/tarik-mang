<?php

namespace App\Livewire\LicensesRequests;

use App\Mail\MailLicenseRequest;
use App\Models\Department;
use App\Models\Jobsite;
use App\Models\LicenseRequest;
use App\Models\LicenseRequestApproval;
use App\Models\LicenseRequestItem;
use App\Models\SoftwareLicenses;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use Log;
use Mail;

class Main extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $openModal = false, $approveModal = false, $detailModal = false, $myDetailModal = false;
    public $departements = [], $jobsites = [];
    public $dept_filter, $site_filter, $year_filter, $status_filter = 'Pending', $search;
    public $isAdmin;
    public $remarks = '', $action = null, $itemId = null;
    public $item_req, $req;
    protected $paginationTheme = 'bootstrap';
    public $activeTab = 'me'; // Default tab

    public function mount()
    {

        $this->departements = Department::orderBy('code')->get(['id', 'name', 'code']);
        $this->jobsites = Jobsite::orderBy('code')->get(['id', 'name', 'code']);
        $this->dept_filter = Auth::user()->department_id;
        $this->site_filter = Auth::user()->jobsite_id;
        $this->isAdmin = Auth::user()->hasRole('Administrator IT') || Auth::user()->hasRole('Administrator');
    }

    public function render()
    {
        return view('livewire.licenses-requests.main', [
            'requests' => $this->dataRequestess(),
            'requestsApprove' => $this->dataRequestApproves(),
        ]);
    }

    public function setTab($tabId)
    {
        $this->activeTab = $tabId;
        $this->dispatch('tab-switched');
    }

    public function closeModal()
    {
        $this->openModal = false;
        $this->item_req = null;
    }

    public function showStatusHistory($id)
    {
        $this->openModal = true;
        $this->item_req = LicenseRequestItem::find($id);
    }

    public function showMyHistory($id)
    {
        $this->openModal = true;
        $this->item_req = LicenseRequestItem::find($id);
    }

    public function showApproveModal($id)
    {

        $this->approveModal = true;
        $this->action = 'approve';
        $this->itemId = $id;
    }

    public function showRejectModal($id)
    {
        $this->approveModal = true;
        $this->action = 'reject';
        $this->itemId = $id;

    }

    public function closeApproveModal()
    {
        $this->approveModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->remarks = '';
        $this->action = null;
        $this->itemId = null;
    }

    public function openDetailModal($id)
    {
        $this->detailModal = true;
        $this->req = LicenseRequest::with('items')->find($id);
    }

    public function closeDetailModal()
    {
        $this->detailModal = false;
        $this->req = null;
    }

    public function openMyDetailModal($id)
    {
        $this->myDetailModal = true;
        $this->req = LicenseRequest::with('items')->find($id);
    }

    public function closeMyDetailModal()
    {
        $this->myDetailModal = false;
        $this->req = null;
    }


    public function dataRequestess()
    {

        $requests = LicenseRequest::with('items')
            ->where('user_id', Auth::id())
            ->orderBy('id', 'desc');
        return $requests->paginate(10, ['*'], 'myPage');
    }

    public function dataRequestApproves()
    {
        $requestsApprove = LicenseRequest::with('items')
            ->whereHas('items', function ($subQuery) {
                $subQuery->when($this->search, function ($query) {
                    $query->whereHas('approvals', function ($query) {
                        $query->when(Auth::user()->department->code != 'IT', function ($query) {
                            $query->where('user_id', '=', Auth::id());
                        })->when($this->status_filter != "", function ($query) {
                            $query->where('status', $this->status_filter);
                        });
                    })->where('software_name', 'like', '%' . $this->search . '%')
                        ->orWhere('request_no', 'like', '%' . $this->search . '%')
                        ->orWhere('purpose', 'like', '%' . $this->search . '%')
                        ->orWhereHas('softwareCategory', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%');
                        });
                    ;
                });
            })
            // ->whereHas('items', function ($query) {
            //     $query->or
            // })
            ->orderBy('id', 'desc');
        // return $requestsApprove->paginate(10, ['*'], 'listPage');
        $paginatedDetails = $requestsApprove->paginate(10, ['*'], 'listPage');
        return $paginatedDetails;
    }

    public function reject()
    {
        $item = LicenseRequestItem::find($this->itemId);
        if (empty($this->remarks)) {
            $this->addError('remarks', 'Keterangan wajib diisi ketika menolak.');
            return;
        }

        // if ($item->approvals->where('status', 'Pending')->first()) {
        // dd($item->approvals);
        foreach ($item->approvals as $approval) {
            if ($approval->user_id == Auth::id()) {
                $approval->where('license_request_item_id', $item->id)->where('status', 'Pending')
                    ->update([
                        'status' => 'Rejected',
                        'remarks' => $this->remarks,
                        'approved_at' => now(),
                    ]);
            } else {
                $approval->where('license_request_item_id', $item->id)->where('status', 'Pending')
                    ->update([
                        'status' => 'Rejected',
                        'remarks' => "Di reject oleh " . Auth::user()->name . " dengan alasan: " . $this->remarks,
                        'approved_at' => null,
                    ]);
            }
        }
        $item->update([
            'status' => 'Rejected',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);
        $this->closeApproveModal();
        // $item->approvals()->where('status', 'Pending')->first()->update([
        //     'status' => 'Rejected',
        //     'remarks' => $this->remarks,
        //     'approved_at' => now(),
        // ]);
        // } else {
        //     abort(403, 'Anda tidak memiliki izin untuk menolak item ini atau item sudah diproses.');
        // }
    }

    public function cek($id)
    {
        $item = LicenseRequestItem::find($id);
        if ($item && $item->approvals->where('status', 'Pending')->count() === 0) {
            $item->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);
        }
    }

    public function approve()
    {
        $item = LicenseRequestItem::find($this->itemId)->with(['request','approvals']);
        if (!$item) {
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => "Items ID tidak ditemukan!"
            ]);
            $this->resetForm();
            return;
        }
        if ($item->approvals->where('user_id', Auth::id())->where('status', 'Pending')->first()) {
            $item->approvals()->where('user_id', Auth::id())->where('status', 'Pending')->first()->update([
                'status' => 'Approved',
                'approved_at' => now(),
                'remarks' => $this->remarks,
                'approved_by' => Auth::id(),
            ]);
            if ($item->approvals->where('status', 'Pending')->count() == 0) {
                $item->update([
                    'status' => 'Approved',
                    'approved_at' => now(),
                    'approved_by' => Auth::id(),
                ]);
            }
            $this->resetForm();

            $draftData = [
                'type' => 'APPROVE',
                'author' => $item->request->user->name,
                'approver' => $item->approvals->where('user_id', Auth::id()),
                'remarks' => $this->remarks,
                'draft' => $item->request
            ];
            // dd($draftData['draft']);
            $isSuccess = Mail::Mailer('smtp2')->to($item->request->user->karyawans->Email)->send(new MailLicenseRequest($draftData));
            if ($isSuccess) {
                $this->dispatch('swal:success', [
                    'title' => 'Berhasil!',
                    'text' => "Pesan berhasil dikirim ke '{$item->request->user->name}'",
                ]);
            } else {
                $this->dispatch('swal:error', [
                    'title' => 'Error!',
                    'text' => "Gagal Mengirim Pesan Follow Up!",
                ]);
            }

            // $listCount = LicenseRequestApproval::where('license_request_item_id', '=', $this->itemId)->count();
            // $approvalCount = LicenseRequestApproval::where('license_request_item_id', '=', $this->itemId)
            //     ->where('status', '=', 'Approved')->count();
            // if ($listCount == $approvalCount) {
            //     $license = SoftwareLicenses::where('name', '=', $item->software_name)->first();
            //     if ($license) {
            //         // $license->update([
            //         //     'seats' => $license->seats + $item->quantity
            //         // ]);
            //     } else {
            //         SoftwareLicenses::create([
            //             'name' => $item->software_name,
            //             'category_id' => $item->software_category_id,
            //             'manufacture_id' => $item->software_manufacture_id,
            //             'product_key' => '',
            //             'order_number' => '',
            //             'purchase_cost' => 0,
            //             'purchase_date' => now(),
            //             'expiration_date' => null,
            //             'po_number' => '',
            //             'note' => 'dibuat dari pengajuan license dengan no ' . $item->request->request_no . ' oleh ' . $item->request->user_id,
            //             'seats' => $item->quantity,
            //             'create_by' => ''
            //         ]);
            //     }
            // }

        } else {
            abort(403, 'Anda tidak memiliki izin untuk menyetujui item ini atau item sudah diproses.');
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
