<?php

namespace App\Http\Controllers;

use App\Enums\DeterminationProfileLabType;
use App\Http\Controllers\Concerns\AppliesProtocolIndexFilters;
use App\Http\Controllers\Concerns\FiltersLabelsByMaterialsQuery;
use App\Mail\AdmissionBatchMail;
use App\Mail\AdmissionResultMail;
use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Customer;
use App\Models\DeterminationProfile;
use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\LabSetting;
use App\Models\Patient;
use App\Models\Test;
use App\Support\ClinicalAdmissionTestHierarchy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PDF;

class LabAdmissionController extends Controller
{
    use AppliesProtocolIndexFilters, FiltersLabelsByMaterialsQuery;

    /**
     * Listado de admisiones
     */
    public function index(Request $request)
    {
        $this->authorize('lab-admissions.index');
        $query = Admission::with(['patient', 'insuranceRelation', 'admissionTests', 'labBranch', 'invoiceProtocols'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        $this->applyLabAdmissionIndexFilters($request, $query);

        $admissions = $query->paginate(20)->withQueryString();
        $insurances = Insurance::where('type', '!=', 'nomenclador')
            ->orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        $branches = \App\Models\LabBranch::active()->orderByDesc('is_central')->orderBy('name')->get();

        return view('lab.admissions.index', compact('admissions', 'insurances', 'branches'));
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
        $branches = \App\Models\LabBranch::active()->orderByDesc('is_central')->orderBy('name')->get();
        $clinicalProfiles = DeterminationProfile::active()
            ->forLabType(DeterminationProfileLabType::Clinico)
            ->orderBy('name')
            ->get(['id', 'name']);

        $coveragePickerItems = $this->labAdmissionCoveragePickerItems($insurances);

        return view('lab.admissions.create', compact('patient', 'insurances', 'coveragePickerItems', 'tests', 'branches', 'clinicalProfiles'));
    }

    /**
     * Opciones del buscador de cobertura: obras sociales + variantes de nombre desde clientes
     * (mismo CUIT o mismo nombre) y clientes activos sin vínculo para mostrar aviso.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Insurance>  $insurances
     * @return list<array<string, mixed>>
     */
    protected function labAdmissionCoveragePickerItems($insurances): array
    {
        $customerTypes = ['obra_social', 'laborales', 'clinico'];
        $customers = Customer::query()
            ->where('status', 'activo')
            ->where(function ($q) use ($customerTypes) {
                foreach ($customerTypes as $t) {
                    $q->orWhereJsonContains('type', $t);
                }
            })
            ->get(['id', 'name', 'taxId']);

        $linkedInsuranceIdByCustomerId = [];
        foreach ($customers as $customer) {
            $linkedInsuranceIdByCustomerId[$customer->id] = $this->resolveCustomerToInsuranceId($customer, $insurances);
        }

        $variantsByInsuranceId = [];
        foreach ($insurances as $ins) {
            $strings = [$ins->name, mb_strtoupper((string) $ins->name)];
            if ($ins->short_name) {
                $strings[] = $ins->short_name;
                $strings[] = mb_strtoupper((string) $ins->short_name);
            }
            $variantsByInsuranceId[$ins->id] = $this->searchVariantsForStrings($strings);
        }

        foreach ($customers as $customer) {
            $iid = $linkedInsuranceIdByCustomerId[$customer->id];
            if ($iid !== null && isset($variantsByInsuranceId[$iid])) {
                foreach ($this->searchVariantsForStrings([$customer->name]) as $v) {
                    $variantsByInsuranceId[$iid][] = $v;
                }
                $variantsByInsuranceId[$iid] = array_values(array_unique($variantsByInsuranceId[$iid]));
            }
        }

        $pickerItems = [];
        foreach ($insurances as $ins) {
            $pickerItems[] = [
                'kind' => 'insurance',
                'id' => $ins->id,
                'name' => mb_strtoupper((string) $ins->displayName()),
                'type' => $ins->type,
                'variants' => $variantsByInsuranceId[$ins->id] ?? [],
            ];
        }

        foreach ($customers as $customer) {
            if ($linkedInsuranceIdByCustomerId[$customer->id] !== null) {
                continue;
            }
            $pickerItems[] = [
                'kind' => 'unlinked_customer',
                'customer_id' => $customer->id,
                'name' => mb_strtoupper((string) $customer->name),
                'tax_id' => $customer->taxId,
                'variants' => $this->searchVariantsForStrings([$customer->name, $customer->taxId ?? '']),
            ];
        }

        return $pickerItems;
    }

    /**
     * Resuelve un cliente de facturación a una obra social del laboratorio por CUIT o nombre exacto (normalizado).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Insurance>  $insurances
     */
    protected function resolveCustomerToInsuranceId(Customer $customer, $insurances): ?int
    {
        $cuit = $this->normalizeTaxDigits($customer->taxId ?? '');
        if ($cuit !== '') {
            foreach ($insurances as $ins) {
                if ($this->normalizeTaxDigits($ins->tax_id ?? '') === $cuit) {
                    return $ins->id;
                }
            }
        }
        $nName = $this->normalizeComparableName($customer->name);
        if ($nName !== '') {
            foreach ($insurances as $ins) {
                if ($this->normalizeComparableName($ins->name) === $nName) {
                    return $ins->id;
                }
            }
        }

        return null;
    }

    protected function normalizeTaxDigits(?string $tax): string
    {
        if ($tax === null || $tax === '') {
            return '';
        }

        return preg_replace('/\D/', '', $tax) ?? '';
    }

    protected function normalizeComparableName(?string $name): string
    {
        if ($name === null || $name === '') {
            return '';
        }
        $collapsed = preg_replace('/\s+/', ' ', trim($name));

        return mb_strtoupper($collapsed);
    }

    /**
     * @return list<string>
     */
    protected function searchVariantsForStrings(array $strings): array
    {
        $out = [];
        foreach ($strings as $s) {
            if ($s === null || $s === '') {
                continue;
            }
            $out[] = (string) $s;
            $out[] = mb_strtoupper((string) $s);
            $out[] = mb_strtolower((string) $s);
        }

        return array_values(array_unique($out));
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
            'lab_branch_id' => 'nullable|exists:lab_branches,id',
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
            'lab_branch_id' => $request->lab_branch_id,
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

        $admission->logAudit('created', 'Creó la admisión Nº '.$admission->protocol_number.' para '.$admission->patient->full_name);

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
            'admissionTests.test.materialRelation',
            'admissionTests.test.referenceValues.category',
            'creator',
            'auditLogs',
            'determinationProfileApplications.user',
        ]);

        // Tests disponibles para agregar al protocolo
        $availableTests = Test::whereNull('parent')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'nbu', 'price']);

        $clinicalProfiles = DeterminationProfile::active()
            ->forLabType(DeterminationProfileLabType::Clinico)
            ->orderBy('name')
            ->get(['id', 'name']);

        $isRecepcionLab = auth()->user()->hasRole('recepcion-lab')
            && ! auth()->user()->hasAnyRole(['bioquimico', 'tecnico-lab']);

        return view('lab.admissions.show', compact('admission', 'availableTests', 'clinicalProfiles', 'isRecepcionLab'));
    }

    /**
     * Siguiente protocolo clínico elegible (no validado, no enviado) según filtros vivos del listado y orden ascendente de protocol_number.
     */
    public function nextPendingAdmission(Request $request, Admission $admission)
    {
        $this->authorize('lab-admissions.show');

        $nav = $this->labAdmissionNavigationQuery($request);

        $query = Admission::query();
        $this->applyLabAdmissionIndexFilters($request, $query);
        $query->where('status', '!=', Admission::STATUS_VALIDATED)
            ->whereNull('sent_at')
            ->where('status', '!=', Admission::STATUS_CANCELLED);

        $next = (clone $query)
            ->where('protocol_number', '>', $admission->protocol_number)
            ->orderBy('protocol_number')
            ->orderBy('id')
            ->first();

        if (! $next) {
            return redirect()->route('lab.admissions.index', $nav)
                ->with('warning', 'No hay siguiente protocolo pendiente (sin validar ni enviar) con estos filtros.');
        }

        return redirect()->route('lab.admissions.show', array_merge(['admission' => $next], $nav));
    }

    /**
     * Protocolo clínico pendiente anterior (mismos filtros; protocol_number descendente respecto del actual).
     */
    public function previousPendingAdmission(Request $request, Admission $admission)
    {
        $this->authorize('lab-admissions.show');

        $nav = $this->labAdmissionNavigationQuery($request);

        $query = Admission::query();
        $this->applyLabAdmissionIndexFilters($request, $query);
        $query->where('status', '!=', Admission::STATUS_VALIDATED)
            ->whereNull('sent_at')
            ->where('status', '!=', Admission::STATUS_CANCELLED);

        $previous = (clone $query)
            ->where('protocol_number', '<', $admission->protocol_number)
            ->orderByDesc('protocol_number')
            ->orderByDesc('id')
            ->first();

        if (! $previous) {
            return redirect()->route('lab.admissions.index', $nav)
                ->with('warning', 'No hay anterior protocolo pendiente (sin validar ni enviar) con estos filtros.');
        }

        return redirect()->route('lab.admissions.show', array_merge(['admission' => $previous], $nav));
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
        $branches = \App\Models\LabBranch::active()->orderByDesc('is_central')->orderBy('name')->get();

        return view('lab.admissions.edit', compact('admission', 'insurances', 'tests', 'branches'));
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
            'lab_branch_id' => 'nullable|exists:lab_branches,id',
        ]);

        $oldBranchId = $admission->lab_branch_id;

        $admission->update([
            'date' => $request->date,
            'insurance' => $request->insurance_id,
            'affiliate_number' => $request->affiliate_number,
            'requesting_doctor' => $request->requesting_doctor,
            'diagnosis' => $request->diagnosis,
            'observations' => $request->observations,
            'lab_branch_id' => $request->lab_branch_id,
        ]);

        $auditMsg = 'Editó la admisión Nº '.$admission->protocol_number;
        if ((string) $oldBranchId !== (string) $request->lab_branch_id) {
            $oldName = $oldBranchId ? (\App\Models\LabBranch::find($oldBranchId)?->name ?? $oldBranchId) : 'Sin sede';
            $newName = $request->lab_branch_id ? (\App\Models\LabBranch::find($request->lab_branch_id)?->name ?? $request->lab_branch_id) : 'Sin sede';
            $auditMsg .= '. Sede: '.$oldName.' → '.$newName;
        }
        $admission->logAudit('updated', $auditMsg);

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
        $this->authorize('lab-admissions.delete');

        if ($test->admission_id !== $admission->id) {
            abort(404);
        }

        if (auth()->user()->hasRole('recepcion-lab')) {
            if ($test->is_validated || $test->hasResult()) {
                return redirect()->back()->with('error', 'No se puede eliminar una práctica en proceso o validada.');
            }
        }

        if (ClinicalAdmissionTestHierarchy::isProtocolSubParent($admission, $test)) {
            return redirect()->back()->with('error', 'No se puede eliminar un grupo intermedio. Quite solo determinaciones hoja sin resultado.');
        }

        if (ClinicalAdmissionTestHierarchy::isProtocolLeafChild($admission, $test)) {
            if ($test->hasResult() || $test->is_validated || $test->is_ratified) {
                return redirect()->back()->with('error', 'No se puede eliminar esta determinación: tiene resultado, está validada o ratificada.');
            }
            $test->delete();
            $admission->calculateTotals();

            return redirect()->back()->with('success', 'Determinación eliminada del protocolo (el grupo permanece).');
        }

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

        $tests = Test::where(function ($query) use ($search) {
            $query->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        })
            ->with(['parentTests', 'parentTest'])
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
                    $test->calculated_price = $insuranceTest->price ?: ($insuranceTest->nbu_units * ($insurance->nbu_value ?? 0));
                    $test->requires_authorization = $insuranceTest->requires_authorization;
                    $test->copago = $insuranceTest->copago;
                    $test->in_nomenclator = true;
                } elseif ($insurance->nomenclator_id) {
                    $baseItem = InsuranceTest::where('insurance_id', $insurance->nomenclator_id)
                        ->where('test_id', $test->id)
                        ->first();

                    if ($baseItem) {
                        $test->calculated_price = $baseItem->nbu_units * ($insurance->nbu_value ?? 0);
                        $test->requires_authorization = $baseItem->requires_authorization ?? false;
                        $test->copago = $baseItem->copago ?? 0;
                        $test->in_nomenclator = true;
                    } else {
                        $test->calculated_price = ($test->nbu ?? 1) * ($insurance->nbu_value ?? 0);
                        $test->requires_authorization = false;
                        $test->copago = 0;
                        $test->in_nomenclator = false;
                    }
                } else {
                    $test->calculated_price = ($test->nbu ?? 1) * ($insurance->nbu_value ?? 0);
                    $test->requires_authorization = false;
                    $test->copago = 0;
                    $test->in_nomenclator = false;
                }

                $test->parent_name = $test->parentTests->first()?->name
                    ?? $test->parentTest?->name;

                return $test;
            });
        } else {
            $tests = $tests->map(function ($test) {
                $test->parent_name = $test->parentTests->first()?->name
                    ?? $test->parentTest?->name;

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
            'is_ratified' => 'nullable|boolean',
        ]);

        $update = [];
        if (! $admissionTest->is_validated) {
            $update['result'] = $request->result;
            $update['unit'] = $request->unit;
            $update['reference_value'] = $request->reference_value;
        }

        if ($request->has('is_ratified') && auth()->user()->can('lab-results.validate')) {
            $this->applyRatifiedFlag($update, $request->boolean('is_ratified'));
        }

        if (! empty($update)) {
            $admissionTest->update($update);
        }

        $admission->load('admissionTests');
        $admission->update(['status' => $admission->calculated_status]);

        return redirect()->back()->with('success', 'Resultado guardado correctamente.');
    }

    /**
     * Mezcla los campos de ratificación según el valor del checkbox.
     */
    private function applyRatifiedFlag(array &$payload, bool $ratified): void
    {
        if ($ratified) {
            $payload['is_ratified'] = true;
            $payload['ratified_at'] = now();
            $payload['ratified_by'] = auth()->id();
        } else {
            $payload['is_ratified'] = false;
            $payload['ratified_at'] = null;
            $payload['ratified_by'] = null;
        }
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
            'results.*.is_ratified' => 'nullable|boolean',
        ]);

        $canRatify = auth()->user()->can('lab-results.validate');

        foreach ($request->results as $data) {
            $admissionTest = AdmissionTest::find($data['id']);
            if (! $admissionTest || $admissionTest->admission_id !== $admission->id) {
                continue;
            }

            $update = [];
            if (! $admissionTest->is_validated) {
                $update['result'] = $data['result'] ?? null;
                $update['unit'] = $data['unit'] ?? null;
                $update['reference_value'] = $data['reference_value'] ?? null;
            }

            if ($canRatify && array_key_exists('is_ratified', $data)) {
                $this->applyRatifiedFlag($update, filter_var($data['is_ratified'], FILTER_VALIDATE_BOOLEAN));
            }

            if (! empty($update)) {
                $admissionTest->update($update);
            }
        }

        $admission->logAudit('results_loaded', 'Cargó resultados en la admisión Nº '.$admission->protocol_number);

        $admission->load('admissionTests');
        $admission->update(['status' => $admission->calculated_status]);

        return redirect()->back()->with('success', 'Resultados guardados correctamente.');
    }

    /**
     * Validar una práctica
     */
    public function validateTest(Request $request, Admission $admission, AdmissionTest $admissionTest)
    {
        $this->authorize('lab-results.validate');
        if (! $admissionTest->hasResult()) {
            return redirect()->back()->with('error', 'No se puede validar una práctica sin resultado.');
        }

        $admissionTest->update([
            'is_validated' => true,
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        $admission->logAudit('validated', 'Validó práctica '.$admissionTest->test->name.' en admisión Nº '.$admission->protocol_number);

        $admission->load('admissionTests');
        $admission->update(['status' => $admission->calculated_status]);

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
            'is_ratified' => false,
            'ratified_at' => null,
            'ratified_by' => null,
        ]);

        $admission->logAudit('unvalidated', 'Desvalidó práctica '.$admissionTest->test->name.' en admisión Nº '.$admission->protocol_number);

        $admission->load('admissionTests');
        $admission->update(['status' => $admission->calculated_status]);

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
            if ($admissionTest->hasResult() && ! $admissionTest->is_validated) {
                $admissionTest->update([
                    'is_validated' => true,
                    'validated_by' => auth()->id(),
                    'validated_at' => now(),
                ]);
                $count++;
            }
        }

        $admission->logAudit('validated', "Validó {$count} prácticas en admisión Nº ".$admission->protocol_number);

        $admission->load('admissionTests');
        $admission->update(['status' => $admission->calculated_status]);

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

    public function destroy(Admission $admission)
    {
        $this->authorize('lab-admissions.delete');

        if (auth()->user()->hasRole('recepcion-lab')) {
            $hasInProcess = $admission->admissionTests->contains(fn ($t) => $t->is_validated || $t->hasResult());
            if ($hasInProcess) {
                return redirect()->back()
                    ->with('error', 'Solo se puede eliminar el protocolo si todas las prácticas están pendientes.');
            }
        }

        $protocolNumber = $admission->protocol_number ?? $admission->id;
        $admission->logAudit('deleted', "Protocolo clínico #{$protocolNumber} eliminado por ".auth()->user()->name);
        $admission->delete();

        return redirect()->route('lab.admissions.index')
            ->with('success', "Protocolo #{$protocolNumber} eliminado correctamente.");
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
            'admissionTests.test.parentTests',
            'admissionTests.test.childTests',
            'admissionTests.test.referenceValues.category',
            'creator',
        ]);

        $validatorId = $admission->admissionTests
            ->where('is_validated', true)
            ->pluck('validated_by')
            ->countBy()->sortDesc()->keys()->first();
        $validator = $validatorId ? \App\Models\User::find($validatorId) : null;

        $pdf = PDF::loadView('lab.admissions.pdf-mpdf', compact('admission', 'validator'), [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        $admission->logAudit('pdf_generated', 'Generó PDF del protocolo Nº '.$admission->protocol_number);

        if ($admission->status === Admission::STATUS_VALIDATED) {
            $admission->update(['sent_at' => now()]);
        }

        return $pdf->download(AdmissionResultMail::generatePdfFilename($admission));
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
            'admissionTests.test.parentTests',
            'admissionTests.test.childTests',
            'admissionTests.test.referenceValues.category',
            'creator',
        ]);

        $validatorId = $admission->admissionTests
            ->where('is_validated', true)
            ->pluck('validated_by')
            ->countBy()->sortDesc()->keys()->first();
        $validator = $validatorId ? \App\Models\User::find($validatorId) : null;

        $pdf = PDF::loadView('lab.admissions.pdf-mpdf', compact('admission', 'validator'), [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        $admission->logAudit('pdf_generated', 'Visualizó PDF del protocolo Nº '.$admission->protocol_number);

        return $pdf->stream(AdmissionResultMail::generatePdfFilename($admission));
    }

    /**
     * Envío masivo: un correo con N PDFs de protocolos con al menos una determinación validada.
     */
    public function batchEmail(Request $request)
    {
        $this->authorize('lab-admissions.show');

        $validated = $request->validate([
            'admission_ids' => 'required|array|min:1',
            'admission_ids.*' => 'integer|exists:admissions,id',
            'email' => 'required|email',
            'message' => 'nullable|string',
        ]);

        $fromEmail = LabSetting::get('results_email', config('mail.from.address'));
        $fromName = LabSetting::get('results_from_name', config('mail.from.name'));

        $query = Admission::with(['patient', 'insuranceRelation', 'admissionTests.test'])
            ->whereIn('id', $validated['admission_ids']);

        if ($activeBranch = active_lab_branch_id()) {
            $query->where(function ($q) use ($activeBranch) {
                $q->where('lab_branch_id', $activeBranch)
                    ->orWhereNull('lab_branch_id');
            });
        }

        $admissions = $query->get();

        $requestedIds = collect($validated['admission_ids']);
        $foundIds = $admissions->pluck('id');
        $missingIds = $requestedIds->diff($foundIds);

        $results = [
            'sent' => [],
            'skipped' => [],
            'errors' => [],
        ];

        foreach ($missingIds as $mid) {
            $orphan = Admission::find($mid);
            $results['skipped'][] = ($orphan?->protocol_number ?? "#{$mid}").' (sin acceso o sede)';
        }

        $eligible = $admissions->filter(fn (Admission $a) => $this->admissionCanReceiveResultsEmail($a));
        $ineligible = $admissions->filter(fn (Admission $a) => ! $this->admissionCanReceiveResultsEmail($a));

        foreach ($ineligible as $a) {
            $results['skipped'][] = $a->protocol_number.' (sin determinaciones validadas)';
        }

        if ($eligible->isEmpty()) {
            $results['errors'][] = 'No hay protocolos válidos para enviar.';

            return response()->json($results);
        }

        try {
            Mail::mailer('smtp')
                ->to($validated['email'])
                ->send(
                    (new AdmissionBatchMail($eligible->values(), $validated['message'] ?? null))
                        ->from($fromEmail, $fromName)
                );

            foreach ($eligible as $adm) {
                $adm->update(['sent_at' => now()]);
                $adm->logAudit('email_sent', 'Enviado en lote masivo a '.$validated['email']);
                $results['sent'][] = $adm->protocol_number;
            }
        } catch (\Throwable $e) {
            foreach ($eligible as $adm) {
                $results['errors'][] = $adm->protocol_number.' (error: '.$e->getMessage().')';
            }
        }

        return response()->json($results);
    }

    /**
     * Misma regla que {@see sendEmail}: al menos una determinación validada.
     */
    private function admissionCanReceiveResultsEmail(Admission $admission): bool
    {
        return $admission->admissionTests->where('is_validated', true)->count() > 0;
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

        $admission->logAudit('email_sent', 'Envió resultados por email a '.$validated['email']);

        $admission->update(['sent_at' => now()]);

        return back()->with('success', 'Informe enviado correctamente a '.$validated['email']);
    }

    public function labelData(Admission $admission)
    {
        $this->authorize('lab-labels.print');

        $admission->load(['patient', 'admissionTests.test.materialRelation', 'admissionTests.test.parentTests', 'labBranch']);

        $labels = $this->groupByMaterial($admission);

        return response()->json([
            'labels' => $labels,
            'total_labels' => count($labels),
        ]);
    }

    public function printLabel(Admission $admission)
    {
        $this->authorize('lab-labels.print');

        $admission->load(['patient', 'admissionTests.test.materialRelation', 'admissionTests.test.parentTests', 'labBranch']);

        $labels = $this->groupByMaterial($admission);
        $labels = $this->filterLabelsByMaterialsQuery($labels);

        $barcode = new \Picqer\Barcode\BarcodeGeneratorSVG;

        foreach ($labels as &$label) {
            $content = \App\Services\BarcodeFormatService::forLabel(
                $admission->protocol_number,
                $label['material'] ?? null
            );

            $label['barcode_content'] = $content;
            $label['barcode_svg'] = $barcode->getBarcode(
                $content,
                $barcode::TYPE_CODE_128,
                2,
                60
            );
        }
        unset($label);

        return view('lab.admissions.label', [
            'admission' => $admission,
            'labels' => $labels,
        ]);
    }

    private function groupByMaterial(Admission $admission): array
    {
        $parentTestIds = $admission->admissionTests->pluck('test_id')->toArray();
        $materialGroups = [];

        foreach ($admission->admissionTests as $at) {
            $test = $at->test;
            if (! $test || ! $test->materialRelation) {
                continue;
            }

            $isChild = $test->parentTests->whereIn('id', $parentTestIds)->isNotEmpty();
            if ($isChild) {
                continue;
            }

            $materialId = $test->materialRelation->id;
            if (! isset($materialGroups[$materialId])) {
                $materialGroups[$materialId] = [
                    'material_name' => $test->materialRelation->name,
                    'material_code' => $test->material_abbreviation,
                    'tests' => [],
                ];
            }
            $materialGroups[$materialId]['tests'][] = $test->name;
        }

        $branchName = $admission->labBranch?->name;
        $labels = [];
        foreach ($materialGroups as $materialId => $group) {
            $labels[] = [
                'material_key' => (string) $materialId,
                'protocol_number' => $admission->protocol_number,
                'customer_name' => $admission->patient?->full_name ?? 'Sin paciente',
                'material' => $group['material_code'],
                'material_name' => $group['material_name'],
                'sample_type' => 'CLINICO',
                'entry_date' => $admission->date?->format('d/m/Y') ?? '',
                'tests_count' => count($group['tests']),
                'branch_name' => $branchName,
            ];
        }

        if (empty($labels)) {
            $labels[] = [
                'material_key' => 'unknown',
                'protocol_number' => $admission->protocol_number,
                'customer_name' => $admission->patient?->full_name ?? 'Sin paciente',
                'material' => '?',
                'material_name' => 'Sin material',
                'sample_type' => 'CLINICO',
                'entry_date' => $admission->date?->format('d/m/Y') ?? '',
                'tests_count' => $admission->admissionTests->count(),
                'branch_name' => $branchName,
            ];
        }

        return $labels;
    }
}
