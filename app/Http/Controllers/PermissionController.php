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
        $role = Role::where('name','administrator')->first();
        
        $permission = Permission::create(['guard_name' => $request->guard_name, 'name' => $request->name]);
        $role->givePermissionTo($request->name);
        return redirect()->action([PermissionController::class, 'new']);
    }

    public function edit(Permission $permission)
    {
        return $permission;
    }
}
