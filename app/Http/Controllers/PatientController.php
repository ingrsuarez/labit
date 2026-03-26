<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index()
    {
        $insurances = Insurance::where('type', '!=', 'nomenclador')
            ->orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        return view('patient.index', compact('insurances'));

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
        $patient->insurance = $request->insurance;
        $patient->insurance_cod = $request->insurance_cod;

        try {
            $patient->save();
            $patient->logAudit('created', 'Creó el paciente '.$patient->full_name);

            return redirect()->back()->with('success', 'Paciente creado correctamente.');
        } catch (\Exception $e) {

            if ($e->errorInfo[1] == 1062) {

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
        $insurances = Insurance::where('type', '!=', 'nomenclador')
            ->orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        $patient = Patient::where('patientId', $request->current_patient)->first();
        $patient->load('auditLogs');

        return view('patient.edit', compact('insurances', 'patient'));
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
            $patient->logAudit('updated', 'Editó el paciente '.$patient->full_name);

            return redirect()->route('patient.show');
        } catch (\Exception $e) {

            if ($e->errorInfo[1] == 1062) {

                return redirect()->back()->with('error', 'El DNI ingresado ya existe!');

            } else {
                throw $e;
            }
        }

    }
}
