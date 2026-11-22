<?php

namespace App\Livewire\Licenses\Manufacture;

use App\Models\SoftwareManufacturer;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class Main extends Component
{
    use WithPagination;
    public $isModalOpen = false;
    public bool $checkChecked=true;
    public $name,$searche,$categoryId;
    public function render()
    {
        $query = SoftwareManufacturer::query();

        if (!empty($this->searche)) {
            $query->where('name', 'like', '%'.$this->searche.'%');
        }

        $softwareCollection = $query->orderBy('id', 'DESC')->paginate(10);
        return view('livewire.licenses.manufacture.main',[
            'softwareCollection' => $softwareCollection
        ]);
    }
    public function updatedSearche()
    {
        // agar pagination reset dan proses selesai normal
        $this->resetPage();
    }
    public function updatedName()
    {
        if(strlen($this->name)>5)
        {
            $this->name = Str::ucfirst($this->name);
        }
       
    }
    public function create()
    {
        $this->resetInputFields();
        $this->isModalOpen = true;

    }
    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }
    private function resetInputFields()
    {
       
        $this->name='';
        $this->categoryId = null;
        $this->resetValidation();
    }
    protected function rules()
    {
        return [
            'name' => 'required|unique:software_category|string|max:255',
        ];
    }
    public function store()
    {
        $this->name = Str::title($this->name);
        $validated = $this->validate();
        $SoftwareCategory = SoftwareManufacturer::updateOrCreate(
            ['id' => $this->categoryId],
            [
                'name' => $this->name,
                'enable' => $this->checkChecked
            ]
        );
        session()->flash('success', 'Data Category berhasil ditambahkan.');
        $this->resetInputFields();
        $this->isModalOpen = false;
        $this->refresh;
    }
    public function edit($categoryId)
    {
        $this->categoryId = $categoryId;
        $category = SoftwareManufacturer::findOrFail($categoryId);
        $this->name = $category->name;
        $this->checkChecked = $category->enable;
        $this->isModalOpen = true;
    }
}
