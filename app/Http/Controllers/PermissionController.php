<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function new() 
    {
        $permissions = Permission::all();
        return view('permission.new',compact('permissions'));
    }

    public function store(Request $request)
    {
         // Buscar si ya existe el permiso
        $permission = Permission::where([
            ['name', '=', $request->name],
            ['guard_name', '=', $request->guard_name]
        ])->first();

        // Si no existe, lo creamos
        if (!$permission) {
            $permission = Permission::create([
                'guard_name' => $request->guard_name,
                'name' => $request->name
            ]);
        }

        // Asignamos el permiso al rol administrador
        $role = Role::where('name', 'admin')->first();
        $role->givePermissionTo($permission);

        
        return redirect()->action([PermissionController::class, 'new']);
    }

    public function edit(Permission $permission)
    {
        return $permission;
    }
}
