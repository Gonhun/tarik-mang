<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Cek apakah data master masih dipakai sebelum dihapus (data master dihapus permanen,
 * dan Entitas memakai soft delete sehingga database tidak menolak penghapusan).
 */
class MasterUsage
{
    /** [jenis => [[tabel, kolom, label], ...]] */
    private const USAGE = [
        'department' => [
            ['users', 'department_id', 'pengguna'],
            ['positions', 'department_id', 'jabatan'],
            ['cost_centers', 'department_id', 'cost center'],
            ['assets', 'department_id', 'aset'],
            ['approval_workflow_steps', 'department_id', 'tahap workflow'],
        ],
        'jobsite' => [
            ['users', 'jobsite_id', 'pengguna'],
            ['assets', 'jobsite_id', 'aset'],
            ['cost_centers', 'jobsite_id', 'cost center'],
            ['approval_workflow_assignments', 'jobsite_id', 'aturan validasi'],
        ],
        'entity' => [
            ['assets', 'entity_id', 'aset'],
        ],
        'position' => [
            ['users', 'position_id', 'pengguna'],
            ['approval_workflow_assignments', 'position_id', 'aturan validasi'],
            ['position_asset_standards', 'position_id', 'standar aset'],
        ],
        'position_level' => [
            ['users', 'position_level_id', 'pengguna'],
            ['approval_workflow_steps', 'approver_position_level_id', 'tahap workflow'],
        ],
    ];

    /**
     * @return string[] mis. ["12 pengguna", "340 aset"]; kosong = aman dihapus.
     */
    public static function reasons(string $type, int $id): array
    {
        $reasons = [];
        foreach (self::USAGE[$type] ?? [] as [$table, $column, $label]) {
            try {
                $q = DB::table($table)->where($column, $id);
                if (in_array('deleted_at', \Illuminate\Support\Facades\Schema::getColumnListing($table), true)) {
                    $q->whereNull('deleted_at');
                }
                if (($n = $q->count()) > 0) {
                    $reasons[] = "{$n} {$label}";
                }
            } catch (\Throwable $e) {
                // Tabel/kolom tidak ada di instalasi ini: abaikan.
            }
        }

        return $reasons;
    }

    public static function message(string $name, array $reasons): string
    {
        return "{$name} masih dipakai oleh " . implode(', ', $reasons) . '. Pindahkan data tersebut dulu sebelum menghapus.';
    }
}
