<?php

namespace App\Livewire\StockOpname;

use App\Models\Jobsite;
use App\Models\StockOpname;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar sesi stock opname & membuat sesi baru.
 */
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $isModalOpen = false;
    public $jobsite_id = '';
    public $title = '';

    public function create()
    {
        $this->resetValidation();
        $this->jobsite_id = (string) (Auth::user()->jobsite_id ?? '');
        $this->title = 'Stock opname ' . now()->locale('id')->isoFormat('MMMM YYYY');
        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate([
            'jobsite_id' => 'required|exists:jobsites,id',
            'title' => 'required|string|max:150',
        ], ['jobsite_id.required' => 'Pilih site yang akan diperiksa.']);

        // Satu sesi terbuka per site agar hasil scan tidak terpecah.
        if ($open = StockOpname::where('jobsite_id', $this->jobsite_id)->where('status', 'open')->first()) {
            $this->addError('jobsite_id', "Site ini masih punya sesi terbuka ({$open->code}). Lanjutkan atau tutup sesi itu dulu.");
            return;
        }

        $opname = DB::transaction(function () {
            $site = Jobsite::findOrFail($this->jobsite_id);
            $prefix = 'SO/' . ($site->code ?? $site->id) . '/' . now()->format('ym') . '/';
            $last = StockOpname::where('code', 'like', $prefix . '%')->orderByDesc('code')->value('code');
            $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            $opname = StockOpname::create([
                'code' => $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT),
                'jobsite_id' => $site->id,
                'title' => trim($this->title),
                'status' => 'open',
                'started_by' => Auth::id(),
                'started_at' => now(),
            ]);
            $opname->update(['expected_count' => $opname->expectedAssetsQuery()->count()]);

            return $opname;
        });

        return $this->redirectRoute('asset.opname.show', ['opname' => $opname->id]);
    }

    public function render()
    {
        $sessions = StockOpname::with(['jobsite:id,code,name', 'starter:id,name'])
            ->withCount([
                'items as found_count' => fn ($q) => $q->where('result', 'found'),
                'items as other_count' => fn ($q) => $q->where('result', 'other_site'),
                'items as unknown_count' => fn ($q) => $q->where('result', 'not_registered'),
            ])
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.stock-opname.index', [
            'sessions' => $sessions,
            'jobsites' => $this->isModalOpen ? Jobsite::orderBy('code')->get(['id', 'code', 'name']) : collect(),
        ]);
    }
}
