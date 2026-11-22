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

class MyList extends Component
{
    use WithPagination;

    public $openModal = false;
    public $myDetailModal = false;
    public $departements = [], $jobsites = [];
    public $dept_filter, $site_filter, $year_filter, $status_filter = 'Pending', $search;
    public $isAdmin;
    public $remarks = '', $action = null, $itemId = null;
    public $item_req, $req;
    protected $paginationTheme = 'bootstrap';

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
        return view('livewire.licenses-requests.my-list');
    }

    public function showMyHistory($id)
    {
        $this->openModal = true;
        $this->item_req = LicenseRequestItem::find($id);
    }

    public function openMyDetailModal($id){
        $this->myDetailModal = true;
        $this->req = LicenseRequest::with('items')->find($id);
    }
    
    public function closeMyDetailModal()
    {
        $this->myDetailModal = false;
        $this->req = null;
    }
    
    public function closeModal()
    {
        $this->openModal = false;
        $this->item_req = null;
    }

    public function dataRequestess()
    {

        $requests = LicenseRequest::with(['items', 'attachments'])
            ->where('user_id', Auth::id())
            ->orderBy('id', 'desc');
        return $requests->paginate(10, ['*'], 'myPage');
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

}
