<?php

namespace App\Livewire\AssetRequests;

use Livewire\Component;
use App\Models\AssetRequest;
use OwenIt\Auditing\Models\Audit; // Import model Audit
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    /**
     * Properti untuk mengontrol tab yang aktif untuk user IT.
     * 'requests', 'logs', atau 'history'.
     */
    public $activeTab = 'requests';

    public $search = '';
    protected $paginationTheme = 'bootstrap';

    // Listener untuk me-refresh dashboard dari komponen lain
    protected $listeners = ['refreshDashboard' => '$refresh'];

    /**
     * Mengubah tab yang aktif.
     */
    public function changeTab($tabName)
    {
        $this->activeTab = $tabName;
        $this->resetPage(); // Reset pagination saat berganti tab
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $isItUser = $user->hasAnyRole(['Administrator IT','Administrator','IT']); // Asumsi menggunakan Spatie/Permission, now using array

        $requests = null;
        $auditLogs = null;
        $historyRequests = null; // Properti baru untuk history

        $requestsAwaitingMyActionQuery = AssetRequest::query();
        if ($user) {
            // PERBAIKAN: Logika disederhanakan untuk hanya menggunakan tabel 'approvals'
            $pendingStatuses = ['Pending IT Item Validation', 'Pending Approval'];
            
            $requestsAwaitingMyActionQuery
                ->whereIn('status', $pendingStatuses)
                ->whereHas('approvals', function ($approvalQuery) use ($user) {
                    $approvalQuery->where('status', 'Pending')
                                  ->where('approver_user_id', $user->id)
                                  ->where('step_order', function ($stepSubQuery) {
                                        $stepSubQuery->selectRaw('MIN(step_order)')
                                                     ->from('asset_request_approvals as sub_approvals')
                                                     ->whereColumn('sub_approvals.asset_request_id', 'asset_request_approvals.asset_request_id')
                                                     ->where('sub_approvals.status', 'Pending');
                                  });
                 });
        } else {
            $requestsAwaitingMyActionQuery->whereRaw('1 = 0'); // Jika tidak ada user, jangan tampilkan apa-apa
        }
        
        $requestsAwaitingMyAction = $requestsAwaitingMyActionQuery
                                      ->with('requester:id,name')
                                      ->orderBy('request_date', 'asc')
                                      ->get();
        // Status-status yang dianggap masih aktif/pending
        $activeStatuses = [
            'Draft', 'Pending IT Item Validation', 'Pending Approval',
            'Pending PR & Accounting Number', 'Pending PO Number', 'Processing'
        ];
        

        if ($isItUser) {
            $requestsQuery = AssetRequest::query()->whereIn('status', $activeStatuses)->with(['requester', 'jobsite', 'approvals.approver']);
            if (!empty($this->search) && $this->activeTab === 'requests') {
                $requestsQuery->where('request_number', 'like', '%' . $this->search . '%')
                              ->orWhereHas('requester', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));
            }
            $requests = $requestsQuery->orderBy('request_date', 'desc')->paginate(10, ['*'], 'requestsPage');

            // Data untuk tab "History Pengajuan"
            $historyQuery = AssetRequest::query()->whereNotIn('status', $activeStatuses)->with(['requester', 'jobsite']);
            if (!empty($this->search) && $this->activeTab === 'history') {
                $historyQuery->where('request_number', 'like', '%' . $this->search . '%')
                              ->orWhereHas('requester', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));
            }
            $historyRequests = $historyQuery->orderBy('updated_at', 'desc')->paginate(10, ['*'], 'historyPage');

            // Data untuk tab "Log Audit"
            $auditLogsQuery = Audit::query()
                ->whereIn('auditable_type', [AssetRequest::class, \App\Models\AssetRequestItem::class])
                ->with('user:id,name');
            if (!empty($this->search) && $this->activeTab === 'logs') {
                $auditLogsQuery->where(function($q) {
                    $q->where('event', 'like', '%' . $this->search . '%')
                      ->orWhereHas('user', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%'));
                });
            }
            $auditLogs = $auditLogsQuery->orderBy('created_at', 'desc')->paginate(10, ['*'], 'logsPage');

        } else {
            $requestsQuery = AssetRequest::where('requester_user_id', $user->id)
                ->with(['requester', 'jobsite', 'approvals.approver']);
            if (!empty($this->search)) {
                $requestsQuery->where('request_number', 'like', '%' . $this->search . '%');
            }
            $requests = $requestsQuery->orderBy('request_date', 'desc')->paginate(10, ['*'], 'requestsPage');
        }

        return view('livewire.asset-requests.dashboard', [
            'isItUser' => $isItUser,
            'requests' => $requests,
            'auditLogs' => $auditLogs,
            'historyRequests' => $historyRequests,
            'requestsAwaitingMyAction' => $requestsAwaitingMyAction,
        ]);
   
    }
}