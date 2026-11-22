<style>
    /* body {
        background-color: #f8f9fa;
        font-family: 'Inter', sans-serif;
    } */
    .chart-container {
        background-color: #ffffff;
        border-radius: 1rem;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.08);
    }
    .apexcharts-tooltip {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #111827;
    }
</style>
<div>
    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="fw-bold">Struktur Organisasi Perusahaan</h1>
            <p class="text-muted">Data dinamis dari database menggunakan Livewire</p>
        </div>
        
        {{-- Kita menggunakan wire:ignore pada container chart agar Livewire tidak
             mengganggu chart yang sudah dirender oleh JavaScript. --}}
        <div wire:ignore class="chart-container">
            <div id="liveOrgChart"></div>
        </div>
    </div>
</div>


