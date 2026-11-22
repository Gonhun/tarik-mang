<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\WithPagination;

class ManageRolesPermissions extends Component
{
    use WithPagination;

    // Properti untuk Role
    public $role_id, $role_name;
    public $role_permissions = []; // Untuk menampung izin yang dipilih untuk sebuah role

    // Properti untuk Permission
    public $permission_id, $permission_name;

    // Properti UI
    public $tab = 0;
    public $isRoleModalOpen = false;
    public $isPermissionModalOpen = false;
    public $searchRole = '', $searchPermission = '';
    protected $paginationTheme = 'bootstrap';

    // Listener untuk konfirmasi delete
    protected $listeners = ['deleteRoleConfirmed', 'deletePermissionConfirmed'];

    public function render()
    {
        $roles = Role::with('permissions')
            ->where('name', 'like', '%' . $this->searchRole . '%')
            ->orderBy('name')->paginate(15, ['*'], 'rolesPage');
            
        $permissions = Permission::where('name', 'like', '%' . $this->searchPermission . '%')
            ->orderBy('name')->paginate(15, ['*'], 'permissionsPage');

        return view('livewire.roles.manage-roles-permissions', [
            'roles' => $roles,
            'allPermissions' => Permission::orderBy('name')->get(), // Untuk checklist di form role
            'permissions' => $permissions
        ]);
    }
    
    // ===================================
    //      LOGIKA UNTUK ROLES
    // ===================================

    public function createRole()
    {
        $this->reset(['role_id', 'role_name', 'role_permissions']);
        $this->isRoleModalOpen = true;
    }

    public function editRole(Role $role)
    {
        $this->role_id = $role->id;
        $this->role_name = $role->name;
        $this->role_permissions = $role->permissions->pluck('id')->toArray();
        $this->isRoleModalOpen = true;
    }

    public function storeRole()
    {
        $this->validate([
            'role_name' => 'required|string|max:100|unique:roles,name,' . $this->role_id,
            'role_permissions' => 'nullable|array',
        ]);

        $role = Role::updateOrCreate(['id' => $this->role_id], ['name' => $this->role_name]);
        $permissions = Permission::whereIn('id', $this->role_permissions)->get();
        $role->syncPermissions($permissions);

        session()->flash('message', 'Role berhasil disimpan.');
        $this->closeRoleModal();
    }

    public function deleteRole($id)
    {
       
        $role = Role::find($id);
        if ($role && $role->users()->count() > 0) {
            $this->dispatch('swal:error', [
                'title' => 'Gagal Menghapus!',
                'text' => "Role '{$role->name}' tidak bisa dihapus karena masih digunakan oleh pengguna.'{$role->users()->nik}"
            ]);
            return;
        }
       
        $this->dispatch('swal:confirm', [
            'type' => 'warning',
            'title' => 'Apakah Anda yakin?',
            'text' => "Anda akan menghapus role '{$role->name}'!",
            'id' => $id,
            'method' => 'deleteRoleConfirmed'
        ]);
    }

    public function deleteRoleConfirmed($id)
    {
        Role::find($id)->delete();
        session()->flash('message', 'Role berhasil dihapus.');
    }
    
    public function closeRoleModal() { $this->isRoleModalOpen = false; $this->resetValidation(); }
    
    // ===================================
    //      LOGIKA UNTUK PERMISSIONS
    // ===================================

    public function createPermission()
    {
        $this->reset(['permission_id', 'permission_name']);
        $this->isPermissionModalOpen = true;
    }

    public function editPermission(Permission $permission)
    {
        $this->permission_id = $permission->id;
        $this->permission_name = $permission->name;
        $this->isPermissionModalOpen = true;
    }

    public function storePermission()
    {
        $this->validate([
            'permission_name' => 'required|string|max:100|unique:permissions,name,' . $this->permission_id,
        ]);

        Permission::updateOrCreate(['id' => $this->permission_id], ['name' => $this->permission_name]);
        session()->flash('message', 'Permission berhasil disimpan.');
        $this->closePermissionModal();
    }

    public function deletePermission($id)
    {
        $permission = Permission::find($id);
        if ($permission && ($permission->roles()->count() > 0 || $permission->users()->count() > 0)) {
            $this->dispatch('swal:error', [
                'title' => 'Gagal Menghapus!',
                'text' => "Permission '{$permission->name}' tidak bisa dihapus karena masih digunakan oleh role atau pengguna."
            ]);
            return;
        }

         $this->dispatch('swal:confirm', [
            'type' => 'warning',
            'title' => 'Apakah Anda yakin?',
            'text' => "Anda akan menghapus permission '{$permission->name}'!",
            'id' => $id,
            'method' => 'deletePermissionConfirmed'
        ]);
    }
    
    public function deletePermissionConfirmed($id)
    {
        Permission::find($id)->delete();
        session()->flash('message', 'Permission berhasil dihapus.');
    }

    public function closePermissionModal() { $this->isPermissionModalOpen = false; $this->resetValidation(); }
}
