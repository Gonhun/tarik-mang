<?php

namespace App\Livewire\Tickets\Ticket;

use App\Models\Jobsite;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketSubCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class MyTickets extends Component
{
    public $search = '';
    public $isModalOpen = false;
    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['refreshMyTickets' => '$refresh'];
    public $categories,$jobsites;
    public $category_id;
    public $subcategory_id;
    public $jobsite_id;
    public $title;
    public $description;
    public $attachment;
    public $subCategories = [];
    use WithPagination;

    private function generateTicketNumber()
{
    $today = Carbon::now()->format('Ymd'); // 20251203
    $prefix = "TICKET-{$today}-";          // TICKET-20251203-

    // Ambil nomor terakhir hari ini
    $last = Ticket::where('ticket_no', 'like', $prefix.'%')
        ->orderBy('ticket_no', 'desc')
        ->first();

    if (!$last) {
        $number = 1;
    } else {
        // Ambil 3 digit terakhir
        $lastNumber = (int) substr($last->ticket_no, -3);
        $number = $lastNumber + 1;
    }

    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}
    public function mount()
    {
        $this->categories = TicketCategory::where('active', true)->orderBy('name')->get();
        $this->jobsites = Jobsite::orderBy('name')->get();
    }
    public function updatedSearch()
    {
        $this->resetPage();
    }
    public function render()
    {
        $query = Ticket::where('user_id', Auth::id());
        if ($this->search) {
            $query->where('ticket_no', 'like', '%'.$this->search.'%')
            ->orWhere('title', 'like', '%'.$this->search.'%')
            ->orWhere('description', 'like', '%'.$this->search.'%');
        }
        $tickets = $query->orderBy('created_at', 'desc')->paginate(10);
        return view('livewire.tickets.ticket.my-tickets',[
            'tickets' => $tickets
        ])->layout('components.layouts.tickets.app', [
            'title' => 'My Ticket List'
        ]);
    }
    public function openModal()
    {
        $this->isModalOpen = true;
        $this->dispatch('modal-opened');
    }
    public function GetSubCategorys($value)
    {
        $this->category_id=$value;
        $this->subCategories = TicketSubCategory::where('ticket_category_id', $value)->where('active', true)
        ->orderBy('name')
        ->get();
        $this->subcategory_id = null;
        $this->dispatch('sub-category-updated', $this->subCategories);
    }
    public function store()
    {
        $validated = $this->validate();
        $validated['ticket_sub_category_id'] = $this->subcategory_id;
        $validated['user_id'] = Auth::id();
        $validated['ticket_no'] = $this->generateTicketNumber();
        $validated['created_at'] = now();
        $validated['updated_at'] = now();
        DB::beginTransaction();
        try {
            Ticket::create($validated);
            DB::commit();
            session()->flash('success', 'Ticket berhasil ditambahkan.');
            $this->resetInputFields();
            $this->closeModal();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Ticket gagal ditambahkan.' . $e->getMessage());
        }
    }
    protected function rules()
    {
        return [
            'jobsite_id' => 'required|exists:jobsites,id',
            'category_id' => 'required|exists:ticket_categories,id',
            'subcategory_id' => 'required|exists:ticket_sub_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,png,jpg|max:5120',
        ];
    }
    protected $messages = [
        'jobsite_id.required' => 'Jobsite is required',
        'jobsite_id.exists' => 'Jobsite is not valid',
        'category_id.required' => 'Category is required',
        'category_id.exists' => 'Category is not valid',
        'subcategory_id.required' => 'Subcategory is required',
        'subcategory_id.exists' => 'Subcategory is not valid',
        'title.required' => 'Title is required',
        'description.required' => 'Description is required',
        'attachment.required' => 'Attachment is required',
        'attachment.file' => 'Attachment must be a file',
        'attachment.mimes' => 'Attachment must be a PDF, DOC, DOCX, PNG, or JPG',
    ];
    public function closeModal()
    {
        $this->isModalOpen = false;
        // trigger event supaya JS membersihkan backdrop dll
        $this->dispatch('modal-closed');
    }
    private function resetInputFields()
    {
        $this->jobsite_id = null;
        $this->category_id = null;
        $this->subcategory_id = null;
        $this->title = null;
        $this->description = null;
        $this->attachment = null;
    }
}
