<?php

namespace App\Http\Controllers;

use App\Mail\AdmissionResultMail;
use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\LabSetting;
use App\Models\Patient;
use App\Models\Test;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PDF;

class LabAdmissionController extends Controller
{
    /**
     * Listado de admisiones
     */
    public function index(Request $request)
    {
        $this->authorize('lab-admissions.index');
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
        $insurances = Insurance::where('type', '!=', 'nomenclador')
            ->orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        return view('lab.admissions.index', compact('admissions', 'insurances'));
    }

    /**
     * Formulario para crear nueva admisión
     */
    public function create(Request $request)
    {
        $this->authorize('lab-admissions.create');
        $patient = null;
        if ($request->filled('patient_id')) {
            $patient = Patient::find($request->patient_id);
        }

        $insurances = Insurance::where('type', '!=', 'nomenclador')
            ->orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
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
        $this->authorize('lab-admissions.create');
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

            // Crear la práctica padre
            $admissionTest = AdmissionTest::create([
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

            // Si la práctica tiene hijos, agregarlos automáticamente (para cargar resultados individuales)
            $children = $test->getAllChildren(false);
            foreach ($children as $childTest) {
                // Solo agregar si no existe ya
                $exists = AdmissionTest::where('admission_id', $admission->id)
                    ->where('test_id', $childTest->id)
                    ->exists();

                if (! $exists) {
                    AdmissionTest::create([
                        'admission_id' => $admission->id,
                        'test_id' => $childTest->id,
                        'price' => 0, // Los hijos no tienen precio adicional
                        'nbu_units' => $childTest->nbu ?? 0,
                        'authorization_status' => 'not_required',
                        'paid_by_patient' => false,
                        'copago' => 0,
                    ]);
                }
            }

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

        // Determinar estado de pago
        $insurance = Insurance::find($request->insurance_id);
        if ($insurance && $insurance->type === 'particular') {
            $total = $totalPatient ?: ($totalInsurance + $totalCopago);
            $paidAmount = (float) ($request->paid_amount ?? 0);

            if ($paidAmount <= 0) {
                $paymentStatus = 'pendiente';
            } elseif ($paidAmount >= $total) {
                $paymentStatus = 'pagado';
                $paidAmount = $total;
            } else {
                $paymentStatus = 'parcial';
            }

            $admission->update([
                'payment_status' => $paymentStatus,
                'payment_method' => $request->payment_method ?: null,
                'paid_amount' => $paidAmount,
                'payment_date' => $paidAmount > 0 ? now() : null,
                'payment_notes' => $request->payment_notes,
            ]);
        } else {
            $admission->update(['payment_status' => 'not_applicable']);
        }

        return redirect()->route('lab.admissions.show', $admission)
            ->with('success', 'Admisión creada correctamente. Protocolo: '.$admission->protocol_number);
    }

    /**
     * Ver detalle de admisión
     */
    public function debtors(Request $request)
    {
        $this->authorize('lab-admissions.index');

        $query = Admission::debtors()
            ->with(['patient', 'insuranceRelation'])
            ->orderBy('date', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('lastName', 'like', "%{$search}%")
                    ->orWhere('patientId', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $admissions = $query->paginate(20)->withQueryString();

        $totalDebt = Admission::debtors()->sum(DB::raw('
            CASE
                WHEN total_patient > 0 THEN total_patient - paid_amount
                WHEN patient_price > 0 THEN patient_price - paid_amount
                ELSE 0
            END
        '));

        $debtorCount = Admission::debtors()->count();

        return view('lab.admissions.debtors', compact('admissions', 'totalDebt', 'debtorCount'));
    }

    public function registerPayment(Request $request, Admission $admission)
    {
        $this->authorize('lab-admissions.edit');

        $request->validate([
            'payment_method' => 'required|in:efectivo,transferencia,mercadopago',
            'amount' => 'required|numeric|min:0.01|max:'.$admission->balance,
            'payment_notes' => 'nullable|string|max:255',
        ]);

        $newPaid = (float) $admission->paid_amount + (float) $request->amount;
        $total = $admission->total_to_pay;

        $notes = $admission->payment_notes;
        if ($request->payment_notes) {
            $notes = trim(($notes ? $notes.' | ' : '').$request->payment_notes);
        }

        $admission->update([
            'paid_amount' => $newPaid,
            'payment_method' => $request->payment_method,
            'payment_date' => now(),
            'payment_status' => $newPaid >= $total ? 'pagado' : 'parcial',
            'payment_notes' => $notes,
        ]);

        return back()->with('success', 'Pago registrado correctamente.');
    }

    public function show(Admission $admission)
    {
        $this->authorize('lab-admissions.show');
        $admission->load([
            'patient',
            'insuranceRelation',
            'admissionTests.test.parentTests',
            'admissionTests.test.referenceValues.category',
            'creator',
        ]);

        // Tests disponibles para agregar al protocolo
        $availableTests = Test::whereNull('parent')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'nbu', 'price']);

        return view('lab.admissions.show', compact('admission', 'availableTests'));
    }

    /**
     * Editar admisión
     */
    public function edit(Admission $admission)
    {
        $this->authorize('lab-admissions.edit');
        $admission->load(['patient', 'insuranceRelation', 'admissionTests.test']);
        $insurances = Insurance::where('type', '!=', 'nomenclador')
            ->orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
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
        $this->authorize('lab-admissions.edit');
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

        // Verificar si la práctica ya existe en el protocolo
        $exists = AdmissionTest::where('admission_id', $admission->id)
            ->where('test_id', $request->test_id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'La práctica "'.$test->code.' - '.$test->name.'" ya existe en este protocolo.');
        }

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

        // Si la práctica tiene hijos, agregarlos automáticamente
        $children = $test->getAllChildren(false);
        $childrenAdded = 0;
        foreach ($children as $childTest) {
            $childExists = AdmissionTest::where('admission_id', $admission->id)
                ->where('test_id', $childTest->id)
                ->exists();

            if (! $childExists) {
                AdmissionTest::create([
                    'admission_id' => $admission->id,
                    'test_id' => $childTest->id,
                    'price' => 0,
                    'nbu_units' => $childTest->nbu ?? 0,
                    'authorization_status' => 'not_required',
                    'paid_by_patient' => false,
                    'copago' => 0,
                ]);
                $childrenAdded++;
            }
        }

        $admission->calculateTotals();

        $message = 'Práctica "'.$test->code.' - '.$test->name.'" agregada correctamente.';
        if ($childrenAdded > 0) {
            $message .= ' ('.$childrenAdded.' determinaciones hijas incluidas)';
        }

        return redirect()->back()->with('success', $message);
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
        // Si es una práctica padre (precio > 0), eliminar también sus hijos
        if ($test->price > 0) {
            $parentTest = $test->test;
            $children = $parentTest->getAllChildren(false);

            foreach ($children as $childTest) {
                AdmissionTest::where('admission_id', $admission->id)
                    ->where('test_id', $childTest->id)
                    ->delete();
            }
        }

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

        if (! $insuranceId || ! $testId) {
            return response()->json(['error' => 'Parámetros requeridos'], 400);
        }

        $insurance = Insurance::find($insuranceId);
        $test = Test::find($testId);

        if (! $insurance || ! $test) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $ownItem = InsuranceTest::where('insurance_id', $insuranceId)
            ->where('test_id', $testId)
            ->first();

        if ($ownItem) {
            return response()->json([
                'price' => $ownItem->price,
                'nbu_units' => $ownItem->nbu_units,
                'requires_authorization' => $ownItem->requires_authorization,
                'copago' => $ownItem->copago,
                'in_nomenclator' => true,
                'source' => 'own',
            ]);
        }

        if ($insurance->nomenclator_id) {
            $baseItem = InsuranceTest::where('insurance_id', $insurance->nomenclator_id)
                ->where('test_id', $testId)
                ->first();

            if ($baseItem) {
                $price = $baseItem->nbu_units * ($insurance->nbu_value ?? 0);

                return response()->json([
                    'price' => round($price, 2),
                    'nbu_units' => $baseItem->nbu_units,
                    'requires_authorization' => $baseItem->requires_authorization ?? false,
                    'copago' => $baseItem->copago ?? 0,
                    'in_nomenclator' => true,
                    'source' => 'base',
                ]);
            }
        }

        $nbuUnits = $test->nbu ?? 1;
        $price = $nbuUnits * ($insurance->nbu_value ?? 0);

        return response()->json([
            'price' => round($price, 2),
            'nbu_units' => $nbuUnits,
            'requires_authorization' => false,
            'copago' => 0,
            'in_nomenclator' => false,
            'source' => 'fallback',
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

    /**
     * Guardar resultado de una práctica
     */
    public function saveResult(Request $request, Admission $admission, AdmissionTest $admissionTest)
    {
        $this->authorize('lab-results.create');
        $request->validate([
            'result' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'reference_value' => 'nullable|string|max:255',
        ]);

        $admissionTest->update([
            'result' => $request->result,
            'unit' => $request->unit,
            'reference_value' => $request->reference_value,
        ]);

        return redirect()->back()->with('success', 'Resultado guardado correctamente.');
    }

    /**
     * Guardar resultados de múltiples prácticas
     */
    public function saveResults(Request $request, Admission $admission)
    {
        $this->authorize('lab-results.create');
        $request->validate([
            'results' => 'required|array',
            'results.*.id' => 'required|exists:admission_tests,id',
            'results.*.result' => 'nullable|string|max:255',
            'results.*.unit' => 'nullable|string|max:50',
            'results.*.reference_value' => 'nullable|string|max:255',
        ]);

        foreach ($request->results as $data) {
            $admissionTest = AdmissionTest::find($data['id']);
            if ($admissionTest && $admissionTest->admission_id === $admission->id) {
                $admissionTest->update([
                    'result' => $data['result'] ?? null,
                    'unit' => $data['unit'] ?? null,
                    'reference_value' => $data['reference_value'] ?? null,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Resultados guardados correctamente.');
    }

    /**
     * Validar una práctica
     */
    public function validateTest(Request $request, Admission $admission, AdmissionTest $admissionTest)
    {
        $this->authorize('lab-results.validate');
        if (! $admissionTest->result) {
            return redirect()->back()->with('error', 'No se puede validar una práctica sin resultado.');
        }

        $admissionTest->update([
            'is_validated' => true,
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Práctica validada correctamente.');
    }

    /**
     * Invalidar/desvalidar una práctica
     */
    public function unvalidateTest(Request $request, Admission $admission, AdmissionTest $admissionTest)
    {
        $this->authorize('lab-results.validate');
        $admissionTest->update([
            'is_validated' => false,
            'validated_by' => null,
            'validated_at' => null,
        ]);

        return redirect()->back()->with('success', 'Validación removida.');
    }

    /**
     * Validar todas las prácticas con resultado
     */
    public function validateAll(Request $request, Admission $admission)
    {
        $this->authorize('lab-results.validate');
        $count = 0;
        foreach ($admission->admissionTests as $admissionTest) {
            if ($admissionTest->result && ! $admissionTest->is_validated) {
                $admissionTest->update([
                    'is_validated' => true,
                    'validated_by' => auth()->id(),
                    'validated_at' => now(),
                ]);
                $count++;
            }
        }

        return redirect()->back()->with('success', "Se validaron {$count} prácticas.");
    }

    /**
     * Sincronizar determinaciones hijas de las prácticas de una admisión
     * Agrega las determinaciones faltantes que son hijas de las prácticas existentes
     */
    public function syncChildTests(Admission $admission)
    {
        $count = 0;

        foreach ($admission->admissionTests as $admissionTest) {
            $test = $admissionTest->test;
            $children = $test->getAllChildren(false);

            foreach ($children as $childTest) {
                $exists = AdmissionTest::where('admission_id', $admission->id)
                    ->where('test_id', $childTest->id)
                    ->exists();

                if (! $exists) {
                    AdmissionTest::create([
                        'admission_id' => $admission->id,
                        'test_id' => $childTest->id,
                        'price' => 0,
                        'nbu_units' => $childTest->nbu ?? 0,
                        'authorization_status' => 'not_required',
                        'paid_by_patient' => false,
                        'copago' => 0,
                    ]);
                    $count++;
                }
            }
        }

        return redirect()->back()->with('success', "Se sincronizaron {$count} determinaciones.");
    }

    public function downloadPdf(Admission $admission)
    {
        $this->authorize('lab-admissions.show');

        $validatedCount = $admission->admissionTests()->where('is_validated', true)->count();
        if ($validatedCount === 0) {
            return back()->with('error', 'Debe validar al menos una determinación para descargar el informe.');
        }

        $admission->load([
            'patient',
            'insuranceRelation',
            'admissionTests' => fn ($q) => $q->where('is_validated', true),
            'admissionTests.test.parentTests',
            'admissionTests.test.childTests',
            'admissionTests.test.referenceValues.category',
            'creator',
        ]);

        $validatorId = $admission->admissionTests
            ->pluck('validated_by')
            ->countBy()->sortDesc()->keys()->first();
        $validator = $validatorId ? \App\Models\User::find($validatorId) : null;

        $pdf = PDF::loadView('lab.admissions.pdf-mpdf', compact('admission', 'validator'), [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        return $pdf->download($this->generatePdfFilename($admission));
    }

    public function viewPdf(Admission $admission)
    {
        $this->authorize('lab-admissions.show');

        $validatedCount = $admission->admissionTests()->where('is_validated', true)->count();
        if ($validatedCount === 0) {
            return back()->with('error', 'Debe validar al menos una determinación para ver el informe.');
        }

        $admission->load([
            'patient',
            'insuranceRelation',
            'admissionTests' => fn ($q) => $q->where('is_validated', true),
            'admissionTests.test.parentTests',
            'admissionTests.test.childTests',
            'admissionTests.test.referenceValues.category',
            'creator',
        ]);

        $validatorId = $admission->admissionTests
            ->pluck('validated_by')
            ->countBy()->sortDesc()->keys()->first();
        $validator = $validatorId ? \App\Models\User::find($validatorId) : null;

        $pdf = PDF::loadView('lab.admissions.pdf-mpdf', compact('admission', 'validator'), [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        return $pdf->stream($this->generatePdfFilename($admission));
    }

    public function sendEmail(Request $request, Admission $admission)
    {
        $this->authorize('lab-admissions.show');

        $validatedCount = $admission->admissionTests()->where('is_validated', true)->count();
        if ($validatedCount === 0) {
            return back()->with('error', 'Debe validar al menos una determinación para enviar el informe.');
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string',
        ]);

        $fromEmail = LabSetting::get('results_email', config('mail.from.address'));
        $fromName = LabSetting::get('results_from_name', config('mail.from.name'));

        Mail::mailer('smtp')
            ->to($validated['email'])
            ->send(
                (new AdmissionResultMail($admission, $validated['message'] ?? null))
                    ->from($fromEmail, $fromName)
            );

        return back()->with('success', 'Informe enviado correctamente a '.$validated['email']);
    }

    private function generatePdfFilename(Admission $admission): string
    {
        $parts = [
            'LabClinico',
            $admission->patient?->name ?? 'SinPaciente',
            $admission->date ? $admission->date->format('Y-m-d') : now()->format('Y-m-d'),
        ];

        $sanitized = collect($parts)->map(function ($part) {
            $clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $part);
            $clean = preg_replace('/[^A-Za-z0-9_-]/', '_', $clean);
            $clean = preg_replace('/_+/', '_', $clean);

            return trim($clean, '_');
        })->implode('-');

        return $sanitized.'.'.$admission->protocol_number.'.pdf';
    }
}
