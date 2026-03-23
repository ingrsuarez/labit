<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use App\Models\Patient;
use App\Models\Test;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $current_patient = Patient::where('patientId', $request->current_patient)->first();
        $insurances = Insurance::where('type', '!=', 'nomenclador')
            ->orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        $analisis = Test::select('id', 'code', 'name')->get();

        return view('admission.index', compact('current_patient', 'insurances', 'analisis'));

    }

    public function store(Request $request) {}
}
