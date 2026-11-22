<?php

namespace App\Livewire\Master\Asset;

use Livewire\Component;
use App\Models\Asset;
use Illuminate\Support\Collection;

class AssetDetail extends Component
{
    public Asset $asset;

    /**
     * Properti untuk menampung data timeline yang sudah diproses.
     * @var Collection
     */
    public $timelineEvents;

    /**
     * Dijalankan saat komponen dimuat, menerima Asset dari route model binding.
     */
    public function mount(Asset $asset)
    {
        // Eager load semua relasi yang dibutuhkan untuk history
        $this->asset = $asset->load([
            'assetType', 'assetBrand', 'assetSeries', 'jobsite', 'holder',
            'assetRequestItem.assetRequest.requester', // Asal-usul aset
            // PERBAIKAN: Hapus orderBy() dari sini karena sudah ada di definisi relasi
            'transactions' => function ($query) { // Riwayat BAST & Mutasi
                $query->with(['fromUser:id,name', 'toUser:id,name', 'fromJobsite:id,name', 'toJobsite:id,name']);
            },
            'damageReports.reporter:id,name' // Riwayat Kerusakan
        ]);

        $this->buildTimeline();
    }

    /**
     * Membangun koleksi timeline dari berbagai sumber data (transaksi, kerusakan, dll).
     */
    protected function buildTimeline()
    {
        $events = collect();

        // 1. Event: Aset Didaftarkan
        $events->push([
            'date' => $this->asset->created_at,
            'type' => 'CREATED',
            'title' => 'Aset Didaftarkan di Sistem',
            'description' => $this->asset->assetRequestItem
                ? 'Aset dibuat berdasarkan pengajuan No. ' . ($this->asset->assetRequestItem->assetRequest->request_number ?? 'N/A')
                : 'Aset didaftarkan secara manual.',
            'icon' => 'bx bxs-plus-circle',
            'color' => 'primary'
        ]);

        // 2. Event: Riwayat Transaksi (BAST & Mutasi)
        foreach ($this->asset->transactions as $transaction) {
            $event = [
                'date' => $transaction->transaction_date,
                'type' => $transaction->transaction_type,
                'title' => 'Transaksi Aset (' . $transaction->transaction_type . ')',
                // PERBAIKAN: Tambahkan deskripsi default untuk semua jenis transaksi
                'description' => $transaction->notes ?? 'Tidak ada catatan untuk transaksi ini.',
                'icon' => 'bx bx-alarm-exclamation',
                'color' => 'info',
                'data' => $transaction
            ];
            // Judul dan deskripsi akan ditimpa (override) jika cocok dengan kondisi di bawah ini
            if ($transaction->transaction_type === 'HANDOVER') {
                $event['title'] = 'Serah Terima (BAST)';
                $event['description'] = "Aset diserahkan dari " . ($transaction->fromUser->name ?? 'Gudang') . " kepada " . ($transaction->toUser->name ?? 'N/A') . ".";
                $event['icon'] = 'bx bx-bxs-user-check';
            } elseif ($transaction->transaction_type === 'RETURN') {
                $event['title'] = 'Pengembalian Aset';
                $event['description'] = "Aset dikembalikan oleh " . ($transaction->fromUser->name ?? 'N/A') . ".";
                $event['icon'] = 'bx bx-undo';
                $event['color'] = 'secondary';
            } elseif ($transaction->transaction_type === 'MUTATION') {
                $event['title'] = 'Mutasi Aset';
                $event['description'] = "Aset dimutasi dari " . ($transaction->fromJobsite->name ?? 'N/A') . " ke " . ($transaction->toJobsite->name ?? 'N/A') . ".";
                $event['icon'] = 'bx bxs-truck';
            }
            $events->push($event);
        }

        // 3. Event: Riwayat Laporan Kerusakan
        foreach ($this->asset->damageReports as $report) {
            $events->push([
                'date' => $report->report_date,
                'type' => 'DAMAGE_REPORT',
                'title' => 'Laporan Kerusakan',
                'description' => "Kerusakan dilaporkan oleh " . ($report->reporter->name ?? 'N/A') . ": '{$report->damage_description}'",
                'icon' => 'fas fa-exclamation-triangle',
                'color' => 'warning',
                'data' => $report
            ]);
            // Tambahkan event untuk perbaikan selesai jika ada
            if ($report->repaired_date) {
                 $events->push([
                    'date' => $report->repaired_date,
                    'type' => 'REPAIR_COMPLETE',
                    'title' => 'Perbaikan Selesai',
                    'description' => "Aset selesai diperbaiki. Catatan: " . ($report->repair_notes ?? '-'),
                    'icon' => 'fas fa-wrench',
                    'color' => 'success',
                    'data' => $report
                ]);
            }
        }

        // Urutkan semua event berdasarkan tanggal, dari yang terbaru ke terlama
        $this->timelineEvents = $events->sortByDesc('date');
    }

    public function render()
    {
        return view('livewire.master.asset.asset-detail');
    }
}
