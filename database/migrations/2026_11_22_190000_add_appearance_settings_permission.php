<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Permission "Pengaturan Tampilan" (tampilan halaman login); diberikan ke role Administrator. */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'Pengaturan Tampilan', 'guard_name' => 'web']);
        Role::where('name', 'Administrator')->get()->each(fn (Role $role) => $role->givePermissionTo('Pengaturan Tampilan'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'Pengaturan Tampilan')->get()->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
