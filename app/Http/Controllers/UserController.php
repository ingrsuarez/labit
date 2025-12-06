<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Employee;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'employee'])->orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    public function edit(Request $request)
    {
        $user = User::with(['roles', 'permissions', 'employee'])->findOrFail($request->user);
        $roles = Role::orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        
        // Empleados disponibles (no asignados a otro usuario o asignados a este usuario)
        $availableEmployees = Employee::where(function($q) use ($user) {
            $q->whereNull('user_id')
              ->orWhere('user_id', $user->id);
        })->orderBy('lastName')->get();

        return view('users.edit', compact('user', 'roles', 'permissions', 'availableEmployees'));
    }

    public function save(Request $request)
    {
        $user = User::findOrFail($request->id);
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'userId' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $user->name = $data['name'];
        $user->lastName = $data['last_name'] ?? null;
        $user->userId = $data['userId'] ?? null;
        $user->email = $data['email'];
        $user->save();

        // Actualizar vinculación con empleado
        if (!empty($data['employee_id'])) {
            // Desvincular empleado anterior si existe
            Employee::where('user_id', $user->id)->update(['user_id' => null]);
            // Vincular nuevo empleado
            Employee::where('id', $data['employee_id'])->update(['user_id' => $user->id]);
        } else {
            // Desvincular empleado si se seleccionó "ninguno"
            Employee::where('user_id', $user->id)->update(['user_id' => null]);
        }

        return redirect()->route('user.edit', ['user' => $user->id])
            ->with('success', 'Usuario actualizado correctamente');
    }

    public function attachRole(Request $request)
    {
        $user = User::findOrFail($request->user);
        $role = Role::findOrFail($request->role);

        $user->assignRole($role->name);

        return redirect()->route('user.edit', ['user' => $user->id])
            ->with('success', "Rol '{$role->name}' asignado correctamente");
    }

    public function detachRole(Request $request)
    {
        $user = User::findOrFail($request->user);
        $role = Role::findOrFail($request->role);

        $user->removeRole($role->name);

        return redirect()->route('user.edit', ['user' => $user->id])
            ->with('success', "Rol '{$role->name}' removido correctamente");
    }

    /**
     * Sincronizar roles del usuario (para actualización masiva)
     */
    public function syncRoles(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $roleIds = $request->input('roles', []);
        
        $roles = Role::whereIn('id', $roleIds)->pluck('name')->toArray();
        $user->syncRoles($roles);

        return redirect()->route('user.edit', ['user' => $user->id])
            ->with('success', 'Roles actualizados correctamente');
    }
}
