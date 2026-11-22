<?php

namespace App\Livewire\Tickets;

use Livewire\Component;

class UnderDeveloper extends Component
{
    public function render()
    {
        return view('livewire.tickets.under-developer')->layout('components.layouts.tickets.app', [
            'title' => 'Under Developer'
        ]);
    }
}
