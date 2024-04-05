<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('users.index',compact('users'));
    }

    public function edit(Request $request)
    {
        $user = User::find($request->user);

        $roles = Role::all();
        return view('users.edit',compact('roles','user'));
    }

    public function save(Request $request)
    {
        $user = User::find($request->id);
        
        $user->name = $request->name;
        $user->lastName = $request->last_name;
        $user->userId = $request->userId;
        $user->save();
        return redirect()->action([UserController::class, 'index']);
    }

    public function attachRole(Request $request)
    {
        $user = User::find($request->user);
        $role = Role::find($request->role);

        $user->assignRole($role->name);
        $roles = Role::all();
        return view('users.edit',compact('roles','user'));

    }
    public function detachRole(Request $request)
    {
        $user = User::find($request->user);
        $role = Role::find($request->role);

        $user->removeRole($role->name);
        $roles = Role::all();
        return view('users.edit',compact('roles','user'));

    }
}
