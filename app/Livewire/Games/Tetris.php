<?php

namespace App\Livewire\Games;

use Livewire\Component;
use App\Models\TetrisScore;
use Illuminate\Support\Facades\Auth;

class Tetris extends Component
{
    public $highScore;
    public $myLastScores;

    // Listener untuk menerima skor dari JavaScript saat game over
    protected $listeners = ['gameover' => 'saveScore'];

    public function mount()
    {
        $this->loadScores();
    }

    public function loadScores()
    {
        // Ambil high score dari semua pemain
        $this->highScore = TetrisScore::max('score') ?? 0;

        // Ambil 5 skor terakhir dari user yang sedang login
        $this->myLastScores = TetrisScore::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Menerima payload dari event 'gameover' dan menyimpan skor.
     * @param array $payload Data yang dikirim dari JavaScript, cth: ['score' => 123, 'lines' => 4, 'level' => 2]
     */
    public function saveScore(array $payload)
    {
        TetrisScore::create([
            'user_id' => Auth::id(),
            'score' => (int) $payload['score'], // Lakukan casting untuk keamanan
            'lines_cleared' => (int) $payload['lines'],
            'level_reached' => (int) $payload['level'],
        ]);
    
        $this->loadScores(); // Muat ulang data skor
        $this->dispatch('show-toast', [
            'type' => 'success', 
            'message' => "Skor Anda: {$payload['score']} berhasil disimpan!"
        ]);
    }

    public function render()
    {
        return view('livewire.games.tetris'); // Sesuaikan layout Anda
    }
}