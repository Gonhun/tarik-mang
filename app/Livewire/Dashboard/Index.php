<?php

namespace App\Livewire\Dashboard;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Halaman /dashboard: tim IT (dan admin / pemegang izin "Show Dashboard") melihat dashboard IT,
 * user lain melihat dashboard pribadi. Tim IT bisa beralih ke tampilan pribadi.
 */
class Index extends Component
{
    #[Url(as: 'view')]
    public string $view = '';

    public function canSeeItDashboard(): bool
    {
        $user = Auth::user();

        return $user->isItStaff()
            || $user->hasRole(['Administrator IT', 'Administrator'])
            || $user->can('Show Dashboard');
    }

    public function render()
    {
        $isIt = $this->canSeeItDashboard();
        $showIt = $isIt && $this->view !== 'personal';

        return view('livewire.dashboard.index', [
            'isIt' => $isIt,
            'showIt' => $showIt,
        ])->title('Dashboard');
    }
}
