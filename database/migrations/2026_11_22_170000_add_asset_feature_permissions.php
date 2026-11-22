<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pisahkan fitur aset dari "Master Asset" menjadi permission sendiri.
 * Hanya menambah baris permission; role yang punya "Master Asset" otomatis
 * mendapat semuanya sehingga akses yang ada tidak berubah.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['Stock Opname', 'Cetak Label', 'Peminjaman', 'Kerusakan Aset', 'QC Aset'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::whereHas('permissions', fn ($q) => $q->where('name', 'Master Asset'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo(self::PERMISSIONS));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', self::PERMISSIONS)->get()->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
