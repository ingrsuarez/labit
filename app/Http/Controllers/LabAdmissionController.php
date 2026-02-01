<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\Patient;
use App\Models\Test;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LabAdmissionController extends Controller
{
    /**
     * Listado de admisiones
     */
    public function index(Request $request)
    {
        $query = Admission::with(['patient', 'insuranceRelation', 'admissionTests'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('protocol_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('lastName', 'like', "%{$search}%")
                         ->orWhere('patientId', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('insurance')) {
            $query->where('insurance', $request->insurance);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $admissions = $query->paginate(20)->withQueryString();
        $insurances = Insurance::orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('lab.admissions.index', compact('admissions', 'insurances'));
    }

    /**
     * Formulario para crear nueva admisión
     */
    public function create(Request $request)
    {
        $patient = null;
        if ($request->filled('patient_id')) {
            $patient = Patient::find($request->patient_id);
        }

        $insurances = Insurance::orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
            ->orderBy('type')
            ->orderBy('name')
            ->get();
        $tests = Test::whereNull('parent')->orderBy('code')->get(['id', 'code', 'name', 'nbu', 'price']);

        return view('lab.admissions.create', compact('patient', 'insurances', 'tests'));
    }

    /**
     * Guardar nueva admisión
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'date' => 'required|date',
            'insurance_id' => 'required|exists:insurances,id',
            'affiliate_number' => 'nullable|string|max:50',
            'requesting_doctor' => 'nullable|string|max:255',
            'tests' => 'required|array|min:1',
            'tests.*.test_id' => 'required|exists:tests,id',
            'tests.*.price' => 'required|numeric|min:0',
            'tests.*.authorization_status' => 'required|in:pending,authorized,rejected,not_required',
            'tests.*.paid_by_patient' => 'boolean',
            'tests.*.copago' => 'nullable|numeric|min:0',
        ]);

        // Crear la admisión
        $admission = Admission::create([
            'date' => $request->date,
            'number' => Admission::max('id') + 1,
            'protocol_number' => Admission::generateProtocolNumber(),
            'patient_id' => $request->patient_id,
            'insurance' => $request->insurance_id,
            'affiliate_number' => $request->affiliate_number,
            'requesting_doctor' => $request->requesting_doctor,
            'diagnosis' => $request->diagnosis,
            'observations' => $request->observations,
            'room' => 1,
            'institution' => 1,
            'invoice_date' => $request->date,
            'promise_date' => Carbon::parse($request->date)->addDays(3),
            'authorization_code' => '',
            'attended_by' => auth()->id(),
            'created_by' => auth()->id(),
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'status' => 'pending',
        ]);

        // Agregar las prácticas
        $totalInsurance = 0;
        $totalPatient = 0;
        $totalCopago = 0;

        foreach ($request->tests as $testData) {
            $test = Test::find($testData['test_id']);
            $paidByPatient = $testData['paid_by_patient'] ?? false;
            $copago = $testData['copago'] ?? 0;
            $price = $testData['price'];

            AdmissionTest::create([
                'admission_id' => $admission->id,
                'test_id' => $testData['test_id'],
                'price' => $price,
                'nbu_units' => $test->nbu ?? 1,
                'authorization_status' => $testData['authorization_status'],
                'paid_by_patient' => $paidByPatient,
                'copago' => $copago,
                'authorization_code' => $testData['authorization_code'] ?? null,
                'observations' => $testData['observations'] ?? null,
            ]);

            // Calcular totales
            if ($paidByPatient || $testData['authorization_status'] === 'rejected') {
                $totalPatient += $price;
            } else {
                $totalInsurance += $price - $copago;
                $totalCopago += $copago;
            }
        }

        // Actualizar totales
        $admission->update([
            'total_insurance' => $totalInsurance,
            'total_patient' => $totalPatient,
            'total_copago' => $totalCopago,
        ]);

        return redirect()->route('lab.admissions.show', $admission)
            ->with('success', 'Admisión creada correctamente. Protocolo: ' . $admission->protocol_number);
    }

    /**
     * Ver detalle de admisión
     */
    public function show(Admission $admission)
    {
        $admission->load(['patient', 'insuranceRelation', 'admissionTests.test', 'creator']);
        return view('lab.admissions.show', compact('admission'));
    }

    /**
     * Editar admisión
     */
    public function edit(Admission $admission)
    {
        $admission->load(['patient', 'insuranceRelation', 'admissionTests.test']);
        $insurances = Insurance::orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
            ->orderBy('type')
            ->orderBy('name')
            ->get();
        $tests = Test::whereNull('parent')->orderBy('code')->get(['id', 'code', 'name', 'nbu', 'price']);

        return view('lab.admissions.edit', compact('admission', 'insurances', 'tests'));
    }

    /**
     * Actualizar admisión
     */
    public function update(Request $request, Admission $admission)
    {
        $request->validate([
            'date' => 'required|date',
            'insurance_id' => 'required|exists:insurances,id',
            'affiliate_number' => 'nullable|string|max:50',
            'requesting_doctor' => 'nullable|string|max:255',
        ]);

        $admission->update([
            'date' => $request->date,
            'insurance' => $request->insurance_id,
            'affiliate_number' => $request->affiliate_number,
            'requesting_doctor' => $request->requesting_doctor,
            'diagnosis' => $request->diagnosis,
            'observations' => $request->observations,
        ]);

        return redirect()->route('lab.admissions.show', $admission)
            ->with('success', 'Admisión actualizada correctamente.');
    }

    /**
     * Agregar práctica a admisión existente
     */
    public function addTest(Request $request, Admission $admission)
    {
        $request->validate([
            'test_id' => 'required|exists:tests,id',
            'price' => 'required|numeric|min:0',
            'authorization_status' => 'required|in:pending,authorized,rejected,not_required',
            'paid_by_patient' => 'boolean',
            'copago' => 'nullable|numeric|min:0',
        ]);

        $test = Test::find($request->test_id);

        AdmissionTest::create([
            'admission_id' => $admission->id,
            'test_id' => $request->test_id,
            'price' => $request->price,
            'nbu_units' => $test->nbu ?? 1,
            'authorization_status' => $request->authorization_status,
            'paid_by_patient' => $request->boolean('paid_by_patient'),
            'copago' => $request->copago ?? 0,
            'authorization_code' => $request->authorization_code,
            'observations' => $request->observations,
        ]);

        $admission->calculateTotals();

        return redirect()->back()->with('success', 'Práctica agregada correctamente.');
    }

    /**
     * Actualizar práctica de admisión
     */
    public function updateTest(Request $request, Admission $admission, AdmissionTest $test)
    {
        $request->validate([
            'authorization_status' => 'required|in:pending,authorized,rejected,not_required',
            'paid_by_patient' => 'boolean',
            'copago' => 'nullable|numeric|min:0',
        ]);

        $test->update([
            'authorization_status' => $request->authorization_status,
            'paid_by_patient' => $request->boolean('paid_by_patient'),
            'copago' => $request->copago ?? 0,
            'authorization_code' => $request->authorization_code,
            'observations' => $request->observations,
        ]);

        $admission->calculateTotals();

        return redirect()->back()->with('success', 'Práctica actualizada correctamente.');
    }

    /**
     * Eliminar práctica de admisión
     */
    public function removeTest(Admission $admission, AdmissionTest $test)
    {
        $test->delete();
        $admission->calculateTotals();

        return redirect()->back()->with('success', 'Práctica eliminada correctamente.');
    }

    /**
     * API: Obtener precio de una práctica para una obra social
     */
    public function getTestPrice(Request $request)
    {
        $insuranceId = $request->get('insurance_id');
        $testId = $request->get('test_id');

        if (!$insuranceId || !$testId) {
            return response()->json(['error' => 'Parámetros requeridos'], 400);
        }

        $insurance = Insurance::find($insuranceId);
        $test = Test::find($testId);

        if (!$insurance || !$test) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        // Buscar en nomenclador
        $insuranceTest = InsuranceTest::where('insurance_id', $insuranceId)
            ->where('test_id', $testId)
            ->first();

        if ($insuranceTest) {
            return response()->json([
                'price' => $insuranceTest->price,
                'nbu_units' => $insuranceTest->nbu_units,
                'requires_authorization' => $insuranceTest->requires_authorization,
                'copago' => $insuranceTest->copago,
                'in_nomenclator' => true,
            ]);
        }

        // Calcular precio basado en NBU
        $nbuUnits = $test->nbu ?? 1;
        $price = $nbuUnits * ($insurance->nbu_value ?? 0);

        return response()->json([
            'price' => $price,
            'nbu_units' => $nbuUnits,
            'requires_authorization' => false,
            'copago' => 0,
            'in_nomenclator' => false,
        ]);
    }

    /**
     * API: Buscar pacientes
     */
    public function searchPatients(Request $request)
    {
        $search = $request->get('q', '');

        $patients = Patient::where(function ($query) use ($search) {
            $query->where('patientId', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('lastName', 'like', "%{$search}%");
        })
        ->limit(10)
        ->get(['id', 'name', 'lastName', 'patientId', 'birth', 'insurance', 'insurance_cod']);

        return response()->json($patients->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'lastName' => $p->lastName,
                'fullName' => $p->full_name,
                'patientId' => $p->patientId,
                'birth' => $p->birth?->format('d/m/Y'),
                'insurance_id' => $p->insurance,
                'affiliate_number' => $p->insurance_cod,
            ];
        }));
    }

    /**
     * API: Buscar prácticas
     */
    public function searchTests(Request $request)
    {
        $search = $request->get('q', '');
        $insuranceId = $request->get('insurance_id');

        $tests = Test::whereNull('parent')
            ->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'code', 'name', 'nbu', 'price']);

        // Si hay obra social, agregar info del nomenclador
        if ($insuranceId) {
            $insurance = Insurance::find($insuranceId);
            $tests = $tests->map(function ($test) use ($insurance, $insuranceId) {
                $insuranceTest = InsuranceTest::where('insurance_id', $insuranceId)
                    ->where('test_id', $test->id)
                    ->first();

                if ($insuranceTest) {
                    $test->calculated_price = $insuranceTest->price;
                    $test->requires_authorization = $insuranceTest->requires_authorization;
                    $test->copago = $insuranceTest->copago;
                    $test->in_nomenclator = true;
                } else {
                    $test->calculated_price = ($test->nbu ?? 1) * ($insurance->nbu_value ?? 0);
                    $test->requires_authorization = false;
                    $test->copago = 0;
                    $test->in_nomenclator = false;
                }

                return $test;
            });
        }

        return response()->json($tests);
    }
}

