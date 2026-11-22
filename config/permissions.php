<?php

/*
|--------------------------------------------------------------------------
| Katalog permission
|--------------------------------------------------------------------------
| Permission di sini dipakai langsung di kode (@can, middleware can:...),
| sehingga dikunci: tidak bisa diganti nama atau dihapus dari halaman
| Role & Permission. Permission di database yang tidak ada di sini tampil
| di kelompok "Lainnya" dan boleh dihapus bila tidak dipakai role.
|
| Format: 'Kelompok' => ['Nama permission' => 'Keterangan', ...]
*/

return [
    'groups' => [
        'Aset' => [
            'Master Asset' => 'Inventaris aset (BAST, pengembalian, inspeksi), master COA & forecast, inventory parts',
            'Edit Inventaris' => 'Tombol ubah data aset di halaman inventaris',
            'Pemegang Aset' => 'Rekap aset yang dipegang setiap user beserta detail dan riwayatnya',
            'QC Aset' => 'QC aset baru dari pengadaan sebelum masuk inventaris',
            'Peminjaman' => 'Peminjaman aset sementara: pinjamkan, terima kembali, perpanjang, kirim pengingat',
            'Kerusakan Aset' => 'Laporan kerusakan dan perbaikan aset',
            'Stock Opname' => 'Sesi stock opname per jobsite dengan scan QR / SN',
            'Cetak Label' => 'Cetak label QR aset (banyak sekaligus atau satuan)',
            'Mutasi' => 'Mutasi aset antar jobsite dan pengajuan disposal',
        ],
        'Pengadaan' => [
            'Manajement Request' => 'Proses permintaan aset: PR & no. akuntansi, purchase order, retur QC',
        ],
        'Lisensi' => [
            'Lisensi' => 'Lisensi, software, kategori & manufaktur lisensi, serta melihat semua permintaan lisensi',
        ],
        'Dashboard' => [
            'Show Dashboard' => 'Dashboard IT (statistik aset, approval, dll.)',
        ],
        'Pengguna & akses' => [
            'Add User' => 'Daftar user: tambah, ubah, nonaktifkan, reset password',
            'Add Permissions' => 'Halaman Role & Permission (halaman ini)',
        ],
        'Master data' => [
            'Add Jabatan' => 'Jabatan dan level jabatan',
            'Manajement IT' => 'Standar aset per jabatan',
            'Add Type' => 'Tipe aset',
            'Add Brands' => 'Merek aset',
            'Add Series' => 'Seri & spesifikasi aset',
            'Add Jobsites' => 'Jobsite',
            'Add Dept' => 'Departemen',
            'Add Entitas' => 'Entitas',
            'Add Cost Center' => 'Cost center',
        ],
        'Workflow & audit' => [
            'Add Workflow' => 'Aturan workflow validasi, jenis transaksi, dan perbaikan approval yang macet',
            'Logs' => 'Audit log seluruh perubahan data',
            'Pengaturan Tampilan' => 'Tampilan halaman login: gambar latar, logo, judul, dan poin',
        ],
        'Integrasi' => [
            'Whatsapp' => 'Integrasi WhatsApp: scan QR, status, pesan uji, grup',
            'Fortigate' => 'Integrasi Fortigate: daftar perangkat, monitoring, log keamanan',
            'Isr' => 'Billing ISR',
        ],
    ],

    /** Role yang hanya boleh diubah oleh pemilik role ini sendiri, dan tidak bisa dihapus. */
    'protected_roles' => ['Administrator'],

    /** Permission yang tidak boleh dicabut dari role terlindungi (mencegah terkunci dari halaman ini). */
    'locked_on_protected' => ['Add Permissions', 'Add User'],
];
