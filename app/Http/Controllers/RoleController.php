<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function new() 
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        
        // Agregar contadores manualmente
        foreach ($roles as $role) {
            $role->users_count = User::role($role->name)->count();
            $role->permissions_count = $role->permissions->count();
        }
        
        return view('role.new', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $role = Role::create([
            'name' => strtolower($data['name']),
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        return redirect()->route('role.edit', $role)
            ->with('success', "Rol '{$role->name}' creado correctamente");
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        $role->users_count = User::role($role->name)->count();
        $role->permissions_count = $role->permissions->count();
        
        $permissions = Permission::orderBy('name')->get();
        
        // Agrupar permisos por módulo (antes del punto)
        $groupedPermissions = $permissions->groupBy(function($permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'general';
        });

        // Usuarios con este rol
        $usersWithRole = User::role($role->name)->with('employee')->get();

        return view('role.edit', compact('role', 'permissions', 'groupedPermissions', 'usersWithRole'));
    }

    public function update(Request $request)
    {
        $role = Role::findOrFail($request->role_id);
        
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'guard_name' => 'nullable|string|max:255',
        ]);

        $role->name = strtolower($data['name']);
        $role->guard_name = $data['guard_name'] ?? 'web';
        $role->save();

        return redirect()->route('role.edit', $role)
            ->with('success', 'Rol actualizado correctamente');
    }

    public function attachPermission(Role $role, Permission $permission)
    {
        $role->givePermissionTo($permission->name);
        
        return redirect()->route('role.edit', $role)
            ->with('success', "Permiso '{$permission->name}' agregado");
    }

    public function detachPermission(Role $role, Permission $permission)
    {
        $role->revokePermissionTo($permission->name);
        
        return redirect()->route('role.edit', $role)
            ->with('success', "Permiso '{$permission->name}' removido");
    }

    public function destroy(Role $role)
    {
        $roleName = $role->name;
        $role->delete();

        return redirect()->route('role.new')
            ->with('success', "Rol '{$roleName}' eliminado correctamente");
    }
}
