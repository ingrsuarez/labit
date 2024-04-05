<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function new() 
    {
        $roles = Role::all();
        return view('role.new',compact('roles'));
    }

    public function store(Request $request)
    {
        $role = Role::create(['guard_name' => $request->guard_name, 'name' => $request->name]);
        return redirect()->action([RoleController::class, 'new']);
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        return view('role.edit',compact('role','permissions'));
    }

    public function update(Request $request)
    {
        $role = Role::where('id',$request->role_id)->first();
        $role->name = $request->name;
        $role->guard_name = $request->guard_name;
        
        $role->save();
        return redirect()->action([RoleController::class, 'new']);
    }

    public function attachPermission(Role $role, Permission $permission)
    {
        $role->givePermissionTo($permission->name);
        return redirect()->action([RoleController::class, 'edit'],['role'=>$role->id]);
    }

    public function detachPermission(Role $role, Permission $permission)
    {
        $role->revokePermissionTo($permission->name);
        return redirect()->action([RoleController::class, 'edit'],['role'=>$role->id]);
    }
}
