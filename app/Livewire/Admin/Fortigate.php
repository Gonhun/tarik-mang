<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Fortigate extends Component
{
    
    public function render()
    {
        return view('livewire.admin.fortigate');
    }
}
