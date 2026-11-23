# API Mobile AMS (v1)

API JSON untuk aplikasi Flutter. Base URL: `https://<domain-ams>/api/v1`

Semua request mengirim header `Accept: application/json`. Selain login, semua endpoint butuh header:

```
Authorization: Bearer <token>
```

## Konvensi

| Hal | Aturan |
|---|---|
| Format tanggal | `YYYY-MM-DD` untuk tanggal, ISO 8601 (`2026-11-23T10:15:00+07:00`) untuk waktu |
| Angka | Selalu integer/number, bukan string |
| Daftar berhalaman | `data[]` + `meta` (`current_page`, `last_page`, `total`, ...) + `links`. Kirim `?page=2` untuk halaman berikutnya |
| Error | Selalu JSON: `{"message": "..."}`. Validasi (422) menambah `errors: {field: [pesan]}` |
| Kode status | `401` token tidak ada/kedaluwarsa → arahkan ke login · `403` tidak punya izin · `404` tidak ditemukan · `409` konflik (lihat endpoint) · `422` input salah · `429` terlalu banyak percobaan |

Pesan error sudah dalam Bahasa Indonesia dan boleh ditampilkan langsung ke pengguna.

---

## 1. Autentikasi

### `POST /auth/login`
Login dengan NIK & password (sama dengan web). Maksimal 5 percobaan gagal per menit.

```json
{ "nik": "1010436", "password": "rahasia", "device_name": "Samsung A54 - Budi" }
```

Respons `200`:
```json
{
  "token": "12|xxxxxxxx",
  "token_type": "Bearer",
  "expires_at": "2026-12-23T10:15:00+07:00",
  "user": { ...lihat /me... }
}
```
Token berlaku **30 hari**. Simpan di secure storage (mis. `flutter_secure_storage`). Akun nonaktif ditolak `403`.

### `POST /auth/logout`
Mencabut token yang sedang dipakai.

### `GET /me`
Profil + izin. Pakai `features` untuk menampilkan/menyembunyikan menu.

```json
{
  "user": {
    "id": 65, "name": "Dyas Aditya Pratama", "nik": "1010436",
    "position": { "id": 1378, "name": "Kepala Seksi IT Operation" },
    "department": { "id": 8, "code": "IT", "name": "INFORMATION TECHNOLOGY" },
    "jobsite": { "id": 22, "code": "JKT", "name": "Jakarta" },
    "roles": ["Administrator"],
    "permissions": ["Master Asset", "Stock Opname", "..."],
    "features": { "stock_opname": true, "submit_asset_request_for_others": false }
  }
}
```

---

## 2. Aset

### `GET /assets/mine?status=&q=`
Aset yang sedang dipegang user. `status`: `pending` (menunggu konfirmasi terima) · `used` · `borrowed`. `q`: cari No. aset / SN / tipe / seri.
Respons berhalaman + `counts: {total, pending, used, borrowed}`.

### `GET /assets/lookup?code=`
Cari aset dari hasil scan. `code` boleh berupa isi QR label (No. aset atau link `.../asset/show/{no}`), No. aset lama, atau barcode SN.
- `200` → aset + `matched_by` (`no_aset` | `no_lama` | `sn`)
- `404` → tidak ditemukan
- `409` → SN dipakai beberapa aset; `candidates` berisi No. aset. Minta user scan QR label.
- `403` → user tidak berhak melihat aset ini

### `GET /assets/{id}`
Detail aset. Akses sama dengan web: pemegang aset, pihak di serah terima, atau user dengan izin Master Asset / Mutasi / tim IT.

Objek aset:
```json
{
  "id": 44086, "asset_number": "BSSUPS25100148", "old_asset_number": "7906-BSS-1223",
  "serial_number": "1H1KT2300319", "status": "Used", "current_location_detail": "...",
  "type": {"id": 17, "name": "UPS"}, "brand": {"id": 30, "name": "ICA"}, "series": {"id": 4821, "name": "SE3100"},
  "jobsite": {"id": 22, "code": "JKT", "name": "Jakarta"},
  "holder": {"id": 65, "name": "...", "nik": "..."},
  "label_url": "https://.../asset/show/BSSUPS25100148",
  "updated_at": "..."
}
```

---

## 3. Pengajuan aset

### Alur di aplikasi
1. `GET /asset-requests/form` → tampilkan item standar jabatan, alur persetujuan, dan `problems`.
2. Bila `can_submit = false`, tampilkan `problems` dan nonaktifkan tombol kirim.
3. User menambah/ubah item memakai katalog (tipe → merek → seri).
4. `POST /asset-requests`.

### `GET /asset-requests/form?for_user_id=`
`for_user_id` hanya diisi bila mengajukan untuk orang lain.

```json
{
  "requester": { ...user penerima... },
  "for_other": false,
  "can_submit": true,
  "problems": [],
  "default_needed_date": "2026-12-07",
  "standard_items": [
    { "asset_type_id": 4, "asset_brand_id": 1, "asset_series_id": 5357, "quantity": 1,
      "item_description": "...", "is_standard": true,
      "type_name": "Laptop", "brand_name": "ASUS", "series_name": "..." }
  ],
  "approval_flow": [ { "step_order": 1, "role": "Atasan Langsung", "approver": {"id": 10, "name": "..."} } ]
}
```

### Katalog
- `GET /catalog/asset-types`
- `GET /catalog/asset-brands?asset_type_id=`
- `GET /catalog/asset-series?asset_brand_id=`

### Mengajukan untuk orang lain
Hanya bila `features.submit_asset_request_for_others = true`. Penerima harus karyawan **aktif di departemen yang sama**.
- `GET /asset-requests/beneficiaries?q=` (minimal **4 karakter**, maks. 10 hasil). Tampilkan indikator loading saat mencari.
- Setelah memilih, panggil ulang `/asset-requests/form?for_user_id=<id>`: item standar & alur persetujuan mengikuti **jabatan dan site penerima**.

### `POST /asset-requests`
JSON, atau `multipart/form-data` bila melampirkan berita acara.

| Field | Wajib | Keterangan |
|---|---|---|
| `for_user_id` | – | Penerima (kosong = diri sendiri) |
| `purpose` | ya | 10–1000 karakter |
| `needed_date` | ya | `YYYY-MM-DD`, tidak boleh lewat |
| `items[n][asset_type_id]`, `[asset_brand_id]`, `[asset_series_id]` | ya | Dari katalog |
| `items[n][quantity]` | ya | 1–1000 |
| `items[n][item_description]` | – | Maks. 500 |
| `items[n][is_standard]` | ya | `true` bila dari `standard_items` |
| `ba_damage` | – | File BA kerusakan (pdf/doc/docx/png/jpg, maks. 2 MB) |
| `ba_budget_outside` | – | File BA di luar budget (sama) |

Respons `201` berisi pengajuan lengkap (lihat di bawah). `422` bila input salah atau `problems` belum beres.

### `GET /asset-requests?status=`
Pengajuan saya (sebagai penerima **atau** pengisi form), berhalaman.

### `GET /asset-requests/{id}`
Detail + item + tahapan approval.

```json
{
  "id": 121, "request_number": "REQ/JKT/2603/0002", "status": "Pending Approval",
  "request_date": "2026-03-06", "needed_date": "2026-03-25", "purpose": "...",
  "requester": {...}, "submitted_by": null,
  "items": [ { "id": 1, "type": {...}, "brand": {...}, "series": {...}, "quantity": 1, "is_standard": true, "it_approval_status": "Pending" } ],
  "approvals": [ { "id": 9, "step_order": 1, "role": "Atasan Langsung", "status": "Approved", "approver": {...}, "action_by": {...}, "action_at": "...", "notes": null } ]
}
```
`submitted_by` terisi bila pengajuan dibuat orang lain atas nama `requester`.

---

## 4. Approval

### `GET /approvals`
Semua yang **sedang menunggu giliran saya** (pengajuan aset + mutasi/disposal).

```json
{
  "data": [
    { "kind": "asset_request", "approval_id": 77, "step_order": 2, "role": "Kepala Departemen",
      "reference_id": 121, "number": "REQ/JKT/2603/0002", "title": "Pengajuan aset",
      "subtitle": "Budi · JKT", "description": "...", "date": "2026-03-06" },
    { "kind": "transaction", "approval_id": 59, "step_order": 3, "role": "Kepala Seksi",
      "reference_id": 10584, "number": "BSS/MAS/MT/2601/0001", "title": "Mutasi aset",
      "subtitle": "Arwan · MAS → BAYAN", "description": "...", "date": "2026-01-02" }
  ],
  "counts": { "asset_request": 1, "transaction": 1, "total": 2 }
}
```

Detail sebelum memutuskan:
- `kind = asset_request` → `GET /asset-requests/{reference_id}`
- `kind = transaction` → `GET /approvals/transactions/{approval_id}` (berisi daftar aset, site asal/tujuan, tahapan, `can_decide`)

### Memutuskan
- `POST /approvals/asset-requests/{approval_id}/decision`
- `POST /approvals/transactions/{approval_id}/decision`

```json
{ "action": "approve" }
{ "action": "reject", "notes": "Alasan minimal 10 karakter" }
```
`403` bila bukan giliran user atau tahap sudah diputuskan (mis. sudah di-approve dari web). Setelah sukses, muat ulang `GET /approvals`.

---

## 5. Stock opname

Butuh izin **Stock Opname** (`features.stock_opname`). Membuka & menutup sesi tetap dilakukan di web.

### `GET /stock-opnames?status=open|closed`
Sesi terbuka tampil paling atas.

### `GET /stock-opnames/{id}`
Ringkasan + `counts: {expected, found, missing, other_site, not_registered}`.

### `POST /stock-opnames/{id}/scan`
```json
{ "code": "<isi QR label / barcode SN / No. aset>" }
```
Respons:
```json
{
  "feedback": { "type": "success", "title": "Ditemukan: BSSJKTLP22030001", "text": "Laptop ASUS · Budi · status Used" },
  "recorded": true,
  "duplicate": false,
  "result": "found",
  "editable": true,
  "item": { "id": 501, "result": "found", "condition": "baik", "location_note": null, "asset": {...}, "scanned_at": "..." }
}
```
- `feedback.type` → warna & bunyi: `success` (hijau), `warning` (kuning, aset site lain / SN ganda), `danger` (merah, tidak terdaftar), `info` (sudah discan / kode tidak valid).
- `result`: `found` · `other_site` · `not_registered` · `null` (tidak dicatat).
- `editable = true` → tampilkan pilihan kondisi & lokasi untuk item ini.
- Status `409` bila sesi sudah ditutup.

Tips: abaikan kode yang sama berturut-turut dalam 3 detik di sisi aplikasi (kamera membaca berkali-kali).

### `PATCH /stock-opnames/{id}/items/{item_id}`
```json
{ "condition": "rusak", "location": "Gudang IT rak 3" }
```
Lokasi kosong = lokasi aset tidak diubah. Lokasi diisi = lokasi aset di master ikut diperbarui (untuk hasil `found`).

### `DELETE /stock-opnames/{id}/items/{item_id}`
Batalkan scan.

### `GET /stock-opnames/{id}/items?tab=&q=`
`tab`: `missing` (default, aset yang belum discan) · `found` · `other_site` · `not_registered`. Berhalaman (30 per halaman).
Tab `missing` mengembalikan objek aset; tab lain mengembalikan item scan.
