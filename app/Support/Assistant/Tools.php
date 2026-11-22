<?php

namespace App\Support\Assistant;

use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\AssetRequestApproval;
use App\Models\AssetTransaction;
use App\Models\AssetTransactionApproval;
use App\Models\Jobsite;
use App\Models\User;
use App\Support\AssetHolding;
use App\Support\AssetLoan;
use App\Support\SqlLike;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Alat data untuk asisten chat. AI hanya bisa membaca data lewat alat ini, dan setiap alat
 * dicek terhadap permission user yang bertanya. Tidak ada alat yang membaca konfigurasi,
 * password, atau tabel pengguna secara mentah.
 */
class Tools
{
    /** Baris maksimum per dataset (file ekspor) dan yang dikirim ke AI. */
    public const MAX_ROWS = 2000;
    public const ROWS_TO_MODEL = 40;

    public function __construct(protected User $user, protected string $sessionId)
    {
    }

    /** Definisi alat: nama => [deskripsi, parameter JSON schema, syarat akses]. */
    protected function definitions(): array
    {
        return [
            'aset_saya' => [
                'Daftar aset yang sedang dipegang user yang bertanya (aset atas namanya).',
                ['type' => 'object', 'properties' => new \stdClass()],
                fn () => true,
            ],
            'persetujuan_saya' => [
                'Daftar pengajuan/transaksi yang sedang menunggu persetujuan user yang bertanya.',
                ['type' => 'object', 'properties' => new \stdClass()],
                fn () => true,
            ],
            'pengajuan_saya' => [
                'Daftar permintaan aset yang diajukan user yang bertanya beserta statusnya.',
                ['type' => 'object', 'properties' => ['status' => ['type' => 'string', 'description' => 'Filter status, opsional']]],
                fn () => true,
            ],
            'cari_aset' => [
                'Cari aset di inventaris (semua user). Bisa filter kata kunci (no. aset/SN/tipe/merek), kode jobsite, status, tipe.',
                ['type' => 'object', 'properties' => [
                    'kata_kunci' => ['type' => 'string'],
                    'jobsite' => ['type' => 'string', 'description' => 'Kode jobsite, mis. JKT, MOB'],
                    'status' => ['type' => 'string', 'description' => 'Free, Used, Pre-Used, Borrowed, Repair, Disposed, dll.'],
                    'tipe' => ['type' => 'string', 'description' => 'Nama tipe aset, mis. Laptop, Radio HT'],
                ]],
                fn (User $u) => $u->can('Master Asset'),
            ],
            'referensi' => [
                'Kamus data AMS: daftar tipe aset (dengan jumlah), merek per tipe, kode jobsite, status aset, dan departemen. '
                . 'Pakai untuk memetakan istilah user (mis. "HT" = Radio HT, "kantor pusat" = JKT) sebelum memanggil alat lain.',
                ['type' => 'object', 'properties' => ['tipe' => ['type' => 'string', 'description' => 'Opsional: tampilkan merek untuk tipe ini']]],
                fn () => true,
            ],
            'ringkasan_aset' => [
                'Hitung jumlah aset dengan pengelompokan 1–2 tingkat. Contoh: total laptop per merek → kelompok=["merek"], tipe="Laptop"; '
                . 'laptop per merek per jobsite → kelompok=["merek","jobsite"]; radio per status → kelompok=["status"], tipe="Radio". '
                . 'Status "aktif" = semua kecuali Disposed/Scrap.',
                ['type' => 'object', 'properties' => [
                    'kelompok' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['status', 'tipe', 'merek', 'seri', 'jobsite', 'departemen']], 'maxItems' => 2],
                    'tipe' => ['type' => 'string'], 'merek' => ['type' => 'string'], 'seri' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'description' => 'Nama status atau "aktif"'],
                    'jobsite' => ['type' => 'string', 'description' => 'Kode jobsite'],
                ], 'required' => ['kelompok']],
                fn (User $u) => $u->canAny(['Master Asset', 'Pemegang Aset']),
            ],
            'detail_aset' => [
                'Detail lengkap satu aset (cari dengan no. aset, no. aset lama, atau serial number): spesifikasi, status, pemegang, lokasi, harga, dan 10 riwayat transaksi terakhir.',
                ['type' => 'object', 'properties' => ['nomor' => ['type' => 'string']], 'required' => ['nomor']],
                fn () => true, // dicek per aset dengan gate view-asset
            ],
            'pengajuan_disposal' => [
                'Pengajuan disposal/penghapusan aset (rusak berat, hilang, lainnya). Bisa filter status: belum_divalidasi, selesai, ditolak, semua. '
                . 'Menampilkan approver yang sedang ditunggu dan berapa hari sudah menunggu.',
                ['type' => 'object', 'properties' => [
                    'status' => ['type' => 'string', 'enum' => ['belum_divalidasi', 'selesai', 'ditolak', 'semua']],
                    'alasan' => ['type' => 'string', 'enum' => ['hilang', 'rusak', 'semua']],
                    'jobsite' => ['type' => 'string'],
                    'minimal_hari' => ['type' => 'integer', 'description' => 'Hanya yang menunggu >= N hari'],
                ]],
                fn (User $u) => $u->canAny(['Mutasi', 'Master Asset']),
            ],
            'persetujuan_tertunda' => [
                'Semua persetujuan yang masih tertunda di sistem (permintaan aset & transaksi aset), per approver atau daftar lengkap. '
                . 'Untuk mencari siapa yang menahan proses dan sejak kapan.',
                ['type' => 'object', 'properties' => [
                    'tampilan' => ['type' => 'string', 'enum' => ['per_approver', 'daftar']],
                    'jenis' => ['type' => 'string', 'enum' => ['permintaan_aset', 'transaksi', 'semua']],
                ]],
                fn (User $u) => $u->can('Add Workflow') || $u->hasRole(['Administrator', 'Administrator IT']),
            ],
            'daftar_pengajuan_aset' => [
                'Semua permintaan aset (bukan hanya milik user) dengan filter status, jobsite, dan tanggal; termasuk approver yang ditunggu.',
                ['type' => 'object', 'properties' => [
                    'status' => ['type' => 'string', 'description' => 'mis. Pending Approval, Pending PO, Pending QC, Completed, Rejected, atau "berjalan"'],
                    'jobsite' => ['type' => 'string'],
                    'dari_tanggal' => ['type' => 'string'], 'sampai_tanggal' => ['type' => 'string'],
                ]],
                fn (User $u) => $u->can('Manajement Request'),
            ],
            'laporan_kerusakan' => [
                'Laporan kerusakan aset dengan filter status (PendingAssessment, Under Repair, Awaiting Parts, Beyond Economic Repair, Closed, Cancelled, atau "terbuka"), jobsite, tipe.',
                ['type' => 'object', 'properties' => [
                    'status' => ['type' => 'string'], 'jobsite' => ['type' => 'string'], 'tipe' => ['type' => 'string'],
                ]],
                fn (User $u) => $u->can('Kerusakan Aset'),
            ],
            'stock_opname' => [
                'Sesi stock opname dan hasilnya: jumlah aset seharusnya, ditemukan, di site lain, tidak terdaftar, dan belum ditemukan.',
                ['type' => 'object', 'properties' => ['jobsite' => ['type' => 'string'], 'hanya_terbuka' => ['type' => 'boolean']]],
                fn (User $u) => $u->can('Stock Opname'),
            ],
            'pemegang_aset' => [
                'Rekap user yang memegang aset (jumlah aset per user). Bisa filter nama/NIK, jobsite aset, minimal jumlah.',
                ['type' => 'object', 'properties' => [
                    'kata_kunci' => ['type' => 'string', 'description' => 'Nama atau NIK'],
                    'jobsite' => ['type' => 'string'],
                    'minimal' => ['type' => 'integer'],
                ]],
                fn (User $u) => $u->can('Pemegang Aset'),
            ],
            'aset_milik_user' => [
                'Daftar aset yang dipegang satu user tertentu (cari dengan NIK atau nama).',
                ['type' => 'object', 'properties' => ['nik_atau_nama' => ['type' => 'string']], 'required' => ['nik_atau_nama']],
                fn (User $u) => $u->can('Pemegang Aset'),
            ],
            'peminjaman_aktif' => [
                'Aset yang sedang dipinjam beserta peminjam dan tanggal harus kembali (termasuk yang terlambat).',
                ['type' => 'object', 'properties' => ['hanya_terlambat' => ['type' => 'boolean']]],
                fn (User $u) => $u->can('Peminjaman'),
            ],
            'transaksi_aset' => [
                'Riwayat transaksi aset: serah terima (HANDOVER), pengembalian (RETURN), mutasi (MUTATION), disposal (DISPOSED).',
                ['type' => 'object', 'properties' => [
                    'jenis' => ['type' => 'string', 'enum' => ['HANDOVER', 'RETURN', 'MUTATION', 'DISPOSED']],
                    'status' => ['type' => 'string'],
                    'dari_tanggal' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                    'sampai_tanggal' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                ]],
                fn (User $u) => $u->canAny(['Master Asset', 'Mutasi']),
            ],
            'buat_file' => [
                'Buat file PDF atau Excel dari hasil alat sebelumnya (pakai dataset_id dari hasil alat). Panggil ini jika user minta dijadikan PDF/Excel.',
                ['type' => 'object', 'properties' => [
                    'dataset_id' => ['type' => 'string'],
                    'format' => ['type' => 'string', 'enum' => ['pdf', 'xlsx']],
                    'judul' => ['type' => 'string'],
                ], 'required' => ['dataset_id', 'format']],
                fn () => true,
            ],
        ];
    }

    /** Alat yang boleh dipakai user ini, dalam format OpenAI tools. */
    public function available(): array
    {
        $tools = [];
        foreach ($this->definitions() as $name => [$desc, $params, $can]) {
            if ($can($this->user)) {
                $tools[] = ['type' => 'function', 'function' => ['name' => $name, 'description' => $desc, 'parameters' => $params]];
            }
        }

        return $tools;
    }

    public function allowedNames(): array
    {
        return array_column(array_column($this->available(), 'function'), 'name');
    }

    /**
     * Jalankan alat. Mengembalikan array hasil untuk AI; bila ada file, juga 'file' untuk ditampilkan.
     */
    public function run(string $name, array $args): array
    {
        $defs = $this->definitions();
        if (!isset($defs[$name]) || !$defs[$name][2]($this->user)) {
            return ['error' => 'Alat tidak tersedia untuk role Anda.'];
        }

        try {
            return match ($name) {
                'aset_saya' => $this->dataset('Aset saya', $this->assetColumns(), $this->assetRows(AssetHolding::query()->where('holder_user_id', $this->user->id))),
                'persetujuan_saya' => $this->myApprovals(),
                'pengajuan_saya' => $this->myRequests($args),
                'cari_aset' => $this->searchAssets($args),
                'referensi' => $this->reference($args),
                'ringkasan_aset' => $this->assetSummary($args),
                'detail_aset' => $this->assetDetail($args),
                'pengajuan_disposal' => $this->disposals($args),
                'persetujuan_tertunda' => $this->pendingApprovals($args),
                'daftar_pengajuan_aset' => $this->allRequests($args),
                'laporan_kerusakan' => $this->damages($args),
                'stock_opname' => $this->opnames($args),
                'pemegang_aset' => $this->holders($args),
                'aset_milik_user' => $this->assetsOfUser($args),
                'peminjaman_aktif' => $this->loans($args),
                'transaksi_aset' => $this->transactions($args),
                'buat_file' => $this->makeFile($args),
            };
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'Gagal mengambil data.'];
        }
    }

    // ===================== Dataset & file =====================

    /** Simpan dataset (untuk buat_file) dan kembalikan ringkasan untuk AI. */
    protected function dataset(string $title, array $columns, array $rows, array $extra = [], int $toModel = self::ROWS_TO_MODEL): array
    {
        $rows = array_slice($rows, 0, self::MAX_ROWS);
        $id = Str::lower(Str::random(10));
        Cache::put($this->cacheKey($id), ['title' => $title, 'columns' => $columns, 'rows' => $rows, 'user_id' => $this->user->id], now()->addHours(2));

        return [
            'dataset_id' => $id,
            'judul' => $title,
            'total_baris' => count($rows),
            'kolom' => array_values($columns),
            'baris' => array_map(fn ($r) => array_combine(array_values($columns), array_map(fn ($k) => $r[$k] ?? null, array_keys($columns))), array_slice($rows, 0, $toModel)),
            'catatan' => count($rows) > $toModel ? "Hanya {$toModel} baris pertama yang ditampilkan di sini; file PDF/Excel berisi semua baris." : null,
        ] + $extra;
    }

    protected function cacheKey(string $id): string
    {
        return "assistant:dataset:{$this->sessionId}:{$id}";
    }

    protected function makeFile(array $args): array
    {
        $data = Cache::get($this->cacheKey((string) ($args['dataset_id'] ?? '')));
        if (!$data || (int) $data['user_id'] !== $this->user->id) {
            return ['error' => 'Dataset tidak ditemukan atau sudah kedaluwarsa. Ambil datanya lagi dulu.'];
        }
        $format = ($args['format'] ?? 'xlsx') === 'pdf' ? 'pdf' : 'xlsx';
        $title = trim((string) ($args['judul'] ?? '')) ?: $data['title'];
        $file = FileExporter::make($title, $data['columns'], $data['rows'], $format, $this->user);

        return ['ok' => true, 'pesan' => "File {$file['name']} sudah dibuat dan dikirim di chat.", 'file' => $file];
    }

    // ===================== Alat data =====================

    protected function assetColumns(): array
    {
        return ['asset_number' => 'No. Aset', 'type' => 'Tipe', 'brand_series' => 'Merek / Seri', 'serial_number' => 'Serial Number',
            'status' => 'Status', 'jobsite' => 'Jobsite', 'holder' => 'Pemegang'];
    }

    protected function assetRows($query): array
    {
        return $query->with(['assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name', 'jobsite:id,code', 'holder:id,name,nik'])
            ->orderBy('asset_number')->limit(self::MAX_ROWS)->get()
            ->map(fn ($a) => [
                'asset_number' => $a->asset_number,
                'type' => $a->assetType->name ?? '',
                'brand_series' => trim(($a->assetBrand->name ?? '') . ' ' . ($a->assetSeries->name ?? '')),
                'serial_number' => $a->serial_number,
                'status' => $a->status,
                'jobsite' => $a->jobsite->code ?? '',
                'holder' => $a->holder ? "{$a->holder->name} ({$a->holder->nik})" : '',
            ])->all();
    }

    protected function jobsiteId(?string $code): ?int
    {
        return $code ? Jobsite::where('code', strtoupper(trim($code)))->value('id') : null;
    }

    protected function searchAssets(array $a): array
    {
        $q = Asset::query();
        if ($kw = trim((string) ($a['kata_kunci'] ?? ''))) {
            $term = '%' . SqlLike::escape($kw) . '%';
            $q->where(fn ($w) => $w->where('asset_number', 'like', $term)->orWhere('serial_number', 'like', $term)->orWhere('old_asset_number', 'like', $term)
                ->orWhereHas('assetType', fn ($t) => $t->where('name', 'like', $term))
                ->orWhereHas('assetBrand', fn ($t) => $t->where('name', 'like', $term))
                ->orWhereHas('assetSeries', fn ($t) => $t->where('name', 'like', $term)));
        }
        if (!empty($a['jobsite'])) $q->where('jobsite_id', $this->jobsiteId($a['jobsite']) ?? 0);
        if (!empty($a['status'])) $q->where('status', $a['status']);
        if (!empty($a['tipe'])) $q->whereHas('assetType', fn ($t) => $t->where('name', 'like', '%' . SqlLike::escape($a['tipe']) . '%'));

        return $this->dataset('Hasil pencarian aset', $this->assetColumns(), $this->assetRows($q));
    }

    /** Kolom SQL untuk tiap dimensi ringkasan. */
    private const DIMENSIONS = [
        'status' => ['assets.status', 'Status'],
        'tipe' => ['asset_types.name', 'Tipe'],
        'merek' => ['asset_brands.name', 'Merek'],
        'seri' => ['asset_series.name', 'Seri'],
        'jobsite' => ['jobsites.code', 'Jobsite'],
        'departemen' => ['departments.name', 'Departemen'],
    ];

    /** Query aset + join referensi + filter umum (tipe, merek, seri, status, jobsite). */
    protected function assetBase(array $a)
    {
        $q = Asset::query()
            ->leftJoin('asset_types', 'asset_types.id', '=', 'assets.asset_type_id')
            ->leftJoin('asset_brands', 'asset_brands.id', '=', 'assets.asset_brand_id')
            ->leftJoin('asset_series', 'asset_series.id', '=', 'assets.asset_series_id')
            ->leftJoin('jobsites', 'jobsites.id', '=', 'assets.jobsite_id')
            ->leftJoin('departments', 'departments.id', '=', 'assets.department_id');
        foreach (['tipe' => 'asset_types.name', 'merek' => 'asset_brands.name', 'seri' => 'asset_series.name'] as $k => $col) {
            if (filled($a[$k] ?? null)) {
                $q->where($col, 'like', '%' . SqlLike::escape(trim($a[$k])) . '%');
            }
        }
        $status = trim((string) ($a['status'] ?? ''));
        if (strtolower($status) === 'aktif') {
            $q->whereNotIn('assets.status', ['Disposed', 'Scrap']);
        } elseif ($status !== '') {
            $q->where('assets.status', $status);
        }
        if (filled($a['jobsite'] ?? null)) {
            $q->where('jobsites.code', strtoupper(trim($a['jobsite'])));
        }

        return $q;
    }

    protected function assetSummary(array $a): array
    {
        $dims = array_values(array_unique(array_filter((array) ($a['kelompok'] ?? ['status']), fn ($d) => isset(self::DIMENSIONS[$d]))));
        $dims = array_slice($dims ?: ['status'], 0, 2);

        $q = $this->assetBase($a);
        $selects = [];
        foreach ($dims as $i => $d) {
            $col = self::DIMENSIONS[$d][0];
            $selects[] = "coalesce({$col}, '(kosong)') as d{$i}";
            $q->groupBy(DB::raw("coalesce({$col}, '(kosong)')"));
        }
        $rows = $q->selectRaw(implode(', ', $selects) . ', count(*) as total')->orderByDesc('total')->limit(self::MAX_ROWS)->get();
        $grand = (int) $rows->sum('total');

        $columns = [];
        foreach ($dims as $i => $d) {
            $columns["d{$i}"] = self::DIMENSIONS[$d][1];
        }
        $columns['total'] = 'Jumlah';
        $columns['pct'] = '% dari total';
        $data = $rows->map(function ($r) use ($dims, $grand) {
            $row = [];
            foreach ($dims as $i => $d) {
                $row["d{$i}"] = $r->{"d{$i}"};
            }
            $row['total'] = (int) $r->total;
            $row['pct'] = $grand ? round(100 * $r->total / $grand, 1) . '%' : '0%';

            return $row;
        })->all();

        $filters = collect(['tipe', 'merek', 'seri', 'status', 'jobsite'])->filter(fn ($k) => filled($a[$k] ?? null))->map(fn ($k) => "{$k}={$a[$k]}")->implode(', ');
        $title = 'Ringkasan aset per ' . implode(' & ', $dims) . ($filters ? " ({$filters})" : '');

        return $this->dataset($title, $columns, $data, ['total_keseluruhan' => $grand, 'filter' => $filters ?: 'tidak ada'], 120);
    }

    protected function reference(array $a): array
    {
        $types = Asset::query()->join('asset_types', 'asset_types.id', '=', 'assets.asset_type_id')
            ->selectRaw('asset_types.name as n, count(*) as c')->groupBy('asset_types.name')->orderByDesc('c')->pluck('c', 'n');
        $out = [
            'tipe_aset' => $types->map(fn ($c, $n) => "{$n} ({$c})")->values()->all(),
            'jobsite' => Jobsite::orderBy('code')->get(['code', 'name'])->map(fn ($j) => "{$j->code} = {$j->name}")->all(),
            'status_aset' => [
                'Free' => 'tersedia di gudang', 'Used' => 'dipakai user', 'Pre-Used' => 'diserahkan, menunggu konfirmasi penerima',
                'Borrowed' => 'dipinjam', 'Repair' => 'perbaikan', 'Pending Inspection' => 'menunggu inspeksi setelah dikembalikan',
                'Pending Mutation' => 'proses mutasi', 'Pre Disposed' => 'proses disposal', 'Disposed' => 'sudah dihapus', 'Missing' => 'hilang',
            ],
            'departemen' => \App\Models\Department::orderBy('name')->pluck('name')->all(),
        ];
        if (filled($a['tipe'] ?? null)) {
            $out['merek_untuk_' . $a['tipe']] = Asset::query()->join('asset_types', 'asset_types.id', '=', 'assets.asset_type_id')
                ->leftJoin('asset_brands', 'asset_brands.id', '=', 'assets.asset_brand_id')
                ->where('asset_types.name', 'like', '%' . SqlLike::escape($a['tipe']) . '%')
                ->selectRaw("coalesce(asset_brands.name, '(kosong)') as n, count(*) as c")->groupBy(DB::raw("coalesce(asset_brands.name, '(kosong)')"))
                ->orderByDesc('c')->get()->map(fn ($r) => "{$r->n} ({$r->c})")->all();
        }

        return $out;
    }

    protected function assetDetail(array $a): array
    {
        $no = trim((string) ($a['nomor'] ?? ''));
        $asset = Asset::with(['assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name,specifications', 'jobsite:id,code,name',
                'holder:id,name,nik,department_id', 'holder.department:id,name', 'departement:id,name'])
            ->where(fn ($q) => $q->where('asset_number', $no)->orWhere('old_asset_number', $no)->orWhere('serial_number', $no))
            ->first();
        if (!$asset) {
            return ['error' => "Aset '{$no}' tidak ditemukan."];
        }
        if (!\Illuminate\Support\Facades\Gate::forUser($this->user)->allows('view-asset', $asset)) {
            return ['error' => 'Anda tidak punya akses ke detail aset ini.'];
        }
        $history = $asset->transactions()->with(['fromUser:id,name', 'toUser:id,name', 'fromJobsite:id,code', 'toJobsite:id,code'])
            ->orderByDesc('asset_transactions.id')->limit(10)->get()
            ->map(fn ($t) => [
                'tanggal' => optional($t->transaction_date ?? $t->created_at)->format('Y-m-d'),
                'jenis' => $t->transaction_type, 'nomor' => $t->transaction_number, 'status' => $t->status,
                'dari' => $t->fromUser->name ?? ($t->fromJobsite->code ?? ''), 'ke' => $t->toUser->name ?? ($t->toJobsite->code ?? ''),
            ])->all();

        return [
            'no_aset' => $asset->asset_number, 'no_aset_lama' => $asset->old_asset_number, 'serial_number' => $asset->serial_number,
            'tipe' => $asset->assetType->name ?? null, 'merek' => $asset->assetBrand->name ?? null, 'seri' => $asset->assetSeries->name ?? null,
            'spesifikasi' => $asset->assetSeries->specifications ?? null,
            'status' => $asset->status, 'jobsite' => $asset->jobsite ? "{$asset->jobsite->code} - {$asset->jobsite->name}" : null,
            'lokasi' => $asset->current_location_detail, 'departemen' => $asset->departement->name ?? null,
            'pemegang' => $asset->holder ? "{$asset->holder->name} ({$asset->holder->nik}, " . ($asset->holder->department->name ?? '-') . ')' : null,
            'harga' => $asset->price ? 'Rp ' . number_format((float) $asset->price, 0, ',', '.') : null,
            'no_po' => $asset->po_number, 'no_pr' => $asset->pr_number,
            'terdaftar' => optional($asset->created_at)->format('Y-m-d'),
            'laporan_kerusakan_terbuka' => $asset->damageReports()->whereNotIn('damage_status', ['Closed', 'Cancelled'])->count(),
            'riwayat_terakhir' => $history,
        ];
    }

    /** Approver yang sedang ditunggu (tahap Pending terendah). */
    protected function currentPending($approvals): string
    {
        $p = collect($approvals)->where('status', 'Pending')->sortBy('step_order')->first();
        if (!$p) {
            return '-';
        }

        return trim(($p->approver_role_name ?: 'Tahap ' . $p->step_order) . ': ' . ($p->approver->name ?? 'approver belum diatur'));
    }

    protected function disposals(array $a): array
    {
        $open = \App\Livewire\AssetDisposals\ManageDisposed::OPEN_STATUSES;
        $status = $a['status'] ?? 'belum_divalidasi';
        $q = AssetTransaction::query()->where('transaction_type', 'DISPOSED')
            ->with(['approvals.approver:id,name', 'items.asset:id,asset_number,asset_type_id,jobsite_id', 'items.asset.assetType:id,name', 'items.asset.jobsite:id,code', 'creator:id,name', 'fromJobsite:id,code'])
            ->when($status === 'belum_divalidasi', fn ($w) => $w->whereIn('status', $open))
            ->when($status === 'selesai', fn ($w) => $w->whereIn('status', ['Completed', 'Disposed']))
            ->when($status === 'ditolak', fn ($w) => $w->where('status', 'Rejected'))
            ->when(($a['alasan'] ?? '') === 'hilang', fn ($w) => $w->where('notes', 'like', SqlLike::prefix('[Kehilangan]')))
            ->when(($a['alasan'] ?? '') === 'rusak', fn ($w) => $w->where(fn ($x) => $x->whereNull('notes')->orWhere('notes', 'not like', SqlLike::prefix('[Kehilangan]'))))
            ->when(filled($a['jobsite'] ?? null), function ($w) use ($a) {
                // Transaksi disposal sering tidak menyimpan jobsite asal: cocokkan juga dengan jobsite asetnya.
                $jid = $this->jobsiteId($a['jobsite']) ?? 0;
                $w->where(fn ($x) => $x->where('from_jobsite_id', $jid)->orWhereHas('items.asset', fn ($y) => $y->where('jobsite_id', $jid)));
            })
            ->when(!empty($a['minimal_hari']), fn ($w) => $w->where('created_at', '<=', now()->subDays((int) $a['minimal_hari'])));

        $rows = $q->orderBy('created_at')->limit(self::MAX_ROWS)->get()->map(function ($t) {
            $assets = $t->items->map(fn ($i) => ($i->asset->asset_number ?? '?') . ' (' . ($i->asset->assetType->name ?? '-') . ')');

            return [
                'number' => $t->transaction_number,
                'date' => optional($t->created_at)->format('Y-m-d'),
                'age' => (int) optional($t->created_at)->diffInDays(now()),
                'reason' => str_starts_with((string) $t->notes, '[Kehilangan]') ? 'Hilang' : 'Rusak/lainnya',
                'assets' => $assets->take(3)->implode(', ') . ($assets->count() > 3 ? ' +' . ($assets->count() - 3) : ''),
                'jobsite' => $t->fromJobsite->code ?? $t->items->map(fn ($i) => $i->asset->jobsite->code ?? null)->filter()->unique()->implode(', '),
                'by' => $t->creator->name ?? '',
                'waiting' => $this->currentPending($t->approvals),
                'status' => $t->status,
            ];
        })->all();

        $byWaiting = collect($rows)->groupBy('waiting')->map->count()->sortDesc()->take(10)->all();

        return $this->dataset('Pengajuan disposal (' . str_replace('_', ' ', $status) . ')',
            ['number' => 'No. Pengajuan', 'date' => 'Tanggal', 'age' => 'Umur (hari)', 'reason' => 'Alasan', 'assets' => 'Aset',
                'jobsite' => 'Jobsite', 'by' => 'Diajukan oleh', 'waiting' => 'Menunggu', 'status' => 'Status'],
            $rows,
            ['ringkasan_per_approver_yang_ditunggu' => $byWaiting, 'rata_rata_umur_hari' => count($rows) ? round(collect($rows)->avg('age'), 1) : 0],
        );
    }

    protected function pendingApprovals(array $a): array
    {
        $jenis = $a['jenis'] ?? 'semua';
        $items = collect();
        // Hanya tahap yang sedang gilirannya (step Pending terendah per pengajuan/transaksi):
        // tahap berikutnya belum bisa bertindak, jadi tidak dihitung sebagai "menahan".
        $currentOnly = fn ($rows, string $fk) => $rows->groupBy($fk)->map(fn ($g) => $g->sortBy('step_order')->first())->values();
        if ($jenis !== 'transaksi') {
            $rows = AssetRequestApproval::with(['approver:id,name', 'assetRequest:id,request_number,status'])
                ->where('status', 'Pending')->whereHas('assetRequest', fn ($q) => $q->whereNotIn('status', ['Completed', 'Rejected', 'Close', 'Cancelled']))
                ->get();
            foreach ($currentOnly($rows, 'asset_request_id') as $ap) {
                $items->push(['kind' => 'Permintaan aset', 'number' => $ap->assetRequest->request_number ?? '-', 'approver' => $ap->approver->name ?? '(belum diatur)',
                    'role' => $ap->approver_role_name, 'since' => $ap->created_at]);
            }
        }
        if ($jenis !== 'permintaan_aset') {
            $rows = AssetTransactionApproval::with(['approver:id,name', 'transaction:id,transaction_number,transaction_type,status'])
                ->where('status', 'Pending')->whereHas('transaction', fn ($q) => $q->whereNotIn('status', \App\Support\TransactionApprovalRepair::CLOSED_STATUSES))
                ->get();
            foreach ($currentOnly($rows, 'asset_transaction_id') as $ap) {
                $items->push(['kind' => 'Transaksi ' . ($ap->transaction->transaction_type ?? ''), 'number' => $ap->transaction->transaction_number ?? '-',
                    'approver' => $ap->approver->name ?? '(belum diatur)', 'role' => $ap->approver_role_name, 'since' => $ap->created_at]);
            }
        }

        if (($a['tampilan'] ?? 'per_approver') === 'daftar') {
            $rows = $items->sortBy('since')->map(fn ($i) => ['kind' => $i['kind'], 'number' => $i['number'], 'approver' => $i['approver'], 'role' => $i['role'],
                'age' => (int) optional($i['since'])->diffInDays(now())])->values()->all();

            return $this->dataset('Persetujuan tertunda', ['kind' => 'Jenis', 'number' => 'Nomor', 'approver' => 'Approver', 'role' => 'Sebagai', 'age' => 'Menunggu (hari)'], $rows);
        }

        $rows = $items->groupBy('approver')->map(fn ($g, $name) => [
            'approver' => $name, 'total' => $g->count(),
            'oldest' => (int) optional($g->min('since'))->diffInDays(now()),
            'kinds' => $g->groupBy('kind')->map->count()->map(fn ($c, $k) => "{$k}: {$c}")->implode('; '),
        ])->sortByDesc('total')->values()->all();

        return $this->dataset('Persetujuan tertunda per approver', ['approver' => 'Approver', 'total' => 'Jumlah', 'oldest' => 'Terlama (hari)', 'kinds' => 'Rincian'],
            $rows, ['total_tertunda' => $items->count()], 60);
    }

    protected function allRequests(array $a): array
    {
        $status = trim((string) ($a['status'] ?? ''));
        $q = AssetRequest::query()->with(['requester:id,name', 'jobsite:id,code', 'approvals.approver:id,name'])->withCount('items')
            ->when(strtolower($status) === 'berjalan', fn ($w) => $w->whereNotIn('status', ['Completed', 'Rejected', 'Close', 'Cancelled']))
            ->when($status !== '' && strtolower($status) !== 'berjalan', fn ($w) => $w->where('status', $status))
            ->when(filled($a['jobsite'] ?? null), fn ($w) => $w->where('jobsite_id', $this->jobsiteId($a['jobsite']) ?? 0))
            ->when(filled($a['dari_tanggal'] ?? null), fn ($w) => $w->whereDate('request_date', '>=', $a['dari_tanggal']))
            ->when(filled($a['sampai_tanggal'] ?? null), fn ($w) => $w->whereDate('request_date', '<=', $a['sampai_tanggal']));

        $rows = $q->orderByDesc('id')->limit(self::MAX_ROWS)->get()->map(fn ($r) => [
            'number' => $r->request_number, 'date' => optional($r->request_date)->format('Y-m-d'), 'requester' => $r->requester->name ?? '',
            'jobsite' => $r->jobsite->code ?? '', 'items' => $r->items_count, 'status' => $r->status,
            'waiting' => $this->currentPending($r->approvals), 'age' => (int) optional($r->created_at)->diffInDays(now()),
        ])->all();

        return $this->dataset('Permintaan aset' . ($status ? " ({$status})" : ''),
            ['number' => 'No. Pengajuan', 'date' => 'Tanggal', 'requester' => 'Pengaju', 'jobsite' => 'Jobsite', 'items' => 'Item', 'status' => 'Status', 'waiting' => 'Menunggu', 'age' => 'Umur (hari)'],
            $rows, ['ringkasan_status' => collect($rows)->groupBy('status')->map->count()->all()]);
    }

    protected function damages(array $a): array
    {
        $status = trim((string) ($a['status'] ?? ''));
        $q = \App\Models\AssetDamageReport::query()
            ->with(['asset:id,asset_number,asset_type_id,jobsite_id', 'asset.assetType:id,name', 'asset.jobsite:id,code', 'reporter:id,name'])
            ->when(strtolower($status) === 'terbuka', fn ($w) => $w->whereNotIn('damage_status', ['Closed', 'Cancelled', 'Beyond Economic Repair']))
            ->when($status !== '' && strtolower($status) !== 'terbuka', fn ($w) => $w->where('damage_status', $status))
            ->when(filled($a['jobsite'] ?? null), fn ($w) => $w->whereHas('asset', fn ($x) => $x->where('jobsite_id', $this->jobsiteId($a['jobsite']) ?? 0)))
            ->when(filled($a['tipe'] ?? null), fn ($w) => $w->whereHas('asset.assetType', fn ($x) => $x->where('name', 'like', '%' . SqlLike::escape($a['tipe']) . '%')));

        $rows = $q->orderByDesc('id')->limit(self::MAX_ROWS)->get()->map(fn ($d) => [
            'date' => optional($d->report_date)->format('Y-m-d') ?? (string) $d->report_date,
            'asset' => $d->asset->asset_number ?? '-', 'type' => $d->asset->assetType->name ?? '', 'jobsite' => $d->asset->jobsite->code ?? '',
            'desc' => Str::limit((string) $d->damage_description, 90), 'status' => $d->damage_status, 'by' => $d->reporter->name ?? '',
            'cost' => $d->repair_cost ? (float) $d->repair_cost : null,
        ])->all();

        return $this->dataset('Laporan kerusakan' . ($status ? " ({$status})" : ''),
            ['date' => 'Tanggal', 'asset' => 'No. Aset', 'type' => 'Tipe', 'jobsite' => 'Jobsite', 'desc' => 'Kerusakan', 'status' => 'Status', 'by' => 'Pelapor', 'cost' => 'Biaya'],
            $rows, ['ringkasan_status' => collect($rows)->groupBy('status')->map->count()->all()]);
    }

    protected function opnames(array $a): array
    {
        $q = \App\Models\StockOpname::query()->with('jobsite:id,code')
            ->withCount([
                'items as found' => fn ($w) => $w->where('result', 'found'),
                'items as other_site' => fn ($w) => $w->where('result', 'other_site'),
                'items as not_registered' => fn ($w) => $w->where('result', 'not_registered'),
                'items as broken' => fn ($w) => $w->where('condition', 'rusak'),
            ])
            ->when(filled($a['jobsite'] ?? null), fn ($w) => $w->where('jobsite_id', $this->jobsiteId($a['jobsite']) ?? 0))
            ->when(!empty($a['hanya_terbuka']), fn ($w) => $w->where('status', 'open'));

        $rows = $q->orderByDesc('id')->limit(200)->get()->map(fn ($o) => [
            'code' => $o->code, 'jobsite' => $o->jobsite->code ?? '', 'status' => $o->status === 'open' ? 'Berjalan' : 'Ditutup',
            'started' => optional($o->started_at)->format('Y-m-d'), 'expected' => (int) $o->expected_count, 'found' => $o->found,
            'missing' => max(0, (int) $o->expected_count - $o->found), 'other' => $o->other_site, 'unregistered' => $o->not_registered, 'broken' => $o->broken,
            'progress' => $o->expected_count ? round(100 * $o->found / $o->expected_count, 1) . '%' : '-',
        ])->all();

        return $this->dataset('Stock opname',
            ['code' => 'Kode', 'jobsite' => 'Jobsite', 'status' => 'Status', 'started' => 'Mulai', 'expected' => 'Seharusnya', 'found' => 'Ditemukan',
                'missing' => 'Belum ditemukan', 'other' => 'Dari site lain', 'unregistered' => 'Tidak terdaftar', 'broken' => 'Rusak', 'progress' => 'Progres'],
            $rows);
    }

    protected function holders(array $a): array
    {
        $agg = AssetHolding::query()->when(!empty($a['jobsite']), fn ($q) => $q->where('jobsite_id', $this->jobsiteId($a['jobsite']) ?? 0))
            ->selectRaw("holder_user_id, count(*) as total, sum(case when status = 'Pre-Used' then 1 else 0 end) as pending")->groupBy('holder_user_id');
        $q = User::query()->joinSub($agg, 'h', 'h.holder_user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->select('users.name', 'users.nik', 'departments.name as dept', 'h.total', 'h.pending')
            ->when(!empty($a['minimal']), fn ($w) => $w->where('h.total', '>=', (int) $a['minimal']));
        if ($kw = trim((string) ($a['kata_kunci'] ?? ''))) {
            $term = '%' . SqlLike::escape($kw) . '%';
            $q->where(fn ($w) => $w->where('users.name', 'like', $term)->orWhere('users.nik', 'like', $term));
        }
        $rows = $q->orderByDesc('h.total')->limit(self::MAX_ROWS)->get()
            ->map(fn ($r) => ['name' => $r->name, 'nik' => $r->nik, 'dept' => $r->dept, 'total' => (int) $r->total, 'pending' => (int) $r->pending])->all();

        return $this->dataset('Pemegang aset', ['name' => 'Nama', 'nik' => 'NIK', 'dept' => 'Departemen', 'total' => 'Jumlah aset', 'pending' => 'Belum konfirmasi'], $rows);
    }

    protected function assetsOfUser(array $a): array
    {
        $kw = trim((string) ($a['nik_atau_nama'] ?? ''));
        $users = User::where('nik', $kw)->get(['id', 'name', 'nik']);
        if ($users->isEmpty()) {
            $users = User::where('name', 'like', '%' . SqlLike::escape($kw) . '%')->limit(6)->get(['id', 'name', 'nik']);
        }
        if ($users->isEmpty()) {
            return ['error' => "User '{$kw}' tidak ditemukan."];
        }
        if ($users->count() > 1) {
            return ['perlu_dipilih' => 'Ada beberapa user yang cocok, minta user memilih NIK.', 'kandidat' => $users->map(fn ($u) => "{$u->name} ({$u->nik})")->all()];
        }
        $u = $users->first();

        return $this->dataset("Aset dipegang {$u->name} ({$u->nik})", $this->assetColumns(), $this->assetRows(AssetHolding::query()->where('holder_user_id', $u->id)));
    }

    protected function loans(array $a): array
    {
        $rows = AssetLoan::activeAssetsQuery()->with(['holder:id,name,nik', 'assetType:id,name', 'jobsite:id,code'])->get()
            ->map(function ($asset) {
                $loan = AssetLoan::activeLoan($asset);
                $due = $loan ? (AssetLoan::meta($loan, $asset->id)['due_date'] ?? null) : null;
                $late = $due && now()->startOfDay()->gt(Carbon::parse($due));

                return ['asset_number' => $asset->asset_number, 'type' => $asset->assetType->name ?? '', 'borrower' => $asset->holder ? "{$asset->holder->name} ({$asset->holder->nik})" : '',
                    'jobsite' => $asset->jobsite->code ?? '', 'due' => $due, 'late' => $late ? 'Terlambat' : 'Tepat waktu'];
            })
            ->when(!empty($a['hanya_terlambat']), fn ($c) => $c->where('late', 'Terlambat'))
            ->sortBy('due')->values()->all();

        return $this->dataset('Peminjaman aktif', ['asset_number' => 'No. Aset', 'type' => 'Tipe', 'borrower' => 'Peminjam', 'jobsite' => 'Jobsite', 'due' => 'Harus kembali', 'late' => 'Keterangan'], $rows);
    }

    protected function transactions(array $a): array
    {
        $q = AssetTransaction::query()->with(['fromUser:id,name', 'toUser:id,name', 'fromJobsite:id,code', 'toJobsite:id,code'])->withCount('items')
            ->when(!empty($a['jenis']), fn ($w) => $w->where('transaction_type', $a['jenis']))
            ->when(!empty($a['status']), fn ($w) => $w->where('status', $a['status']))
            ->when(!empty($a['dari_tanggal']), fn ($w) => $w->whereDate('created_at', '>=', $a['dari_tanggal']))
            ->when(!empty($a['sampai_tanggal']), fn ($w) => $w->whereDate('created_at', '<=', $a['sampai_tanggal']));
        $rows = $q->orderByDesc('id')->limit(self::MAX_ROWS)->get()->map(fn ($t) => [
            'number' => $t->transaction_number, 'type' => $t->transaction_type, 'date' => optional($t->transaction_date ?? $t->created_at)->format('Y-m-d'),
            'from' => $t->fromUser->name ?? ($t->fromJobsite->code ?? ''), 'to' => $t->toUser->name ?? ($t->toJobsite->code ?? ''),
            'items' => $t->items_count, 'status' => $t->status,
        ])->all();

        return $this->dataset('Transaksi aset', ['number' => 'No. Transaksi', 'type' => 'Jenis', 'date' => 'Tanggal', 'from' => 'Dari', 'to' => 'Ke', 'items' => 'Jumlah aset', 'status' => 'Status'], $rows);
    }

    protected function myApprovals(): array
    {
        $uid = $this->user->id;
        $rows = [];
        foreach (AssetRequestApproval::with('assetRequest:id,request_number,requester_user_id', 'assetRequest.requester:id,name')
            ->where('approver_user_id', $uid)->where('status', 'Pending')->limit(200)->get() as $ap) {
            $rows[] = ['kind' => 'Permintaan aset', 'number' => $ap->assetRequest->request_number ?? '-', 'from' => $ap->assetRequest->requester->name ?? '', 'step' => $ap->approver_role_name ?? ''];
        }
        foreach (AssetTransactionApproval::with('transaction:id,transaction_number,transaction_type,created_by_user_id', 'transaction.creator:id,name')
            ->where('approver_user_id', $uid)->where('status', 'Pending')->limit(200)->get() as $ap) {
            $rows[] = ['kind' => 'Transaksi ' . ($ap->transaction->transaction_type ?? ''), 'number' => $ap->transaction->transaction_number ?? '-', 'from' => $ap->transaction->creator->name ?? '', 'step' => $ap->approver_role_name ?? ''];
        }

        return $this->dataset('Menunggu persetujuan saya', ['kind' => 'Jenis', 'number' => 'Nomor', 'from' => 'Diajukan oleh', 'step' => 'Sebagai'], $rows);
    }

    protected function myRequests(array $a): array
    {
        $rows = AssetRequest::where('requester_user_id', $this->user->id)
            ->when(!empty($a['status']), fn ($q) => $q->where('status', $a['status']))
            ->orderByDesc('id')->limit(200)->get(['request_number', 'request_date', 'needed_date', 'status', 'purpose'])
            ->map(fn ($r) => ['number' => $r->request_number, 'date' => optional($r->request_date)->format('Y-m-d') ?? (string) $r->request_date,
                'status' => $r->status, 'purpose' => Str::limit((string) $r->purpose, 80)])->all();

        return $this->dataset('Permintaan aset saya', ['number' => 'No. Pengajuan', 'date' => 'Tanggal', 'status' => 'Status', 'purpose' => 'Keperluan'], $rows);
    }
}
