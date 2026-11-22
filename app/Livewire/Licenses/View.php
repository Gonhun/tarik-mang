<?php

namespace App\Livewire\Licenses;

use App\Models\SoftwareLicenses;
use App\Models\SoftwareTransaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class View extends Component
{
    public $softwareCollection;
    public function mount($id) // auto ambil param dari route
    {
        if($id)
        {
            $license=SoftwareLicenses::where('software_id', $id)->first();
            if($license)
            {
                $query = SoftwareLicenses::with(['softwareCategory','softwareManufacturer','createdBy'])
                ->withCount([
                    'softwareTransactions as active_checkout_count' => function ($q) {
                        $q->whereNull('returned_at'); // atau return (silakan ganti)
                    }
                ]);
            $query->where('id', $id);
            $this->softwareCollection = $query->first();
            }else{
                abort(404, 'License not found');
            }
        }
    }
    public function Checkout($id)
    {
        if($id)
        {
            $trans = SoftwareTransaction::find($id);
            if($trans)
            {
                $trans->update([
                    'returned_at' => now(),
                    'transaction_type' => 'return',
                    'created_by' => Auth::id()
                ]);
                $this->dispatch('swal:success', [
                    'title' => 'Berhasil!',
                    'text' => 'License berhasil di checkout!',
                ]);
            }else{
                $this->dispatch('swal:error', [
                    'title' => 'Error!',
                    'text' => 'Transaksi tidak ditemukan!',
                ]);
                return;
            }
        }else{
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => 'Transaksi tidak ditemukan!',
            ]);
            return;
        }
        
    }
    public function render()
    {
        $query = SoftwareTransaction::with(['software','user','asset','creator']);
        
        $softTransCollection = $query->orderBy('id', 'DESC')->paginate(10);
        return view('livewire.licenses.view',[
            'softwareCollection' => $this->softwareCollection,
            'softTransCollection' => $softTransCollection
        ]);
    }
}
