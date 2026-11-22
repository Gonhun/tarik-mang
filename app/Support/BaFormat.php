<?php

namespace App\Support;

use Carbon\Carbon;

/** Format teks untuk template PDF berita acara. */
class BaFormat
{
    public static function date($date, string $format = 'D MMMM YYYY'): string
    {
        return $date ? Carbon::parse($date)->locale('id')->isoFormat($format) : '-';
    }

    /** Badge status berbahasa Indonesia (HTML aman). */
    public static function status(?string $status): string
    {
        [$label, $tone] = match ($status) {
            'Completed', 'Approved' => ['Selesai', 'success'],
            'Rejected' => ['Ditolak', 'danger'],
            'Cancelled' => ['Dibatalkan', 'muted'],
            'Pending Approval' => ['Menunggu persetujuan', 'warning'],
            'Pending Mutation' => ['Menunggu mutasi', 'warning'],
            'Pending Inspection' => ['Menunggu inspeksi', 'warning'],
            'Pending Release' => ['Menunggu rilis', 'warning'],
            'Pending', 'Draft' => ['Menunggu', 'warning'],
            default => [$status ?: '-', 'muted'],
        };

        return '<span class="pill pill-' . $tone . '">' . e($label) . '</span>';
    }

    /** Kalimat pembuka "Pada hari ini ..." (HTML aman). */
    public static function opening($date, ?string $place): string
    {
        $c = Carbon::parse($date)->locale('id');

        return 'Pada hari ini, <b>' . e($c->isoFormat('dddd')) . '</b>, tanggal <b>' . e($c->isoFormat('D MMMM YYYY'))
            . '</b>, bertempat di <b>' . e($place ?: 'lokasi tidak disebutkan') . '</b>,';
    }
}
