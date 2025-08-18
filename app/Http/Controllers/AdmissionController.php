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
        $analisis = Test::select('id','code', 'name')->get();
        return view('admission.index',compact('current_patient','insurances','analisis'));
    
    }

    public function store(Request $request)
    {
        

    }
}
