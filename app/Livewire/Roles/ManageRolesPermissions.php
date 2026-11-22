<?php

namespace App\Livewire\Roles;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Manajemen role & permission:
 *  - Matriks akses: centang permission per role (langsung tersimpan).
 *  - Role: tambah, ganti nama, hapus (bila tidak dipakai user).
 *  - Permission: katalog dengan keterangan; permission sistem dikunci.
 */
#[Title('Role & Permission')]
class ManageRolesPermissions extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'matrix';           // matrix | roles | permissions
    #[Url(as: 'q')]
    public string $search = '';

    // Form role
    public bool $isRoleModalOpen = false;
    #[Locked] public ?int $role_id = null;
    public string $role_name = '';
    public ?int $copyFromRole = null;

    // Form permission
    public bool $isPermissionModalOpen = false;
    #[Locked] public ?int $permission_id = null;
    public string $permission_name = '';

    // ============ Aturan & helper ============

    protected function catalog(): array
    {
        return config('permissions.groups', []);
    }

    /** Nama permission yang dipakai di kode (dikunci). */
    protected function systemPermissions(): array
    {
        return collect($this->catalog())->flatMap(fn ($items) => array_keys($items))->all();
    }

    protected function isSystem(string $name): bool
    {
        return in_array($name, $this->systemPermissions(), true);
    }

    protected function protectedRoles(): array
    {
        return config('permissions.protected_roles', ['Administrator']);
    }

    protected function actorIsAdmin(): bool
    {
        return Auth::user()->hasRole($this->protectedRoles());
    }

    /** Alasan role tidak boleh diubah oleh user ini, atau null bila boleh. */
    protected function roleLockReason(Role $role): ?string
    {
        if (in_array($role->name, $this->protectedRoles(), true) && !$this->actorIsAdmin()) {
            return "Role {$role->name} hanya bisa diubah oleh Administrator.";
        }
        if (!$this->actorIsAdmin() && Auth::user()->hasRole($role->name)) {
            return "Anda tidak bisa mengubah izin role Anda sendiri ({$role->name}).";
        }

        return null;
    }

    protected function fail(string $text): void
    {
        $this->dispatch('swal:error', ['title' => 'Tidak bisa', 'text' => $text]);
    }

    protected function flushCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['matrix', 'roles', 'permissions'], true)) {
            $this->tab = $tab;
        }
    }

    // ============ Matriks ============

    /** Beri / cabut satu permission dari role. */
    public function togglePermission(int $roleId, int $permissionId): void
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);
        if ($reason = $this->roleLockReason($role)) {
            $this->fail($reason);
            return;
        }

        if ($role->hasPermissionTo($permission)) {
            if (in_array($role->name, $this->protectedRoles(), true) && in_array($permission->name, config('permissions.locked_on_protected', []), true)) {
                $this->fail("Izin {$permission->name} tidak boleh dicabut dari {$role->name}, agar tidak ada yang terkunci dari halaman ini.");
                return;
            }
            $role->revokePermissionTo($permission);
        } else {
            $role->givePermissionTo($permission);
        }
        $this->flushCache();
    }

    /** Beri / cabut semua permission dalam satu kelompok untuk satu role. */
    public function toggleGroup(int $roleId, string $group): void
    {
        $role = Role::findOrFail($roleId);
        if ($reason = $this->roleLockReason($role)) {
            $this->fail($reason);
            return;
        }
        $names = $this->groupedPermissions()->get($group, collect())->pluck('name');
        $perms = Permission::whereIn('name', $names)->get();
        if ($perms->isEmpty()) {
            return;
        }

        $hasAll = $perms->every(fn ($p) => $role->hasPermissionTo($p));
        if ($hasAll) {
            $locked = in_array($role->name, $this->protectedRoles(), true) ? config('permissions.locked_on_protected', []) : [];
            $role->revokePermissionTo($perms->reject(fn ($p) => in_array($p->name, $locked, true)));
        } else {
            $role->givePermissionTo($perms);
        }
        $this->flushCache();
    }

    // ============ Role ============

    public function createRole(): void
    {
        $this->reset(['role_id', 'role_name', 'copyFromRole']);
        $this->resetValidation();
        $this->isRoleModalOpen = true;
    }

    public function editRole(int $id): void
    {
        $role = Role::findOrFail($id);
        if ($reason = $this->roleLockReason($role)) {
            $this->fail($reason);
            return;
        }
        $this->role_id = $role->id;
        $this->role_name = $role->name;
        $this->copyFromRole = null;
        $this->resetValidation();
        $this->isRoleModalOpen = true;
    }

    public function storeRole(): void
    {
        $this->role_name = trim($this->role_name);
        $this->validate([
            'role_name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($this->role_id)],
            'copyFromRole' => ['nullable', 'exists:roles,id'],
        ], [
            'role_name.required' => 'Nama role wajib diisi.',
            'role_name.unique' => 'Nama role sudah dipakai.',
        ]);

        if ($this->role_id) {
            $role = Role::findOrFail($this->role_id);
            if ($reason = $this->roleLockReason($role)) {
                $this->fail($reason);
                return;
            }
            if (in_array($role->name, $this->protectedRoles(), true) && $role->name !== $this->role_name) {
                $this->addError('role_name', "Role {$role->name} dipakai sistem dan tidak bisa diganti nama.");
                return;
            }
            $role->update(['name' => $this->role_name]);
        } else {
            $role = Role::create(['name' => $this->role_name, 'guard_name' => 'web']);
            if ($this->copyFromRole) {
                $source = Role::find($this->copyFromRole);
                // Non-admin tidak boleh menyalin izin Administrator.
                if ($source && (!in_array($source->name, $this->protectedRoles(), true) || $this->actorIsAdmin())) {
                    $role->syncPermissions($source->permissions);
                }
            }
        }
        $this->flushCache();
        $this->isRoleModalOpen = false;
        $this->dispatch('swal:success', ['title' => 'Tersimpan', 'text' => "Role {$role->name} disimpan."]);
    }

    public function deleteRole(int $id): void
    {
        $role = Role::withCount('users')->findOrFail($id);
        if (in_array($role->name, $this->protectedRoles(), true)) {
            $this->fail("Role {$role->name} dipakai sistem dan tidak bisa dihapus.");
            return;
        }
        if ($reason = $this->roleLockReason($role)) {
            $this->fail($reason);
            return;
        }
        if ($role->users_count > 0) {
            $this->fail("Role {$role->name} masih dipakai {$role->users_count} user. Pindahkan user ke role lain dulu di Daftar User.");
            return;
        }
        $name = $role->name;
        $role->delete();
        $this->flushCache();
        $this->dispatch('swal:success', ['title' => 'Dihapus', 'text' => "Role {$name} dihapus."]);
    }

    public function closeRoleModal(): void
    {
        $this->isRoleModalOpen = false;
        $this->resetValidation();
    }

    // ============ Permission ============

    public function createPermission(): void
    {
        $this->reset(['permission_id', 'permission_name']);
        $this->resetValidation();
        $this->isPermissionModalOpen = true;
    }

    public function editPermission(int $id): void
    {
        $permission = Permission::findOrFail($id);
        if ($this->isSystem($permission->name)) {
            $this->fail("Permission {$permission->name} dipakai di kode aplikasi, jadi namanya tidak bisa diubah.");
            return;
        }
        $this->permission_id = $permission->id;
        $this->permission_name = $permission->name;
        $this->resetValidation();
        $this->isPermissionModalOpen = true;
    }

    public function storePermission(): void
    {
        $this->permission_name = trim($this->permission_name);
        $this->validate([
            'permission_name' => ['required', 'string', 'max:100', Rule::unique('permissions', 'name')->ignore($this->permission_id)],
        ], [
            'permission_name.required' => 'Nama permission wajib diisi.',
            'permission_name.unique' => 'Nama permission sudah ada.',
        ]);

        if ($this->permission_id) {
            $permission = Permission::findOrFail($this->permission_id);
            if ($this->isSystem($permission->name)) {
                $this->fail('Permission sistem tidak bisa diganti nama.');
                return;
            }
            $permission->update(['name' => $this->permission_name]);
        } else {
            Permission::create(['name' => $this->permission_name, 'guard_name' => 'web']);
        }
        $this->flushCache();
        $this->isPermissionModalOpen = false;
        $this->dispatch('swal:success', ['title' => 'Tersimpan', 'text' => "Permission {$this->permission_name} disimpan. Permission baru baru berfungsi setelah dipakai di kode."]);
    }

    public function deletePermission(int $id): void
    {
        $permission = Permission::withCount(['roles', 'users'])->findOrFail($id);
        if ($this->isSystem($permission->name)) {
            $this->fail("Permission {$permission->name} dipakai di kode aplikasi dan tidak bisa dihapus.");
            return;
        }
        if ($permission->roles_count + $permission->users_count > 0) {
            $this->fail("Permission {$permission->name} masih diberikan ke {$permission->roles_count} role. Cabut dulu di Matriks akses.");
            return;
        }
        $name = $permission->name;
        $permission->delete();
        $this->flushCache();
        $this->dispatch('swal:success', ['title' => 'Dihapus', 'text' => "Permission {$name} dihapus."]);
    }

    public function closePermissionModal(): void
    {
        $this->isPermissionModalOpen = false;
        $this->resetValidation();
    }

    // ============ Data ============

    /** Permission dari database, dikelompokkan sesuai katalog (+ "Lainnya"). */
    protected function groupedPermissions(): Collection
    {
        $catalog = $this->catalog();
        $groupOf = [];
        $descOf = [];
        foreach ($catalog as $group => $items) {
            foreach ($items as $name => $desc) {
                $groupOf[$name] = $group;
                $descOf[$name] = $desc;
            }
        }
        $order = array_flip(array_keys($catalog));

        return Permission::withCount('roles')->orderBy('name')->get()
            ->map(function ($p) use ($groupOf, $descOf) {
                $p->group = $groupOf[$p->name] ?? 'Lainnya';
                $p->description = $descOf[$p->name] ?? 'Tidak dipakai di kode aplikasi.';
                $p->is_system = isset($groupOf[$p->name]);
                return $p;
            })
            ->groupBy('group')
            ->sortBy(fn ($items, $group) => $order[$group] ?? 999);
    }

    public function render()
    {
        $groups = $this->groupedPermissions();
        $term = mb_strtolower(trim($this->search));
        if ($term !== '') {
            $groups = $groups->map(fn ($items, $group) => str_contains(mb_strtolower($group), $term)
                ? $items
                : $items->filter(fn ($p) => str_contains(mb_strtolower($p->name . ' ' . $p->description), $term)))
                ->filter(fn ($items) => $items->isNotEmpty());
        }

        $roles = Role::with('permissions:id,name')->withCount('users')->orderBy('name')->get()
            ->sortBy(fn ($r) => in_array($r->name, $this->protectedRoles(), true) ? 0 : 1)->values();
        $locks = $roles->mapWithKeys(fn ($r) => [$r->id => $this->roleLockReason($r)]);

        return view('livewire.roles.manage-roles-permissions', [
            'groups' => $groups,
            'roles' => $roles,
            'granted' => $roles->mapWithKeys(fn ($r) => [$r->id => $r->permissions->pluck('id')->flip()->all()]),
            'locks' => $locks,
            'protectedRoles' => $this->protectedRoles(),
            'lockedOnProtected' => config('permissions.locked_on_protected', []),
            'permissionCount' => $groups->flatten(1)->count(),
            'unusedCount' => $this->groupedPermissions()->get('Lainnya', collect())->count(),
        ]);
    }
}
