<?php

namespace App\Livewire\Tickets\Categorys;

use App\Models\TicketCategory;
use Livewire\WithPagination;
use Livewire\Component;

class Index extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    public $name;
   

    public function render()
    {
       
       
        return view('livewire.tickets.categorys.index', [
            'categories' => TicketCategory::orderBy('created_at', 'desc')->paginate(10),
        ])->layout('components.layouts.tickets.app', [
            'title' => 'Ticket Categorys',
        ]);
    }
    public function saveCategory()
    {
        $validated = $this->validate();
        $validated['active'] = true;
        TicketCategory::create($validated);
        session()->flash('success', 'Ticket Category berhasil ditambahkan.');
        $this->resetInputFields();
    }
    public function changeStatusCategory($id)
    {
        $category = TicketCategory::find($id);
        $category->active = !$category->active;
        $category->save();
        session()->flash('success', 'Status Ticket Category berhasil diubah.');
    }
    private function resetInputFields()
    {
        $this->name = '';
    }
    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
    protected $messages = [
        'name.required' => 'Nama kategori wajib diisi.',
        'name.string' => 'Nama kategori harus berupa string.',
        'name.max' => 'Nama kategori maksimal 255 karakter.',
    ];
}
