<?php

namespace App\Livewire\Tickets\Subcategorys;

use App\Models\TicketCategory;
use App\Models\TicketSubCategory;
use Livewire\Component;

class Index extends Component
{
    public $ticket_category_id;
    public $name;
    public $categories;

    public function mount()
    {
        $this->categories = TicketCategory::where('active', true)->orderBy('name')->get();
    }
    public function render()
    {
        return view('livewire.tickets.subcategorys.index',[
            'subcategories' => TicketSubCategory::orderBy('created_at', 'desc')->paginate(10),
            // 'categories' => TicketCategory::where('active', true)->orderBy('name')->get(),
        ])->layout('components.layouts.tickets.app', [
            'title' => 'Ticket SubCategorys',
        ]);
    }
    public function saveSubCategory()
    {
        $validated = $this->validate();
        $validated['active'] = true;
        TicketSubCategory::create($validated);
        session()->flash('success', 'Ticket SubCategory berhasil ditambahkan.');
        $this->resetInputFields();
    }
    private function resetInputFields()
    {
        $this->ticket_category_id = '';
        $this->name = '';
       
    }
    protected function rules()
    {
        return [
            'ticket_category_id' => 'required|exists:ticket_categories,id',
            'name' => 'required|string|max:255',
           
        ];
    }
    protected $messages = [
        'ticket_category_id.required' => 'Kategori wajib diisi.',
        'ticket_category_id.exists' => 'Kategori tidak ditemukan.',
        'name.required' => 'Nama sub kategori wajib diisi.',
        'name.string' => 'Nama sub kategori harus berupa string.',
        'name.max' => 'Nama sub kategori maksimal 255 karakter.',
        
    ];
    public function changeStatusSubCategory($id)
    {
        $subcategory = TicketSubCategory::find($id);
        $subcategory->active = !$subcategory->active;
        $subcategory->save();
        session()->flash('success', 'Status Ticket Category berhasil diubah.');
    }
}
