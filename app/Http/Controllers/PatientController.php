<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;

class PatientController extends Controller
{
    public function index()
    {
        return view('patient.index');
    
    }

    public function store(Request $request)
    {
        $patient = new Patient;
        $patient->name = strtolower($request->name);
        $patient->lastName = strtolower($request->last_name);
        $patient->patientId = $request->id;
        $patient->email = $request->email;
        $patient->phone = $request->phone;
        $patient->birth = $request->birth;
        $patient->sex = $request->sex;
        $patient->type = 'active';
        $patient->address = strtolower($request->address);
        $patient->country = $request->country;
        $patient->state = $request->state;
        
        try {
            $patient->save();
            return redirect()->back();
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }
        
        // return $patient;
        // return $request;
    }
}
