<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AssetsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $search;
        protected $jobsiteId;
        protected $status;
        // Tambahkan properti filter lain jika perlu

        public function __construct($search, $jobsiteId, $status)
        {
            $this->search = $search;
            $this->jobsiteId = $jobsiteId;
            $this->status = $status;
        }

        /**
         * Mendefinisikan query untuk mengambil data aset.
         * Ini akan menggunakan filter yang sama dengan yang ada di halaman manajemen aset.
         */
        public function query()
        {
            $query = Asset::query()->with([
                'assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name',
                'jobsite:id,name', 'holder:id,nik,name', 'entity:id,name'
            ]);

            // Terapkan filter Jobsite
            if (!empty($this->jobsiteId)) {
                $query->where('jobsite_id', $this->jobsiteId);
            }

            // Terapkan filter Status
            if (!empty($this->status)) {
                $query->where('status', $this->status);
            }

            // Terapkan filter Pencarian
            if (!empty($this->search)) {
                $searchTerm = '%' . $this->search . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('asset_number', 'like', $searchTerm)
                      ->orWhere('serial_number', 'like', 'searchTerm')
                      ->orWhereHas('assetType', fn($sq) => $sq->where('name', 'like', $searchTerm))
                      ->orWhereHas('holder', fn($sq) => $sq->where('name', 'like', $searchTerm));
                });
            }

            return $query->orderBy('asset_number', 'asc');
        }

        /**
         * Mendefinisikan header untuk kolom di file Excel.
         */
        public function headings(): array
        {
            return [
                'Nomor Aset',
                'Nomor Aset Lama',
                'Serial Number',
                'Status',
                'Tipe Aset',
                'Merek',
                'Seri/Model',
                'Nik Pemegang',
                'Pemegang Aset',
                'Jobsite',
                'Lokasi',
                'Entitas',
                'Tanggal Dibuat',
            ];
        }

        /**
         * Memetakan data dari setiap model Asset ke baris di Excel.
         * Ini memungkinkan kita untuk menampilkan nama dari relasi, bukan hanya ID.
         * @param Asset $asset
         */
        public function map($asset): array
        {
            return [
                $asset->asset_number,
                $asset->old_asset_number,
                $asset->serial_number,
                $asset->status,
                $asset->assetType->name ?? 'N/A',
                $asset->assetBrand->name ?? 'N/A',
                $asset->assetSeries->name ?? 'N/A',
                $asset->holder->nik ?? '-',
                $asset->holder->name ?? '-',
                $asset->jobsite->name ?? 'N/A',
                $asset->current_location_detail ?? 'N/A',
                $asset->entity->name ?? 'N/A',
                // $asset->created_at->format('d-m-Y H:i'),
                $asset->created_at ? $asset->created_at->format('d-m-Y H:i') : '-'
            ];
        }
}
