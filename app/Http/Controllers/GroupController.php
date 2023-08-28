<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;

class GroupController extends Controller
{
    public function index()
    {
        return view('group.index');
    
    }

    public function store(Request $request)
    {
        $group = new Group;
        $group->name = strtolower($request->name);
        $group->email = $request->email;
        $group->phone = $request->phone;
        $group->address = $request->address;
        $group->country = $request->country;
        $group->state = $request->state;
        
        try {
            $group->save();
            return redirect()->back();
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }
    }
}
