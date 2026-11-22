<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * Menyajikan file dari disk "public" hanya untuk user yang sudah login.
 *
 * Dokumen (BAST, mutasi, BA kerusakan, lampiran) sebelumnya bisa diunduh siapa pun
 * lewat symlink public/storage. URL tetap sama (/storage/...), jadi path di database tidak berubah.
 * Di server, hapus symlink public/storage agar semua request lewat route ini.
 */
class StorageFileController extends Controller
{
    public function show(string $path)
    {
        abort_if(str_contains($path, '..') || str_contains($path, "\0"), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
