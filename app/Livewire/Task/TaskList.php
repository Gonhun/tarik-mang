<?php

namespace App\Livewire\Task;

use App\Models\Task;
use App\Models\TaskProgressLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TaskList extends Component
{
    public $tasks;

    // Untuk update progress
    public $selectedTaskId;
    public $progressInput = 0;
    public $note = '';
    public $search = '';

    public function updatedSearch()
    {
        $this->loadTasks();
    }

    public function loadTasks()
    {
        $query = Task::with('logs');

        if (!empty($this->search)) {
            $search = $this->search;
            $query = $query->where(function($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $this->tasks = $query->orderBy('id', 'desc')->get();
    }

    public function mount()
    {
        $this->loadTasks();
    }

   
    public function openModal($taskId)
    {
        $this->selectedTaskId = $taskId;

        $task = Task::find($taskId);
        $this->progressInput = $task->progress;
        $this->note = '';

        $this->dispatch('open-progress-modal');
    }

    public function saveProgress()
    {
        $this->validate([
            'progressInput' => 'required|integer|min:0|max:100',
            'note' => 'required|string|min:5',
        ]);

        $task = Task::find($this->selectedTaskId);
        if ($this->progressInput <= $task->progress) {
            $this->addError('progressInput', 'Progress baru harus lebih besar dari progress sebelumnya.');
            return;
        }

        // Update task
        $task->progress = $this->progressInput;
        $task->status = $this->progressInput == 100 ? 'done' : 'progress';
        $task->save();

        // Simpan catatan progress
        TaskProgressLog::create([
            'task_id' => $task->id,
            'progress' => $this->progressInput,
            'note' => $this->note,
            'user_id' => Auth::id(),
        ]);

        // refresh data
        $this->loadTasks();

        // Tutup modal
        $this->dispatch('close-progress-modal');

        // Reset
        $this->reset(['selectedTaskId', 'progressInput', 'note']);
    }
    public function render()
    {
        return view('livewire.task.task-list');
    }
}

