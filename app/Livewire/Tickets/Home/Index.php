<?php

namespace App\Livewire\Tickets\Home;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.tickets.home.index')->layout('components.layouts.tickets.app', [
            'title' => 'Ticket List'
        ]);
    }
}
