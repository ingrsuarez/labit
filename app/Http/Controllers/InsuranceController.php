<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Insurance;
use App\Models\Group;

class InsuranceController extends Controller
{
    public function index()
    {
        $groups = Group::all();
        return view('insurance.index',compact('groups'));
    
    }

    public function store(Request $request)
    {
        $insurance = new Insurance;
        $insurance->name = strtolower($request->name);
        $insurance->tax_id = $request->tax_id;
        $insurance->tax = $request->tax;
        $insurance->group = $request->group;
        $insurance->email = $request->email;
        $insurance->phone = $request->phone;
        $insurance->address = $request->address;
        $insurance->price = $request->price;
        $insurance->nbu = $request->nbu;
        $insurance->instructions = strtolower($request->instructions);;
        $insurance->address = strtolower($request->address);
        $insurance->country = $request->country;
        $insurance->state = $request->state;
        
        try {
            $insurance->save();
            return redirect()->back();
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }
    }
}
