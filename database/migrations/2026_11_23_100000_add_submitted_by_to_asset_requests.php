<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pengajuan aset atas nama orang lain.
 *  - asset_requests.submitted_by_user_id: siapa yang mengisi form (null = pemohon sendiri, data lama).
 *    requester_user_id tetap = penerima aset, jadi BAST, pemegang aset & workflow mengikuti penerima.
 *  - Permission "Ajukan Aset untuk Orang Lain": boleh mengajukan untuk karyawan satu departemen.
 *    Tidak diberikan otomatis ke role mana pun; atur lewat halaman permission.
 */
return new class extends Migration
{
    public const PERMISSION = 'Ajukan Aset untuk Orang Lain';

    public function up(): void
    {
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->foreignId('submitted_by_user_id')->nullable()->after('requester_user_id')->constrained('users');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by_user_id');
        });

        Permission::where('name', self::PERMISSION)->get()->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
