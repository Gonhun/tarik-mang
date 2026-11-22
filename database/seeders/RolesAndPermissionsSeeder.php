<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Jobsite;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat Permissions (Izin Aksi)
        Permission::firstOrCreate(['name' => 'Manajement IT']);
        Permission::firstOrCreate(['name' => 'Manajement Request']);
        Permission::firstOrCreate(['name' => 'Master Asset']);
        Permission::firstOrCreate(['name' => 'Mutasi']);
        Permission::firstOrCreate(['name' => 'Add Entitas']);
        Permission::firstOrCreate(['name' => 'Add Jobsites']);
        Permission::firstOrCreate(['name' => 'Add Permissions']);
        Permission::firstOrCreate(['name' => 'Add User']);
        Permission::firstOrCreate(['name' => 'Fortigate']);
        Permission::firstOrCreate(['name' => 'Logs']);
        Permission::firstOrCreate(['name' => 'Add Cost Center']);
        Permission::firstOrCreate(['name' => 'Add Brands']);
        Permission::firstOrCreate(['name' => 'Add Type']);
        Permission::firstOrCreate(['name' => 'Whatsapp']);
        Permission::firstOrCreate(['name' => 'Add Dept']);
        Permission::firstOrCreate(['name' => 'Add Jabatan']);
        Permission::firstOrCreate(['name' => 'Add Workflow']);
        Permission::firstOrCreate(['name' => 'Add Series']);
        Permission::firstOrCreate(['name' => 'Show Dashboard']);
        Permission::firstOrCreate(['name' => 'Edit Inventaris']);
        
       
        // ... tambahkan semua izin approval lainnya

        // Buat Roles dan berikan permissions
        $roleAdminIT = Role::firstOrCreate(['name' => 'Administrator IT']);
        $roleAdminIT->givePermissionTo(Permission::all()->pluck('name')->toArray());

        $roleManager = Role::firstOrCreate(['name' => 'Administrator CC']);
        $roleManager->givePermissionTo([
            'Add Cost Center',
            'Manajement Request',    // Asumsi manager adalah PM
            'Mutasi',
            
        ]);
        $roleManager = Role::firstOrCreate(['name' => 'IT']);
        $roleManager->givePermissionTo([
            'Show Dashboard',
            'Manajement Request',  
            'Mutasi',
            'Master Asset'
        ]);
        // Karyawan tidak butuh permission khusus: permintaan aset & aset saya terbuka untuk semua user.
        Role::firstOrCreate(['name' => 'Karyawan']);

        $roleSuperAdmin = Role::firstOrCreate(['name' => 'Administrator']);
        $roleSuperAdmin->givePermissionTo(Permission::all());

        $deptIT = Department::where('code', 'IT')->first();
        $jobsiteHO = Jobsite::where('code', 'JKT')->first();
        $posITAdmin = Position::where('name', 'Administrator IT')->first();
        $adminUser = User::updateOrCreate(
            ['nik' => 'admin'],
            [
                'name' => 'Administrator',
                'nik' => 'admin',
                'password' => Hash::make('maximize855'),
                'whatsapp' => '6281200000003',
                'department_id' => $deptIT->id,
                'jobsite_id' => $jobsiteHO->id,
                'position_id' => $posITAdmin->id,
            ]
        );

        // Menetapkan role "Administrator" ke user yang baru dibuat
        $adminUser->assignRole($roleSuperAdmin);
    }
}
