# AMS IT OPS

Asset Management System untuk tim IT Operations PT Bina Sarana Sukses. Aplikasi ini mencatat aset IT dari pengadaan sampai penghapusan, lengkap dengan persetujuan berjenjang, berita acara PDF, notifikasi WhatsApp, dan monitoring Fortigate per jobsite.

## Fitur

| Area | Isi |
|---|---|
| Aset | Inventaris, detail & riwayat lengkap (audit log), QC aset baru, cetak label QR (QR + no. aset + SN) |
| Transaksi | Serah terima (BAST), pengembalian + inspeksi, mutasi antar jobsite, peminjaman sementara, laporan kerusakan, disposal / kehilangan |
| Berita acara | PDF BAST, pengembalian, dan mutasi; bisa dicetak ulang untuk transaksi lama |
| Stock opname | Sesi per jobsite, scan QR / barcode SN, kondisi & lokasi diisi setelah aset ditemukan, ekspor CSV |
| Pengajuan | Permintaan aset (PR, PO, retur QC) dan permintaan lisensi |
| Persetujuan | Workflow validasi per jenis transaksi, jobsite, dan jabatan; lonceng notifikasi; perbaikan approval yang macet |
| Integrasi | WhatsApp gateway (scan QR dari aplikasi), banyak Fortigate per jobsite |
| Master data | User & role, jabatan, kategori aset, jobsite, departemen, entitas, cost center, lisensi |

## Teknologi

- Laravel 12, Livewire 3, PHP 8.2+ (dites di PHP 8.5)
- SQL Server (driver `sqlsrv` + Microsoft ODBC Driver 18)
- Tema Sneat (Bootstrap 5, Boxicons)
- Spatie Permission, owen-it/laravel-auditing, DomPDF, simple-qrcode, Maatwebsite Excel

## Persyaratan server

- PHP 8.2+ dengan ekstensi `sqlsrv`, `pdo_sqlsrv`, `gd`, `zip`, `mbstring`, `intl`
- Microsoft ODBC Driver 18 for SQL Server
  - macOS (Homebrew): ODBC 18 butuh `openssl@3`. Jika muncul error *"OpenSSL library could not be loaded"*, tautkan ulang ke `openssl@3`.
  - Pastikan ekstensi `sqlsrv` hanya dimuat sekali di `php.ini` (hindari peringatan *"Module already loaded"*).
- Composer 2
- Akses jaringan ke server SQL, WhatsApp gateway, dan Fortigate (untuk monitoring)

## Instalasi (lokal / server baru)

```bash
git clone <repo> ams && cd ams
composer install
cp .env.example .env
php artisan key:generate
# isi .env (lihat bagian Konfigurasi), lalu:
php artisan config:clear
php artisan serve
```

Halaman login ada di `/`. Login memakai **NIK**. Pengguna yang belum pernah login diminta membuat password sendiri (minimal 8 karakter).

## Konfigurasi `.env`

Jangan pernah commit file `.env`. Semua kunci di bawah ini ada contohnya di `.env.example`.

| Kunci | Keterangan |
|---|---|
| `DB_CONNECTION=sqlsrv`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Database utama AMS |
| `DB_HRD_*` | Database HRD (data karyawan & email) |
| `APP_IT_DEPARTMENT_ID` | ID departemen IT (default `8`); menentukan siapa yang dianggap staf IT |
| `MAIL_*` | SMTP utama |
| `MAIL2_HOST`, `MAIL2_PORT`, `MAIL2_USERNAME`, `MAIL2_PASSWORD`, `MAIL2_VERIFY_PEER` | SMTP kedua (notifikasi pengajuan) |
| `WHATSAPP_API_URL`, `WHATSAPP_API_KEY`, `WHATSAPP_INSTANCE`, `WHATSAPP_VERIFY_SSL` | WhatsApp gateway untuk semua notifikasi WA |
| `FORTIGATE_API_URL`, `FORTIGATE_API_TOKEN`, `FORTIGATE_VERIFY_SSL` | Hanya dipakai sekali oleh migrasi Fortigate untuk memindahkan perangkat lama. Setelah itu perangkat dikelola dari aplikasi. |
| `AINYA_*`, `N8N_WEBHOOK_URL`, `N8N_SECRET_TOKEN`, `GEMINI_API_KEY` | Integrasi ISR billing, n8n, dan AI chat |

Setelah mengubah `.env` di server, jalankan `php artisan config:clear` (atau `config:cache` di produksi).

## Database & migrasi

> **Penting untuk database produksi:** jangan menjalankan `php artisan migrate` tanpa `--path`. Ada beberapa migrasi lama yang statusnya masih *pending* dan belum siap dijalankan di produksi. Jalankan migrasi baru satu per satu.

```bash
php artisan migrate:status                       # lihat yang pending
php artisan migrate --path=database/migrations/<nama_file>.php --force
```

Migrasi yang sudah dijalankan di produksi lewat `--path`:

| File | Isi |
|---|---|
| `2026_11_22_120000_create_stock_opname_tables.php` | Tabel `stock_opnames` & `stock_opname_items` |
| `2026_11_22_150000_create_fortigate_devices_table.php` | Tabel `fortigate_devices`; Fortigate dari `.env` otomatis dipindahkan sebagai perangkat pertama |

Migrasi lama yang masih pending: `master_pivots`, `master_forecasts`, `software_licenses_details`, `isr_billings`, `softwares`, `materials`, `audits`. Akibatnya halaman `/software`, `/licenses`, dan `/coa` error 500 sampai tabelnya dibuat. Periksa dulu apakah tabel tersebut sudah ada dengan nama lain sebelum menjalankannya.

Catatan khusus SQL Server:

- `LIKE` memperlakukan `[` sebagai karakter khusus. Untuk mencari teks berawalan `[Pinjam]`, `[Kehilangan]`, dll., pakai `App\Support\SqlLike::prefix()` / `escape()`.
- Kolom `asset_transactions.transaction_type` punya CHECK constraint: hanya `HANDOVER`, `RETURN`, `MUTATION`, `DISPOSED`, `DISPOSE`, `DAMAGE_REPORT`. Peminjaman memakai `HANDOVER`/`RETURN` dengan penanda `[Pinjam]` / `[Kembali pinjam]` di kolom `notes`.

## Integrasi

### WhatsApp

Semua notifikasi WA (BAST, peminjaman, approval mutasi, disposal, pengajuan aset) dikirim lewat fungsi `sendMessageWithWhatsApp($nomor, $teks)` di `app/helpers.php`, yang memakai `App\Support\WhatsAppGateway`.

1. Isi `WHATSAPP_*` di `.env`, lalu `php artisan config:clear`.
2. Buka **Administrasi → Integrasi → WhatsApp** (butuh permission `Whatsapp`).
3. Klik **Hubungkan dengan scan QR**. Di HP buka WhatsApp → Perangkat tertaut → Tautkan perangkat, lalu scan. Kalau tidak bisa scan, pakai **kode pairing**.
4. Kirim **pesan uji** untuk memastikan.

Jika WA belum terhubung atau gagal, notifikasi hanya dilewati dan dicatat di log. Transaksi tetap berjalan.

### Fortigate

Kelola di **Administrasi → Integrasi → Fortigate** (permission `Fortigate`).

- Bisa lebih dari satu perangkat, masing-masing dikaitkan ke jobsite. API token disimpan terenkripsi.
- Cara membuat token di Fortigate: System → Administrators → Create New → **REST API Admin**. Tambahkan IP server AMS ke *Trusted Hosts*. Untuk sertifikat bawaan (self-signed), matikan "Verifikasi sertifikat SSL".
- Halaman daftar tidak menghubungi perangkat. Data diambil saat tab di halaman detail dibuka: Ringkasan, Interface, DHCP, ARP, Routing, VPN IPsec, SD-WAN, dan API (hanya baca).

## Permission utama

| Permission | Akses |
|---|---|
| `Master Asset` | Inventaris, QC, peminjaman, kerusakan, stock opname, label QR, COA |
| `Mutasi` | Mutasi dan disposal |
| `Manajement Request` | PR, PO, retur QC |
| `Lisensi` | Lisensi & software |
| `Add User`, `Add Permissions` | Pengguna, role & permission |
| `Add Workflow` | Workflow validasi & jenis transaksi |
| `Whatsapp`, `Fortigate` | Halaman integrasi |
| `Logs` | Audit log |

Jenis transaksi untuk workflow diatur di `config/transaction_types.php` (tanpa tabel). Transaksi akan ditolak dengan pesan jelas bila workflow untuk jenis, jobsite, dan jabatan terkait belum diatur.

## Deploy ke produksi

```bash
git pull
composer install --no-dev --optimize-autoloader
# jalankan migrasi BARU saja, satu per satu (lihat bagian Database & migrasi)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan `APP_DEBUG=false` dan `APP_ENV=production`.

File upload (foto profil, lampiran) disajikan lewat route `storage.public` yang memeriksa izin akses. Jangan membuat symlink `public/storage` (`php artisan storage:link`), dan hapus symlink itu jika sudah ada, karena symlink membuat semua file bisa diakses tanpa login.

## Struktur penting

| Lokasi | Isi |
|---|---|
| `app/Livewire/` | Halaman (komponen Livewire) |
| `app/Support/` | Logika bersama: `Workflow`, `AssetLoan`, `AssetDisposal`, `TransactionApprovalRepair`, `TransactionNumber`, `WhatsAppGateway`, `FortigateClient`, `BaFormat`, `SqlLike` |
| `resources/views/pdf/` | Template berita acara; bagian bersama ada di `pdf/partials/` |
| `public/assets/css/app.css` | Gaya global di atas tema Sneat |
| `config/transaction_types.php` | Master jenis transaksi untuk workflow |

## Tes

```bash
php artisan test
```
