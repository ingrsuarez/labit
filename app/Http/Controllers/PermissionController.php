<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionController extends Controller
{
    public function new() 
    {
        $permissions = Permission::orderBy('name')->get();
        
        // Agrupar permisos por módulo (primera parte del nombre)
        $groupedPermissions = $permissions->groupBy(function($permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'otros';
        });

        // Contar roles que usan cada permiso
        foreach ($permissions as $permission) {
            $permission->roles_count = $permission->roles()->count();
        }

        return view('permission.new', compact('permissions', 'groupedPermissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'guard_name' => 'required|string|in:web,api,sanctum',
        ]);

        // Normalizar nombre (minúsculas, sin espacios extra)
        $name = strtolower(trim($request->name));
        $name = preg_replace('/\s+/', '.', $name); // Reemplazar espacios con puntos

        // Buscar si ya existe el permiso
        $existing = Permission::where('name', $name)
            ->where('guard_name', $request->guard_name)
            ->first();

        if ($existing) {
            return redirect()->route('permission.new')
                ->with('error', 'El permiso "' . $name . '" ya existe.');
        }

        // Crear el permiso
        $permission = Permission::create([
            'name' => $name,
            'guard_name' => $request->guard_name,
        ]);

        return redirect()->route('permission.new')
            ->with('success', 'Permiso "' . $name . '" creado correctamente.');
    }

    public function edit(Permission $permission)
    {
        // Obtener roles que tienen este permiso
        $rolesWithPermission = $permission->roles;
        
        // Obtener roles disponibles (que no tienen el permiso)
        $availableRoles = Role::where('guard_name', $permission->guard_name)
            ->whereNotIn('id', $rolesWithPermission->pluck('id'))
            ->get();

        return view('permission.edit', compact('permission', 'rolesWithPermission', 'availableRoles'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'guard_name' => 'required|string|in:web,api,sanctum',
        ]);

        $name = strtolower(trim($request->name));
        $name = preg_replace('/\s+/', '.', $name);

        // Verificar que no exista otro permiso con el mismo nombre
        $existing = Permission::where('name', $name)
            ->where('guard_name', $request->guard_name)
            ->where('id', '!=', $permission->id)
            ->first();

        if ($existing) {
            return redirect()->route('permission.edit', $permission)
                ->with('error', 'Ya existe otro permiso con el nombre "' . $name . '".');
        }

        $permission->update([
            'name' => $name,
            'guard_name' => $request->guard_name,
        ]);

        return redirect()->route('permission.edit', $permission)
            ->with('success', 'Permiso actualizado correctamente.');
    }

    public function destroy(Permission $permission)
    {
        $name = $permission->name;
        
        // Eliminar el permiso (automáticamente se desvincula de los roles)
        $permission->delete();

        return redirect()->route('permission.new')
            ->with('success', 'Permiso "' . $name . '" eliminado correctamente.');
    }

    public function attachRole(Permission $permission, Role $role)
    {
        if (!$role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }

        return redirect()->route('permission.edit', $permission)
            ->with('success', 'Rol "' . $role->name . '" asignado al permiso.');
    }

    public function detachRole(Permission $permission, Role $role)
    {
        $role->revokePermissionTo($permission);

        return redirect()->route('permission.edit', $permission)
            ->with('success', 'Rol "' . $role->name . '" removido del permiso.');
    }

    /**
     * Genera permisos automáticamente para un módulo
     */
    public function generateForModule(Request $request)
    {
        $request->validate([
            'module' => 'required|string|max:50',
            'guard_name' => 'required|string|in:web,api,sanctum',
            'actions' => 'required|array|min:1',
        ]);

        $module = strtolower(trim($request->module));
        $actions = $request->actions;
        $created = [];

        foreach ($actions as $action) {
            $name = $module . '.' . $action;
            
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => $request->guard_name]
            );

            if ($permission->wasRecentlyCreated) {
                $created[] = $name;
            }
        }

        if (count($created) > 0) {
            return redirect()->route('permission.new')
                ->with('success', 'Se crearon ' . count($created) . ' permisos para el módulo "' . $module . '".');
        }

        return redirect()->route('permission.new')
            ->with('info', 'Todos los permisos ya existían para el módulo "' . $module . '".');
    }
}
