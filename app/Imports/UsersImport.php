<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Jobsite;
use App\Models\Position;
use App\Models\PositionLevel;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;

class UsersImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        try {
            $jobsite = Jobsite::where('code', $row[0])->first();
            $dept = Department::where('code', $row[1])->first();

            // Validasi data yang diperlukan
            if (!$jobsite || !$dept) {
                Log::warning('UsersImport: Jobsite atau Department tidak ditemukan.', [
                    'jobsite_code' => $row[0] ?? null,
                    'department_code' => $row[1] ?? null,
                    'row' => $row
                ]);
                return null;
            }

            $position = Position::where('name', $row[2])
                ->where('department_id', $dept->id)
                ->first();

            $positionlevel = PositionLevel::where('name', $row[6])->first();

            // Pastikan semua relasi ditemukan sebelum membuat User
            if (!$position || !$positionlevel) {
                Log::warning('UsersImport: Position atau PositionLevel tidak ditemukan.', [
                    'position_name' => $row[2] ?? null,
                    'department_id' => $dept->id,
                    'position_level_name' => $row[6] ?? null,
                    'row' => $row
                ]);
                return null;
            }

            // Pastikan NIK adalah string agar tidak terjadi konversi tipe data
            $nik = (string) $row[3];

            // Update existing user by NIK, atau insert jika belum ada
            $user = User::updateOrCreate(
                ['nik' => $nik],
                [
                    'name' => $row[4],
                    'whatsapp' => $row[5],
                    'department_id' => $dept->id,
                    'jobsite_id' => $jobsite->id,
                    'position_id' => $position->id,
                    'position_level_id' => $positionlevel->id,
                ]
            );

            Log::info('UsersImport: User diupdate/dibuat.', [
                'nik' => $nik,
                'user_id' => $user->id ?? null,
                'row' => $row
            ]);

            // Tambahkan role 'karyawan' jika user belum punya role apapun
            if ($user && ($user->roles()->count() == 0)) {
                $role = \Spatie\Permission\Models\Role::where('name', 'karyawan')->first();
                if ($role) {
                    $user->assignRole($role);
                    Log::info('UsersImport: Role "karyawan" ditambahkan ke user.', [
                        'user_id' => $user->id,
                        'role_id' => $role->id
                    ]);
                } else {
                    Log::warning('UsersImport: Role "karyawan" tidak ditemukan.', [
                        'user_id' => $user->id
                    ]);
                }
            }

            return $user;
        } catch (\Throwable $e) {
            Log::error('UsersImport: Exception saat import user.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'row' => $row
            ]);
            return null;
        }
    }
}
