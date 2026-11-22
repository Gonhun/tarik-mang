<?php

namespace App\Livewire\Tickets\Ticket;

use App\Models\Ticket;
use App\Models\TicketSubCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Detail extends Component
{
    public const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'pending' => 'Pending',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    #[Locked]
    public $ticketId;

    public $ticket_no;
    public $assignedId, $setStatus;
    public $rating;
    public $rating_note;

    // Field form (tidak lagi bind langsung ke model Ticket)
    public $categoryId;
    public $subCategoryId;
    public $priority;
    public $resolutionNote;

    public function mount($ticket)
    {
        $model = Ticket::with('subCategory')->where('ticket_no', $ticket)->firstOrFail();
        abort_unless(Gate::allows('view-ticket', $model), 403);

        $this->ticketId = $model->id;
        $this->ticket_no = $model->ticket_no;
        $this->fillForm($model);
    }

    protected function fillForm(Ticket $ticket): void
    {
        $this->setStatus = $ticket->status;
        $this->subCategoryId = $ticket->ticket_sub_category_id;
        $this->categoryId = $ticket->subCategory?->ticket_category_id;
        $this->priority = $ticket->priority;
        $this->resolutionNote = $ticket->resolution_note;
    }

    protected function ticket(): Ticket
    {
        return Ticket::with(['subCategory', 'assignedTo', 'user'])->findOrFail($this->ticketId);
    }

    protected function canManage(Ticket $ticket): bool
    {
        return Gate::allows('manage-ticket', $ticket);
    }

    protected function isOwner(Ticket $ticket): bool
    {
        return (int) $ticket->user_id === Auth::id();
    }

    public function updatedCategoryId()
    {
        $this->subCategoryId = null;
    }

    public function updateSubCategory()
    {
        $ticket = $this->ticket();
        abort_unless($this->canManage($ticket) && !in_array($ticket->status, ['closed', 'resolved'], true), 403);

        $this->validate(['subCategoryId' => 'required|exists:ticket_sub_categories,id']);
        $ticket->ticket_sub_category_id = $this->subCategoryId;
        $ticket->save();
        session()->flash('success', 'Sub kategori berhasil disimpan.');
    }

    // Kategori tidak disimpan sendiri (tabel tickets hanya punya sub kategori); form kategori hanya memfilter sub kategori.
    public function updateCategory()
    {
        $this->updateSubCategory();
    }

    public function updatePriority()
    {
        $ticket = $this->ticket();
        abort_unless($this->canManage($ticket), 403);

        $this->validate(['priority' => 'required|in:' . implode(',', array_keys(self::PRIORITIES))]);
        $ticket->priority = $this->priority;
        $ticket->save();
        session()->flash('success', 'Prioritas berhasil disimpan.');
    }

    public function setRating($value)
    {
        $ticket = $this->ticket();
        if ($ticket->status != 'closed' || !$this->isOwner($ticket)) return;

        $this->rating = max(1, min(5, (int) $value));
    }

    public function submitRating()
    {
        $ticket = $this->ticket();
        abort_unless($this->isOwner($ticket), 403);

        if ($ticket->status != 'closed') {
            session()->flash('error', 'Ticket belum closed.');
            return;
        }

        $this->validate([
            'rating' => 'required|integer|min:1|max:5',
            'rating_note' => 'nullable|string|max:1000',
        ]);

        $ticket->update([
            'rating' => $this->rating,
            'rating_note' => $this->rating_note,
        ]);

        session()->flash('success', 'Rating berhasil diberikan.');
    }

    public function assigned()
    {
        $ticket = $this->ticket();
        abort_unless($this->canManage($ticket), 403);

        $this->validate([
            'assignedId' => ['required', 'exists:users,id'],
        ]);

        $ticket->assigned_to = $this->assignedId;
        $ticket->assigned_at = now();
        $ticket->assigned_by = Auth::id();
        $ticket->save();
        $this->assignedId = null;
        session()->flash('success', 'Penanggung jawab berhasil ditugaskan.');
    }

    public function updateStatus()
    {
        $ticket = $this->ticket();

        $this->validate([
            'setStatus' => 'required|in:' . implode(',', array_keys(self::STATUSES)),
            'resolutionNote' => ($this->setStatus === 'resolved' ? 'required' : 'nullable') . '|string|max:5000',
        ]);

        if ($this->setStatus === $ticket->status) {
            return;
        }

        // Tim IT: mengubah status selama tiket belum closed (tidak boleh langsung closed).
        // Pembuat tiket: hanya boleh menutup / membuka ulang tiket yang sudah resolved.
        $itAllowed = $this->canManage($ticket) && $ticket->status !== 'closed' && $this->setStatus !== 'closed';
        $ownerAllowed = $this->isOwner($ticket) && $ticket->status === 'resolved' && in_array($this->setStatus, ['closed', 'open'], true);
        abort_unless($itAllowed || $ownerAllowed, 403, 'Aksi tidak diizinkan.');

        $ticket->status = $this->setStatus;

        if ($this->setStatus === 'resolved') {
            $ticket->resolved_by = Auth::id();
            $ticket->resolved_at = now();
        } elseif ($this->setStatus === 'closed') {
            $ticket->closed_by = Auth::id();
            $ticket->closed_at = now();
        }

        if ($this->canManage($ticket)) {
            $ticket->resolution_note = $this->resolutionNote;
        }

        $ticket->save();
        $this->fillForm($ticket);
        session()->flash('success', 'Status berhasil di update.');
    }

    public function render()
    {
        $ticket = $this->ticket();

        $users = User::with('jobsite')
            ->where('department_id', config('app.it_department_id'))
            ->orderBy('name')
            ->get();

        $subCategories = $this->categoryId
            ? TicketSubCategory::where('ticket_category_id', $this->categoryId)->orderBy('name')->get()
            : collect();

        return view('livewire.tickets.ticket.detail', [
            'ticket' => $ticket,
            'users' => $users,
            'status' => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'subCategories' => $subCategories,
            'canManage' => $this->canManage($ticket),
        ])->layout('components.layouts.tickets.app', [
            'title' => 'Detail Ticket'
        ]);
    }
}
