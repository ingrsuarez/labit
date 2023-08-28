<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Admission;
use App\Models\Insurance;
use App\Models\Patient;
use App\Models\Test;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $current_patient = Patient::where('patientId',$request->current_patient)->first();   
        $insurances = Insurance::all();     
        return view('admission.index',compact('current_patient','insurances'));
    
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
