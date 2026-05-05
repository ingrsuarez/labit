<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use App\Models\Patient;
use Illuminate\Http\Request;

class LabPatientController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('patients.index');

        $query = Patient::with('insuranceRelation')
            ->orderBy('lastName')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('patientId', 'like', "%{$search}%")
                    ->orWhereRaw('lower(name) LIKE ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('lower(lastName) LIKE ?', ['%'.strtolower($search).'%']);
            });
        }

        if ($request->filled('insurance')) {
            $query->where('insurance', $request->insurance);
        }

        $patients = $query->paginate(20)->withQueryString();

        $insurances = Insurance::where('type', '!=', 'nomenclador')
            ->orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        return view('lab.patients.index', compact('patients', 'insurances'));
    }
}
