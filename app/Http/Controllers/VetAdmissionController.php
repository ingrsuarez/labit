<?php

namespace App\Http\Controllers;

use App\Mail\VetAdmissionResultMail;
use App\Models\Customer;
use App\Models\LabSetting;
use App\Models\Species;
use App\Models\Test;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use PDF;

class VetAdmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = VetAdmission::with(['customer', 'veterinarian', 'species', 'vetTests', 'labBranch', 'invoiceProtocols'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('protocol_number', 'like', "%{$search}%")
                    ->orWhere('animal_name', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%")
                    ->orWhere('breed', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('veterinarian', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('species_id')) {
            $query->where('species_id', $request->species_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('owner')) {
            $query->where('owner_name', 'like', '%'.$request->owner.'%');
        }

        if ($request->filled('animal')) {
            $query->where('animal_name', 'like', '%'.$request->animal.'%');
        }

        if ($request->filled('lab_branch_id')) {
            if ($request->lab_branch_id === 'all') {
                // No filtrar — se ven todos
            } elseif ($request->lab_branch_id === 'none') {
                $query->whereNull('lab_branch_id');
            } else {
                $query->where('lab_branch_id', $request->lab_branch_id);
            }
        } elseif ($activeBranch = active_lab_branch_id()) {
            $query->where(function ($q) use ($activeBranch) {
                $q->where('lab_branch_id', $activeBranch)
                    ->orWhereNull('lab_branch_id');
            });
        }

        $admissions = $query->paginate(20)->withQueryString();
        $species = Species::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::whereJsonContains('type', 'veterinario')->orderBy('name')->get();
        $branches = \App\Models\LabBranch::active()->orderByDesc('is_central')->orderBy('name')->get();

        return view('vet.admissions.index', compact('admissions', 'species', 'customers', 'branches'));
    }

    public function create()
    {
        $customers = Customer::whereJsonContains('type', 'veterinario')->orderBy('name')->get();
        $species = Species::where('is_active', true)->orderBy('name')->get();
        $branches = \App\Models\LabBranch::active()->orderByDesc('is_central')->orderBy('name')->get();
        $customerNbuValues = $customers->mapWithKeys(fn (Customer $c) => [
            $c->id => (float) $c->veterinaryNbuRate(),
        ])->all();

        return view('vet.admissions.create', compact('customers', 'species', 'branches', 'customerNbuValues'));
    }

    public function searchTests(Request $request)
    {
        $query = $request->get('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $customerId = $request->integer('customer_id');
        if ($customerId <= 0) {
            return response()->json(['error' => 'Seleccioná primero la veterinaria.'], 422);
        }

        $customer = Customer::find($customerId);
        if (! $customer || ! $customer->isVeterinary()) {
            return response()->json(['error' => 'Cliente veterinario no válido.'], 422);
        }

        $rate = $customer->veterinaryNbuRate();

        $tests = Test::where(function ($q) use ($query) {
            $q->where('code', 'like', "%{$query}%")
                ->orWhere('name', 'like', "%{$query}%");
        })
            ->whereJsonContains('categories', 'veterinario')
            ->whereDoesntHave('parentTests')
            ->limit(20)
            ->get(['id', 'code', 'name', 'nbu']);

        return response()->json($tests->map(function (Test $test) use ($rate) {
            $nbu = (float) ($test->nbu ?? 0);

            return [
                'id' => $test->id,
                'code' => $test->code,
                'name' => $test->name,
                'nbu' => $nbu,
                'price' => self::veterinaryPriceFromNbu($rate, $nbu),
            ];
        }));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'veterinarian_id' => 'nullable|exists:veterinarians,id',
            'species_id' => 'required|exists:species,id',
            'animal_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'owner_phone' => 'nullable|string|max:50',
            'owner_email' => 'nullable|email|max:255',
            'breed' => 'nullable|string|max:100',
            'age' => 'nullable|string|max:50',
            'date' => 'required|date',
            'observations' => 'nullable|string',
            'tests' => 'required|array|min:1',
            'tests.*.test_id' => 'required|exists:tests,id',
            'tests.*.price' => 'nullable|numeric|min:0',
            'lab_branch_id' => 'nullable|exists:lab_branches,id',
        ]);

        $admission = VetAdmission::create([
            'protocol_number' => VetAdmission::generateProtocolNumber(),
            'date' => $request->date,
            'customer_id' => $request->customer_id,
            'veterinarian_id' => $request->veterinarian_id ?: null,
            'species_id' => $request->species_id,
            'animal_name' => $request->animal_name,
            'owner_name' => $request->owner_name,
            'owner_phone' => $request->owner_phone,
            'owner_email' => $request->owner_email,
            'breed' => $request->breed,
            'age' => $request->age,
            'observations' => $request->observations,
            'created_by' => auth()->id(),
            'lab_branch_id' => $request->lab_branch_id,
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        if (! $customer->isVeterinary()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['customer_id' => 'El cliente debe ser una veterinaria.']);
        }

        $rate = $customer->veterinaryNbuRate();

        $totalPrice = 0;

        foreach ($request->tests as $testData) {
            $test = Test::find($testData['test_id']);
            $nbu = (float) ($test->nbu ?? 0);
            $price = self::veterinaryPriceFromNbu($rate, $nbu);
            $totalPrice += $price;

            VetAdmissionTest::create([
                'vet_admission_id' => $admission->id,
                'test_id' => $test->id,
                'price' => $price,
                'nbu_units' => $test->nbu ?? 1,
                'unit' => $test->unit,
                'method' => $test->method,
                'reference_value' => $this->buildReferenceValue($test, $request->species_id),
            ]);

            // Expand children
            $children = $test->getAllChildren(false);
            foreach ($children as $childTest) {
                $exists = VetAdmissionTest::where('vet_admission_id', $admission->id)
                    ->where('test_id', $childTest->id)
                    ->exists();

                if (! $exists) {
                    VetAdmissionTest::create([
                        'vet_admission_id' => $admission->id,
                        'test_id' => $childTest->id,
                        'price' => 0,
                        'nbu_units' => $childTest->nbu ?? 0,
                        'unit' => $childTest->unit,
                        'method' => $childTest->method,
                        'reference_value' => $this->buildReferenceValue($childTest, $request->species_id),
                    ]);
                }
            }
        }

        $admission->update(['total_price' => $totalPrice]);

        return redirect()->route('vet.admissions.show', $admission)
            ->with('success', 'Protocolo veterinario creado. Nº '.$admission->protocol_number);
    }

    public function show(VetAdmission $vetAdmission)
    {
        $vetAdmission->load([
            'customer', 'veterinarian', 'species',
            'vetTests.test.parentTests',
            'vetTests.test.materialRelation',
            'creator', 'labBranch',
        ]);

        return view('vet.admissions.show', compact('vetAdmission'));
    }

    public function loadResults(Request $request, VetAdmission $vetAdmission)
    {
        $request->validate([
            'results' => 'required|array',
            'results.*.id' => 'required|exists:vet_admission_tests,id',
            'results.*.result' => 'nullable|string|max:255',
            'results.*.unit' => 'nullable|string|max:50',
            'results.*.reference_value' => 'nullable|string|max:255',
            'results.*.method' => 'nullable|string|max:255',
        ]);

        foreach ($request->results as $data) {
            $vat = VetAdmissionTest::find($data['id']);
            if ($vat && $vat->vet_admission_id === $vetAdmission->id && ! $vat->is_validated) {
                $vat->update([
                    'result' => $data['result'] ?? null,
                    'unit' => $data['unit'] ?? null,
                    'reference_value' => $data['reference_value'] ?? null,
                    'method' => $data['method'] ?? null,
                    'status' => ! empty($data['result']) ? 'completed' : 'pending',
                    'analyzed_by' => ! empty($data['result']) ? auth()->id() : null,
                    'analyzed_at' => ! empty($data['result']) ? now() : null,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Resultados guardados correctamente.');
    }

    public function validateTest(VetAdmission $vetAdmission, VetAdmissionTest $vetAdmissionTest)
    {
        if (! $vetAdmissionTest->hasResult()) {
            return redirect()->back()->with('error', 'No se puede validar sin resultado.');
        }

        $vetAdmissionTest->update([
            'is_validated' => true,
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Práctica validada.');
    }

    public function unvalidateTest(VetAdmission $vetAdmission, VetAdmissionTest $vetAdmissionTest)
    {
        $vetAdmissionTest->update([
            'is_validated' => false,
            'validated_by' => null,
            'validated_at' => null,
        ]);

        return redirect()->back()->with('success', 'Validación removida.');
    }

    public function validateAll(VetAdmission $vetAdmission)
    {
        $count = 0;
        foreach (\App\Support\VetAdmissionTestDisplayOrder::orderedEntries($vetAdmission, false) as $entry) {
            $vat = $entry['vt'];
            if ($vat->hasResult() && ! $vat->is_validated) {
                $vat->update([
                    'is_validated' => true,
                    'validated_by' => auth()->id(),
                    'validated_at' => now(),
                ]);
                $count++;
            }
        }

        return redirect()->back()->with('success', "Se validaron {$count} prácticas.");
    }

    public function getVeterinarians(Customer $customer)
    {
        return response()->json(
            $customer->veterinarians()->where('is_active', true)->get(['id', 'name', 'matricula'])
        );
    }

    public function downloadPdf(VetAdmission $vetAdmission)
    {
        $validatedCount = $vetAdmission->vetTests()->where('is_validated', true)->count();
        if ($validatedCount === 0) {
            return back()->with('error', 'Debe validar al menos una determinación para descargar el informe.');
        }

        $vetAdmission->load([
            'customer', 'veterinarian', 'species',
            'vetTests.test.parentTests', 'vetTests.test.childTests',
        ]);

        $validatorId = $vetAdmission->vetTests
            ->where('is_validated', true)
            ->pluck('validated_by')
            ->countBy()->sortDesc()->keys()->first();
        $validator = $validatorId ? \App\Models\User::find($validatorId) : null;

        $pdf = PDF::loadView('vet.admissions.pdf-mpdf', compact('vetAdmission', 'validator'), [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        return $pdf->download($this->generatePdfFilename($vetAdmission));
    }

    public function viewPdf(VetAdmission $vetAdmission)
    {
        $validatedCount = $vetAdmission->vetTests()->where('is_validated', true)->count();
        if ($validatedCount === 0) {
            return back()->with('error', 'Debe validar al menos una determinación para ver el informe.');
        }

        $vetAdmission->load([
            'customer', 'veterinarian', 'species',
            'vetTests.test.parentTests', 'vetTests.test.childTests',
        ]);

        $validatorId = $vetAdmission->vetTests
            ->where('is_validated', true)
            ->pluck('validated_by')
            ->countBy()->sortDesc()->keys()->first();
        $validator = $validatorId ? \App\Models\User::find($validatorId) : null;

        $pdf = PDF::loadView('vet.admissions.pdf-mpdf', compact('vetAdmission', 'validator'), [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        return $pdf->stream($this->generatePdfFilename($vetAdmission));
    }

    public function sendEmail(Request $request, VetAdmission $vetAdmission)
    {
        $validatedCount = $vetAdmission->vetTests()->where('is_validated', true)->count();
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
                (new VetAdmissionResultMail($vetAdmission, $validated['message'] ?? null))
                    ->from($fromEmail, $fromName)
            );

        return back()->with('success', 'Informe enviado correctamente a '.$validated['email']);
    }

    public function labelData(VetAdmission $vetAdmission)
    {
        $this->authorize('vet-labels.print');

        $vetAdmission->load(['vetTests.test.materialRelation', 'vetTests.test.parentTests', 'labBranch']);

        $labels = $this->groupByMaterial($vetAdmission);

        return response()->json([
            'labels' => $labels,
            'total_labels' => count($labels),
        ]);
    }

    public function printLabel(VetAdmission $vetAdmission)
    {
        $this->authorize('vet-labels.print');

        $vetAdmission->load(['vetTests.test.materialRelation', 'vetTests.test.parentTests', 'labBranch']);

        $labels = $this->groupByMaterial($vetAdmission);

        $barcode = new \Picqer\Barcode\BarcodeGeneratorSVG;

        foreach ($labels as &$label) {
            $content = \App\Services\BarcodeFormatService::forLabel(
                $vetAdmission->protocol_number,
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

        return view('vet.admissions.label', [
            'vetAdmission' => $vetAdmission,
            'labels' => $labels,
        ]);
    }

    private function groupByMaterial(VetAdmission $vetAdmission): array
    {
        $parentTestIds = $vetAdmission->vetTests->pluck('test_id')->toArray();
        $materialGroups = [];

        foreach ($vetAdmission->vetTests as $vt) {
            $test = $vt->test;
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

        $branchName = $vetAdmission->labBranch->name ?? 'VETERINARIO';

        $labels = [];
        foreach ($materialGroups as $group) {
            $labels[] = [
                'protocol_number' => $vetAdmission->protocol_number,
                'customer_name' => $vetAdmission->owner_name ?? 'Sin dueño',
                'material' => $group['material_code'],
                'material_name' => $group['material_name'],
                'sample_type' => 'VETERINARIO',
                'entry_date' => $vetAdmission->date?->format('d/m/Y') ?? '',
                'tests_count' => count($group['tests']),
                'branch_name' => $branchName,
            ];
        }

        if (empty($labels)) {
            $labels[] = [
                'protocol_number' => $vetAdmission->protocol_number,
                'customer_name' => $vetAdmission->owner_name ?? 'Sin dueño',
                'material' => '?',
                'material_name' => 'Sin material',
                'sample_type' => 'VETERINARIO',
                'entry_date' => $vetAdmission->date?->format('d/m/Y') ?? '',
                'tests_count' => $vetAdmission->vetTests->count(),
                'branch_name' => $branchName,
            ];
        }

        return $labels;
    }

    private function generatePdfFilename(VetAdmission $vetAdmission): string
    {
        $parts = [
            'LabVeterinario',
            $vetAdmission->animal_name,
            $vetAdmission->species->name ?? 'SinEspecie',
            $vetAdmission->date ? $vetAdmission->date->format('Y-m-d') : now()->format('Y-m-d'),
        ];

        $sanitized = collect($parts)->map(function ($part) {
            $clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $part);
            $clean = preg_replace('/[^A-Za-z0-9_-]/', '_', $clean);
            $clean = preg_replace('/_+/', '_', $clean);

            return trim($clean, '_');
        })->implode('-');

        return $sanitized.'.'.$vetAdmission->protocol_number.'.pdf';
    }

    /**
     * Resolve reference value for a test given a species.
     * Priority: test_species_references -> test.low/high -> test.other_reference
     */
    private function buildReferenceValue(Test $test, int $speciesId): ?string
    {
        $speciesRef = $test->getReferenceForSpecies($speciesId);
        if ($speciesRef) {
            return $speciesRef->formatted_range;
        }

        if ($test->low || $test->high) {
            $ref = ($test->low ?? '').' - '.($test->high ?? '');
            if ($test->other_reference) {
                $ref .= ' | '.$test->other_reference;
            }

            return trim($ref, ' -');
        }

        if ($test->other_reference) {
            return $test->other_reference;
        }

        return null;
    }

    /**
     * Precio de una práctica veterinaria: valor NBU de la veterinaria × NBU de la determinación.
     */
    public static function veterinaryPriceFromNbu(float $veterinaryNbuRate, float $testNbuUnits): float
    {
        return round($veterinaryNbuRate * $testNbuUnits, 2);
    }
}
