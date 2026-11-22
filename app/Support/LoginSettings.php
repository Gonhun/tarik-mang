<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Pengaturan tampilan halaman login. Disimpan di file JSON (tanpa tabel),
 * gambar disimpan di public/uploads/login agar bisa dibuka tanpa login.
 */
class LoginSettings
{
    public const DIR = 'uploads/login';

    public const DEFAULTS = [
        'background' => null,          // path relatif dari public/, mis. uploads/login/bg-xxx.jpg
        'logo' => null,                // null = logo BSS bawaan
        'overlay' => 55,               // 0–90: kegelapan lapisan warna di atas gambar
        'headline' => 'Aset IT tercatat rapi, dari gudang sampai ke tangan user.',
        'points' => [
            'Inventaris, label QR, dan stock opname',
            'Serah terima, pengembalian, mutasi, dan peminjaman',
            'Persetujuan berjenjang sesuai workflow',
        ],
        'show_character' => true,
        'updated_by' => null,
        'updated_at' => null,
    ];

    public const DEFAULT_LOGO = 'assets/img/LogoBSS.png';

    protected static function file(): string
    {
        return storage_path('app/settings/login.json');
    }

    public static function all(): array
    {
        return Cache::rememberForever('login-settings', function () {
            $data = File::exists(self::file()) ? json_decode(File::get(self::file()), true) : [];

            return array_replace(self::DEFAULTS, is_array($data) ? $data : []);
        });
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function save(array $values): void
    {
        $data = array_replace(self::all(), array_intersect_key($values, self::DEFAULTS));
        File::ensureDirectoryExists(dirname(self::file()));
        File::put(self::file(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        Cache::forget('login-settings');
    }

    /** Simpan gambar upload ke public/uploads/login, kembalikan path relatif. */
    public static function storeImage(UploadedFile $file, string $prefix): string
    {
        $dir = public_path(self::DIR);
        File::ensureDirectoryExists($dir);
        $name = $prefix . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . strtolower($file->extension() ?: 'jpg');
        // Salin isi file: file sementara Livewire bukan upload HTTP langsung, jadi move() (move_uploaded_file) selalu gagal.
        File::put($dir . '/' . $name, $file->get());

        return self::DIR . '/' . $name;
    }

    /** Hapus file lama (hanya yang ada di folder upload login). */
    public static function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, self::DIR . '/') && !str_contains($path, '..')) {
            File::delete(public_path($path));
        }
    }

    public static function backgroundUrl(): ?string
    {
        $bg = self::get('background');

        return $bg && File::exists(public_path($bg)) ? asset($bg) : null;
    }

    public static function logoUrl(): string
    {
        $logo = self::get('logo');

        return asset($logo && File::exists(public_path($logo)) ? $logo : self::DEFAULT_LOGO);
    }
}
