<?php

namespace App\Livewire\Licenses;

use App\Models\SoftwareCategory;
use App\Models\SoftwareManufacturer;
use App\Models\Softwares;
use Livewire\Component;
use App\Models\SoftwareLicense;
use App\Models\SoftwareLicenses;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class Main extends Component
{
    public $software_id, $name, $category_id, $manufacture_id, $order_number, $purchase_cost, $purchase_date, $expiration_date, $po_number, $note;
    public $selectedManufacture, $selectedCategory;
    public $seats = 0;
    public $license_keys = [];
    public $isModalOpen = false;

    public $licenseCategory = [];
    public $licenseManufactur = [];

    public $sortField = 'name';
    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['refreshParentComponent' => '$refresh'];
    public function mount()
    {
        $this->licenseCategory = SoftwareCategory::where('enable', 'true')->orderBy('name')->get(['id', 'name']);
        $this->licenseManufactur = SoftwareManufacturer::where('enable', 'true')->orderBy('name')->get(['id', 'name']);
        $this->purchase_date = now();
    }
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    public function changeCategoryId()
    {
        // dd($this->category_id);

    }
    public function render()
    {
        // $query = SoftwareLicenses::with(['softwareCategory','softwareManufacturer','createdBy'])
        //     ->withCount([
        //         'softwareTransactions as active_checkout_count' => function ($q) {
        //             $q->whereNull('returned_at'); // atau return (silakan ganti)
        //         }
        //     ]);
        $query = Softwares::with(['category', 'manufacturer'])
            ->withCount([
                'licenses as total_licenses_count',
                'licenses as active_checkout_count' => function ($q) {
                    $q->whereHas('softwareTransactions', function ($qt) {
                        $qt->whereNull('returned_at');
                    });
                },
                'licenses as expired_licenses_count' => function ($q) {
                    $q->where('expiration_date', '<', now());
                },
                'licenses as free_licenses_count' => function ($q) {
                    $q->whereHas('softwareTransactions', function ($qt) {
                        $qt->whereNull('assigned_at');
                    });
                },
                'licenses as usage_status_count' => function ($q) {
                    $q->selectRaw("CASE 
                        WHEN expiration_date < NOW() THEN 'expired' 
                        WHEN id IN (SELECT software_license_id FROM software_transactions WHERE returned_at IS NULL AND assigned_at IS NOT NULL) THEN 'active' 
                        ELSE 'free' 
                    END as usage_status");
                }
            ]);
        if (!empty($this->selectedManufacture)) {
            $query->where('manufacture_id', $this->selectedManufacture);
        }
        if (!empty($this->selectedCategory)) {
            $query->where('category_id', $this->selectedCategory);
        }
        $softwareCollection = $query->orderBy($this->sortField, $this->sortDirection)->paginate(10);
        $totalLicenses = $softwareCollection->sum('total_licenses_count');
        $usedSeat = $softwareCollection->sum('active_checkout_count');
        return view('livewire.licenses.main', [
            'softwareCollection' => $softwareCollection,
            'totalLicenses' => $totalLicenses,
            'usedSeat' => $usedSeat
        ]);
    }
    public function create($id = null)
    {
        $this->resetInputFields();
        $this->isModalOpen = true;

        $datas = Softwares::find($id);
        if ($datas) {
            $this->software_id = $datas->id;
            $this->name = $datas->name;
            $this->category_id = $datas->category_id;
            $this->manufacture_id = $datas->manufacture_id;
        }
    }

    public function updatedSeats($value)
    {
        $count = (int) $value;

        // Jika user menambah angka seat, tambahkan elemen kosong di array
        // Jika dikurangi, potong array agar sinkron
        if ($count > count($this->license_keys)) {
            for ($i = count($this->license_keys); $i < $count; $i++) {
                $this->license_keys[$i] = '';
            }
        } else {
            $this->license_keys = array_slice($this->license_keys, 0, $count);
        }
    }

    // Role: Dapatkan rules validasi form License
    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:software_category,id',
            'manufacture_id' => 'nullable|exists:software_manufacturer,id',
            'license_key' => 'nullable|string|max:255',
            'order_number' => 'nullable|string|max:100',
            'purchase_cost' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'expiration_date' => 'nullable|date|after_or_equal:purchase_date',
            'po_number' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:4000',
            'seats' => 'nullable|numeric|min:0',
        ];
    }

    // Role: Dispatch/emit pesan toast
    public function message($type, $msg)
    {
        $this->dispatch('show-toast', [
            'type' => $type,
            'message' => $msg
        ]);
    }

    // Role: Simpan data License baru
    public function store()
    {
        $validated = $this->validate();

        $license = SoftwareLicenses::create([
            'name' => $this->name,
            'category_id' => $this->category_id,
            'manufacture_id' => $this->manufacture_id,
            'product_key' => $this->license_key,
            'order_number' => $this->order_number,
            'purchase_cost' => $this->purchase_cost,
            'purchase_date' => $this->purchase_date,
            'expiration_date' => $this->expiration_date,
            'po_number' => $this->po_number,
            'note' => $this->note,
            'seats' => $this->seats,
            'create_by' => Auth::id()
        ]);

        $this->message('success', 'Data License berhasil ditambahkan.');
        $this->resetInputFields();
        $this->isModalOpen = false;
        $this->dispatch('refreshLicensesTable');
    }

    // Role: Edit/memuat data existing ke form modal
    public function edit($licenseId)
    {
        $license = SoftwareLicenses::findOrFail($licenseId);

        $this->name = $license->name;
        $this->category_id = $license->category_id;
        $this->manufacture_id = $license->manufacture_id;
        $this->license_key = $license->license_key;
        $this->order_number = $license->order_number;
        $this->purchase_cost = $license->purchase_cost;
        $this->purchase_date = $license->purchase_date;
        $this->expiration_date = $license->expiration_date;
        $this->po_number = $license->po_number;
        $this->note = $license->note;
        $this->seats = $license->seats;

        $this->isModalOpen = true;
    }
    public function CheckoutSoftware($softwareId)
    {
        $this->dispatch('openCheckoutModal', $softwareId);
        $this->dispatch('refreshParentComponent');
    }
    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }
    private function resetInputFields()
    {
        $this->purchase_cost = 0;
        $this->name = '';
        $this->license_key = '';
        $this->order_number = null;
        $this->category_id = null;
        $this->manufacture_id = null;
        $this->purchase_date = null;
        $this->expiration_date = null;
        $this->po_number = null;
        $this->note = null;
        $this->seats = 0;
        $this->purchase_date = now();
        $this->resetValidation();
    }
}
