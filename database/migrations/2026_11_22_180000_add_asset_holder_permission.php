<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Permission menu "Pemegang Aset"; diberikan ke role yang punya "Master Asset". */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'Pemegang Aset', 'guard_name' => 'web']);
        Role::whereHas('permissions', fn ($q) => $q->where('name', 'Master Asset'))->get()
            ->each(fn (Role $role) => $role->givePermissionTo('Pemegang Aset'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'Pemegang Aset')->get()->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
