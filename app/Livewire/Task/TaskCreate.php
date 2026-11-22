<?php

namespace App\Livewire\Task;

use Livewire\Component;
use App\Models\Task;
use App\Models\TaskProgressLog;
use Illuminate\Support\Facades\Auth;

class TaskCreate extends Component
{
    public $title, $description, $start_date, $due_date, $assigned_to;
    public $users = [];

    public function mount()
    {
        $this->users = \App\Models\User::select('id','name')->where('department_id', config('app.it_department_id'))->where('is_active',1)->orderBy('name')->get();
        $this->assigned_to =Auth::id();
    }
    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'required|string|max:255',
        'start_date' => 'required|date',
        'due_date' => 'required|date|after_or_equal:start_date',
        'assigned_to' => 'required|exists:users,id',
    ];
    
    public function save()
    {
        $this->validate();

        // Insert task
        $task = Task::create([
            'title'         => $this->title,
            'description'   => $this->description,
            'start_date'    => now(),
            'due_date'      => $this->due_date,
            'user_id'   => $this->assigned_to,
            'status'        => 'pending',
        ]);

        // Insert log awal progress
        TaskProgressLog::create([
            'task_id' => $task->id,
            'progress' => 0,
            'note' => 'Task Created',
            'user_id' => Auth::id(),
        ]);

        session()->flash('success', 'Task berhasil dibuat!');
        $this->reset();  
    }
    public function render()
    {
        if (empty($this->start_date)) {
            $this->start_date = now()->toDateString();
        }
        return view('livewire.task.task-create');
    }
}
