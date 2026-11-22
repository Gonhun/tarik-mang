<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\User;
use App\Models\Department;
use App\Models\Jobsite;
use App\Models\Asset; // Pastikan model Asset ada
use Illuminate\Support\Facades\Auth;

class Karyawan extends Component
{
    public $search = '';
    public $perPage = 10;
    public $department_id = '';
    public $jobsite_id = '';
    public $idne,$pesan,$totalPengajuan;
    public $tab = 0;
    public $totalAssetByType = [];
    public $usersIT = [];
    public $labels = [];
    public $series = [];
    public $totalAsset=0;

    public function mount()
    {
        
        $this->getITSite();
    }
    public function kirimPesan()
    {
        
        $this->validate([
            'idne' => 'required',
            'pesan' => 'required|string',
        ], [
            'idne.required' => 'Nomor WhatsApp wajib diisi.',
            'pesan.required' => 'Pesan wajib diisi.',
        ]);
        $user = User::find($this->idne);
        if ($user) {
            $name = $user->name;
            $noWa = $user->whatsapp;
            $pesan = "Halo ".$name."🙏\n";
            $pesan .= "*". Auth::user()->name ."*\n";
            $pesan .= "Contac Person +". Auth::user()->whatsapp."\n" ;
            $pesan .= "Memerluhakn Support untuk\n";
            $pesan .= $this->pesan;
            sendMessageWithWhatsApp($noWa,$pesan);
            $this->pesan='';
           
            session()->flash('wa_sukses', 'Pesan Berhasil Dikirim!');
        } else {
            $noWa = null;
            session()->flash('wa_error', 'User IT tidak ditemukan!');
        }
       
        if(!empty($this->wa) || !empty($this->pesan))
        {
            
        }
        
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDepartmentId()
    {
        $this->resetPage();
    }

    public function updatingJobsiteId()
    {
        $this->resetPage();
    }
    public function getITSite()
    {
        $this->usersIT = User::whereHas('department', function ($query) {
            $query->where('name', 'IT');
        })->where('jobsite_id', Auth::user()->jobsite_id)
          ->where('id', '!=', Auth::id())
          ->get(['id','name']);
    }
    public function calculateTotalAssetByType()
    {
        // Asumsi: kolom 'type' pada tabel assets
        $this->totalAssetByType = Asset::selectRaw('asset_type_id, COUNT(*) as total')
            ->groupBy('asset_type_id')
            ->with('assetType') // kalau mau akses nama tipe
            ->get();
           
            foreach ($this->totalAssetByType as $item) {
                $this->labels[] = $item->assetType->name ?? 'Unknown';
                $this->series[] = (int) $item->total;
            }
            
            // Kirim data ke JS
            $this->dispatch('updateOrderChart', [
                'labels' => $this->labels,
                'series' => $this->series,
            ]);
        $this->totalAsset = Asset::count('id');
    }

    public function render()
    {
        // Jika ingin update setiap render, uncomment:
        // $this->calculateTotalAssetByType();
        $this->calculateTotalAssetByType();
        // Total pengajuan (jumlah seluruh asset request)
        $this->totalPengajuan = \App\Models\AssetRequest::count();
        return view('livewire.dashboard.karyawan', [
            'totalAssetByType' => $this->totalAssetByType,
        ]);
    }
}
