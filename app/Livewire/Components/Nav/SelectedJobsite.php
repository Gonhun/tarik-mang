<?php

namespace App\Livewire\Components\Nav;

use App\Models\Jobsite;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class SelectedJobsite extends Component
{
    public $selectedJobsites = '';


    public $jobsites= [];
    public $jobsiteID;
    protected $listeners = ['GantiJobsite'];
    public function mount()
    {
        $this->jobsites= Jobsite::orderBy('code','ASC')->get();
       
    }
  
    public function ChangeJobsites($value)
    {
        $this->dispatch('swal:GantiJobsite', [
            'id' => $value,
            'title' => 'Pemberitahuan',
            'text' => 'Jika merubah Jobsite, Anda harus memilih Atasan Langsung lagi!',
            'method' => 'GantiJobsite',
        ]);
       

    }
    public function GantiJobsite($id)
    {
        $user = Auth::user();
        $user->jobsite_id = $id;
        $user->save();
        return redirect(request()->header('Referer')); 
    }
    public function render()
    {
        return view('livewire.components.nav.selected-jobsite');
    }
}
