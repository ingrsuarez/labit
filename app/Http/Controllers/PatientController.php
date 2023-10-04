<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\Insurance;

class PatientController extends Controller
{
    public function index()
    {
        $insurances = Insurance::all();
        return view('patient.index',compact('insurances'));
    
    }

    public function store(Request $request)
    {

        if (Patient::where('patientId', $request->id)->exists()) {
            return redirect()->back()->with('error', 'El DNI ingresado ya existe!');
        }

        $patient = new Patient;
        $patient->name = strtolower($request->name);
        $patient->lastName = strtolower($request->last_name);
        $patient->patientId = $request->id;
        $patient->email = $request->email;
        $patient->phone = $request->phone;
        $patient->birth = $request->birth;
        $patient->sex = $request->sex;
        $patient->type = 'active';
        $patient->type = 'active';
        $patient->address = strtolower($request->address);
        $patient->country = $request->country;
        $patient->state = $request->state;
        
        try {
            $patient->save();
            return redirect()->back();
        }
        catch ( \Exception $e ) {
    
            if($e->errorInfo[1] == 1062) {
    
                return redirect()->back()->with('error', 'El DNI ingresado ya existe!');
    
            } else {
                throw $e;
            }
        }
        
    }

    public function show()
    {
        return view('patient.show');
    }

    public function edit(Request $request)
    {
        $insurances = Insurance::all();
        $patient = Patient::where('patientId',$request->current_patient)->first();
        return view('patient.edit',compact('insurances','patient'));
    }

    public function save_changes(Request $request)
    {

        $patient = Patient::where('patientId', $request->id)->first();
        $patient->name = strtolower($request->name);
        $patient->lastName = strtolower($request->last_name);
        $patient->patientId = $request->id;
        $patient->email = $request->email;
        $patient->phone = $request->phone;
        $patient->birth = $request->birth;
        $patient->sex = $request->sex;
        $patient->type = 'active';
        $patient->type = 'active';
        $patient->address = strtolower($request->address);
        $patient->country = $request->country;
        $patient->state = $request->state;
        
        try {
            $patient->save();
            return redirect()->route('patient.show');
        }
        catch ( \Exception $e ) {
    
            if($e->errorInfo[1] == 1062) {
    
                return redirect()->back()->with('error', 'El DNI ingresado ya existe!');
    
            } else {
                throw $e;
            }
        }
        
    }

}
