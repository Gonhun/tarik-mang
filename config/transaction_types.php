<?php

/*
|--------------------------------------------------------------------------
| Master Jenis Transaksi (untuk Aturan Validasi / workflow approval)
|--------------------------------------------------------------------------
|
| Daftar jenis transaksi yang bisa diberi aturan validasi di Admin › Workflow.
| Kode harus sama dengan yang dipakai kode aplikasi (lihat kolom "dipakai_di").
| Disimpan di file (bukan tabel): ubah di sini lalu jalankan `php artisan config:clear`.
|
| - label       : nama yang tampil ke user
| - deskripsi   : penjelasan singkat kapan workflow ini dipakai
| - dipakai_di  : menu / aksi yang memakai workflow ini
| - fallback    : kode lain yang dipakai bila jenis ini belum punya aturan (opsional)
| - aktif       : false = tidak bisa dipilih untuk aturan baru (aturan lama tetap ada)
| - wajib       : true = harus punya aturan, ditampilkan di panel kesiapan
|
*/

return [
    'REQUEST_ASSET' => [
        'label' => 'Pengajuan aset',
        'deskripsi' => 'Persetujuan permintaan aset baru oleh karyawan (setelah validasi item oleh IT site).',
        'dipakai_di' => ['Pengajuan aset › Ajukan aset'],
        'fallback' => null,
        'aktif' => true,
        'wajib' => true,
    ],
    'MUTATION' => [
        'label' => 'Mutasi aset',
        'deskripsi' => 'Pemindahan aset antar site, termasuk mutasi otomatis dari pengajuan aset yang dipenuhi stok site lain.',
        'dipakai_di' => ['Aset › Mutasi', 'Pengajuan aset (stok site lain)'],
        'fallback' => null,
        'aktif' => true,
        'wajib' => true,
    ],
    'DISPOSED' => [
        'label' => 'Disposal aset',
        'deskripsi' => 'Penghapusbukuan aset rusak berat / tidak terpakai.',
        'dipakai_di' => ['Aset › Ajukan disposal', 'Inspeksi pengembalian (rusak berat)', 'Laporan kerusakan (rusak berat)'],
        'fallback' => null,
        'aktif' => true,
        'wajib' => true,
    ],
    'LOST' => [
        'label' => 'Kehilangan aset',
        'deskripsi' => 'Laporan aset hilang. Bila belum punya aturan sendiri, memakai workflow disposal.',
        'dipakai_di' => ['Aset › Laporkan hilang', 'Aset saya › Laporkan hilang'],
        'fallback' => 'DISPOSED',
        'aktif' => true,
        'wajib' => false,
    ],
    'REQUEST_LICENSE' => [
        'label' => 'Pengajuan lisensi',
        'deskripsi' => 'Pengajuan lisensi software baru dan perpanjangan lisensi.',
        'dipakai_di' => ['Lisensi › Ajukan lisensi', 'Lisensi › Perpanjang'],
        'fallback' => null,
        'aktif' => true,
        'wajib' => true,
    ],
];
