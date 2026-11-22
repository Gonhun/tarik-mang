<?php

namespace App\Livewire\Tickets\Ticket;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Detail extends Component
{
    public $ticket_no;
    public $ticket;
    public $assignedId,$setStatus;
    public $rating;
    public $rating_note;
    public function mount($ticket)
    {
       
        $this->ticket = Ticket::where('ticket_no', $ticket)->first();
        $this->setStatus = $this->ticket->status;
    }
    public function setRating($value)
    {
        if ($this->ticket->status != 'closed') return;

        $this->rating = $value;
    }

    public function submitRating()
    {
        if ($this->ticket->status != 'closed') {
            session()->flash('error', 'Ticket belum closed.');
            return;
        }

        $this->ticket->update([
            'rating' => $this->rating,
            'rating_note' => $this->rating_note,
        ]);

        session()->flash('success', 'Rating berhasil diberikan.');
    }
    public function assigned()
    {
      
    // Update the ticket's assigned_to with the selected user ID and save
        $this->ticket->assigned_to = $this->assignedId;
        $this->ticket->assigned_at = now();
        $this->ticket->assigned_by = Auth::id();
        $this->ticket->save();
        $this->assignedId=null;
        session()->flash('success', 'Penanggung jawab berhasil ditugaskan.');
        // Optionally reset the property or refresh
        $this->ticket = Ticket::find($this->ticket->id);
    }
    public function updateStatus()
    {
        if($this->setStatus !== $this->ticket->status)
        {
            if (!in_array($this->setStatus, ['closed', 'resolved'])) {
           
                $this->ticket->status = $this->setStatus;
                $this->ticket->save();
                session()->flash('success', 'Status Updated');
             // Optionally reset the property or refresh
             }else{
                 if($this->setStatus == 'resolved')
                 {
                     $this->ticket->status = $this->setStatus;
                     $this->ticket->resolved_by = Auth::id();
                     $this->ticket->resolved_at = now();
                     $this->ticket->save();
                     
                 }elseif($this->setStatus == 'closed')
                 {
                     $this->ticket->status = $this->setStatus;
                     $this->ticket->closed_by = Auth::id();
                     $this->ticket->closed_at = now();
                     $this->ticket->save();
                 }
             }
             $this->ticket->resolution_note;
             $this->ticket->save();
             session()->flash('success', 'Status berhasil di update.');
             $this->ticket = Ticket::find($this->ticket->id);
        }
       
    }
    public function render()
    {
        $users = User::where('department_id',8)->orderBy('name')->get();
        $statuse = [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'pending' => 'Pending',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
        return view('livewire.tickets.ticket.detail', [
            'ticket' => $this->ticket,
            'users' => $users,
            'status' => $statuse
        ])->layout('components.layouts.tickets.app', [
            'title' => 'Detail Ticket'
        ]);
    }
}
