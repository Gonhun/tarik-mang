<?php

namespace App\Livewire;

use Livewire\Component;

class GpsMapViewer extends Component
{
    public $id = 2809;
    public $mapid = 'D340D7A2-B000-4D8E-B565-5A444F6BE006';
    public function render()
    {
        return view('livewire.gps-map-viewer');
    }
}
