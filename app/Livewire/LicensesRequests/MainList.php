<?php

namespace App\Livewire\LicensesRequests;

use App\Models\Department;
use App\Models\Jobsite;
use App\Models\LicenseRequest;
use App\Models\LicenseRequestItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

class MainList extends Component
{
    use WithPagination;

    public $openModal = false;
    public $departements = [], $jobsites = [];
    public $dept_filter, $site_filter, $year_filter, $status_filter, $search;

    public $isAdmin;
    public $approveModal = false;
    public $remarks = '', $action = null, $itemId = null;
    public $item_req;
    public $listPage = 1;

    #[Lazy]
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
        return view('livewire.licenses-requests.main-list');
    }
    
    public function showStatusHistory($id)
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

    public function dataRequestApproves()
    {
        $requestsApprove = null;
        if (Auth::user()->hasRole('Administrator IT') || Auth::user()->hasRole('Administrator')) {
            $requestsApprove = LicenseRequest::with('items')
                ->whereHas('items', function ($query) {
                    $query->where('software_name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('softwareCategory', function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy('id', 'desc');
        } else {
            $requestsApprove = LicenseRequest::with('items')
            ->where('request_no', 'like', '%' . $this->search . '%')
                ->orWhereHas('items', function ($query) {
                    $query
                    ->where('software_name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('softwareCategory', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%');
                        })
                    ->whereHas('approvals', function ($query) {
                        $query->where('user_id', Auth::id())
                            ->where('status', 'Pending');
                    });;
                })
                ->orderBy('id', 'desc');
        }
        // return $requestsApprove->paginate(10, ['*'], 'listPage');
        $paginatedDetails = $requestsApprove->paginate(10, ['*'], 'listPage');
        $this->startnum = ($paginatedDetails->currentPage() - 1) * $paginatedDetails->perPage();
        return $paginatedDetails;
    }

    public function reject($id)
    {

        $item = LicenseRequestItem::find($id);
        if (empty($this->remarks)) {
            $this->addError('remarks', 'Keterangan wajib diisi ketika menolak.');
            return;
        }
        if ($item->approvals->where('user_id', Auth::id())->where('status', 'Pending')->first()) {
            $item->approvals()->where('user_id', Auth::id())->where('status', 'Pending')->first()->update([
                'status' => 'Rejected',
                'remarks' => $this->remarks,
                'approved_at' => now(),
            ]);
            $item->update([
                'status' => 'Rejected',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);
        } else {
            abort(403, 'Anda tidak memiliki izin untuk menolak item ini atau item sudah diproses.');
        }
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
        $item = LicenseRequestItem::find($this->itemId);
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
        } else {
            abort(403, 'Anda tidak memiliki izin untuk menyetujui item ini atau item sudah diproses.');
        }
    }
}
