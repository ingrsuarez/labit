<?php

namespace App\Http\Controllers;

use App\Mail\SampleResultMail;
use App\Models\Customer;
use App\Models\LabSetting;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use PDF; // mPDF facade

class SampleController extends Controller
{
    /**
     * Muestra el listado de muestras/protocolos
     */
    public function index(Request $request)
    {
        $this->authorize('samples.index');
        $samples = Sample::with(['customer', 'determinations'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('sample.index', compact('samples'));
    }

    /**
     * Muestra el formulario para crear una nueva muestra
     */
    public function create()
    {
        $this->authorize('samples.create');
        $customers = Customer::orderBy('name')->get();
        $tests = Test::whereJsonContains('categories', 'aguas_alimentos')
            ->orderBy('name')
            ->get();

        return view('sample.create', compact('customers', 'tests'));
    }

    /**
     * Almacena una nueva muestra
     */
    public function store(Request $request)
    {
        $this->authorize('samples.create');
        $validated = $request->validate([
            'sample_type' => 'required|in:agua,alimento,hielo',
            'entry_date' => 'required|date',
            'sampling_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'location' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'batch' => 'nullable|string|max:100',
            'product_name' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'determinations' => 'required|array|min:1',
            'determinations.*' => 'exists:tests,id',
        ]);

        // Generar n?mero de protocolo
        $validated['protocol_number'] = Sample::generateProtocolNumber();
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending';

        // Crear la muestra
        $sample = Sample::create($validated);

        // Calcular descuento del cliente
        $customer = Customer::find($validated['customer_id']);
        $discountPercent = $customer->discount_percent ?? 0;
        $discountMultiplier = 1 - ($discountPercent / 100);

        // Agregar las determinaciones (incluyendo hijos automáticamente)
        foreach ($request->determinations as $testId) {
            $test = Test::with(['children', 'childTests', 'referenceValues'])->find($testId);

            $parentCategoryId = $test->default_reference_category_id;

            $parentRef = $this->buildReferenceValue($test);
            $basePrice = $test->price ?? 0;
            $finalPrice = round($basePrice * $discountMultiplier, 2);

            SampleDetermination::create([
                'sample_id' => $sample->id,
                'test_id' => $testId,
                'price' => $finalPrice,
                'unit' => $test->unit,
                'method' => $test->method,
                'reference_value' => $parentRef['value'],
                'reference_category_id' => $parentRef['category_id'],
                'status' => 'pending',
            ]);

            $allChildren = $test->getAllChildren();
            foreach ($allChildren as $childTest) {
                $exists = $sample->determinations()->where('test_id', $childTest->id)->exists();
                if (! $exists) {
                    $childRef = $this->buildReferenceValue($childTest, $parentCategoryId);
                    SampleDetermination::create([
                        'sample_id' => $sample->id,
                        'test_id' => $childTest->id,
                        'price' => 0,
                        'unit' => $childTest->unit,
                        'method' => $childTest->method,
                        'reference_value' => $childRef['value'],
                        'reference_category_id' => $childRef['category_id'],
                        'status' => 'pending',
                    ]);
                }
            }
        }

        return redirect()->route('sample.show', $sample)
            ->with('success', 'Protocolo '.$sample->protocol_number.' creado correctamente.');
    }

    /**
     * Muestra los detalles de una muestra
     */
    public function show(Sample $sample)
    {
        $this->authorize('samples.show');
        $sample->load([
            'customer',
            'determinations.test.parentTest',
            'determinations.test.parentTests',
            'determinations.test.children',
            'determinations.test.childTests',
            'creator',
            'validator',
        ]);

        return view('sample.show', compact('sample'));
    }

    /**
     * Muestra el formulario para editar una muestra
     */
    public function edit(Sample $sample)
    {
        $this->authorize('samples.edit');
        $customers = Customer::orderBy('name')->get();
        $tests = Test::whereJsonContains('categories', 'aguas_alimentos')
            ->orderBy('name')
            ->get();
        $sample->load('determinations');

        return view('sample.edit', compact('sample', 'customers', 'tests'));
    }

    /**
     * Actualiza una muestra
     */
    public function update(Request $request, Sample $sample)
    {
        $this->authorize('samples.edit');
        $validated = $request->validate([
            'sample_type' => 'required|in:agua,alimento,hielo',
            'entry_date' => 'required|date',
            'sampling_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'location' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'batch' => 'nullable|string|max:100',
            'product_name' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $sample->update($validated);

        return redirect()->route('sample.show', $sample)
            ->with('success', 'Protocolo actualizado correctamente.');
    }

    /**
     * Agrega una determinaci?n a una muestra existente
     */
    public function addDetermination(Request $request, Sample $sample)
    {
        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
        ]);

        // Verificar que no exista ya esta determinaci?n
        $exists = $sample->determinations()->where('test_id', $validated['test_id'])->exists();

        if ($exists) {
            return back()->with('error', 'Esta determinaci?n ya existe en el protocolo.');
        }

        $test = Test::with(['children', 'childTests', 'referenceValues'])->find($validated['test_id']);

        $parentCategoryId = $test->default_reference_category_id;

        // Calcular descuento del cliente
        $customer = $sample->customer;
        $discountPercent = $customer->discount_percent ?? 0;
        $discountMultiplier = 1 - ($discountPercent / 100);
        $basePrice = $test->price ?? 0;
        $finalPrice = round($basePrice * $discountMultiplier, 2);

        $parentRef = $this->buildReferenceValue($test);
        SampleDetermination::create([
            'sample_id' => $sample->id,
            'test_id' => $validated['test_id'],
            'price' => $finalPrice,
            'unit' => $test->unit,
            'method' => $test->method,
            'reference_value' => $parentRef['value'],
            'reference_category_id' => $parentRef['category_id'],
            'status' => 'pending',
        ]);

        $childrenAdded = 0;
        $allChildren = $test->getAllChildren();
        foreach ($allChildren as $childTest) {
            $childExists = $sample->determinations()->where('test_id', $childTest->id)->exists();
            if (! $childExists) {
                $childRef = $this->buildReferenceValue($childTest, $parentCategoryId);
                SampleDetermination::create([
                    'sample_id' => $sample->id,
                    'test_id' => $childTest->id,
                    'price' => 0,
                    'unit' => $childTest->unit,
                    'method' => $childTest->method,
                    'reference_value' => $childRef['value'],
                    'reference_category_id' => $childRef['category_id'],
                    'status' => 'pending',
                ]);
                $childrenAdded++;
            }
        }

        $message = 'Determinación agregada correctamente.';
        if ($childrenAdded > 0) {
            $message .= " Se agregaron {$childrenAdded} subdeterminaciones.";
        }

        return back()->with('success', $message);
    }

    /**
     * Elimina una determinaci?n de una muestra
     */
    public function removeDetermination(Sample $sample, SampleDetermination $determination)
    {
        if ($determination->sample_id !== $sample->id) {
            abort(403);
        }

        $test = $determination->test;
        $allChildren = $test->getAllChildren(false);
        $childTestIds = $allChildren->pluck('id');
        $deletedChildren = 0;

        if ($childTestIds->isNotEmpty()) {
            $deletedChildren = $sample->determinations()
                ->whereIn('test_id', $childTestIds)
                ->delete();
        }

        $determination->delete();

        $msg = 'Determinación eliminada correctamente.';
        if ($deletedChildren > 0) {
            $msg = "Determinación y {$deletedChildren} subdeterminaciones eliminadas correctamente.";
        }

        return back()->with('success', $msg);
    }

    /**
     * Actualiza el resultado de una determinaci?n
     */
    public function updateDetermination(Request $request, SampleDetermination $determination)
    {
        $validated = $request->validate([
            'result' => 'nullable|string|max:255',
            'reference_value' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        if ($validated['status'] === 'completed' && ! $determination->analyzed_at) {
            $validated['analyzed_at'] = now();
            $validated['analyzed_by'] = auth()->id();
        }

        $determination->update($validated);

        // Verificar si todas las determinaciones est?n completadas
        $sample = $determination->sample;
        $allCompleted = $sample->determinations()->where('status', '!=', 'completed')->count() === 0;

        if ($allCompleted && $sample->status !== 'completed') {
            $sample->update(['status' => 'completed']);
        } elseif (! $allCompleted && $sample->status === 'pending') {
            $sample->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Determinaci?n actualizada correctamente.');
    }

    /**
     * Muestra la vista de carga rápida de resultados (tipo planilla)
     */
    public function loadResults(Sample $sample)
    {
        $this->authorize('samples-results.create');
        $sample->load([
            'customer',
            'determinations.test.parentTest',
            'determinations.test.parentTests',
            'determinations.test.children',
            'determinations.test.childTests',
            'determinations.test.referenceValues.category',
            'determinations.referenceCategory',
            'creator',
        ]);

        // Cargar categorías de referencia activas
        $referenceCategories = \App\Models\ReferenceCategory::active()->ordered()->get();

        return view('sample.load-results', compact('sample', 'referenceCategories'));
    }

    /**
     * Guarda m?ltiples resultados de forma masiva
     */
    public function saveResults(Request $request, Sample $sample)
    {
        $this->authorize('samples-results.create');
        $validated = $request->validate([
            'determinations' => 'required|array',
            'determinations.*.id' => 'required|exists:sample_determinations,id',
            'determinations.*.result' => 'nullable|string|max:255',
            'determinations.*.reference_value' => 'nullable|string|max:255',
            'determinations.*.reference_category_id' => 'nullable|exists:reference_categories,id',
            'determinations.*.method' => 'nullable|string|max:255',
            'determinations.*.observations' => 'nullable|string',
            'determinations.*.status' => 'required|in:pending,in_progress,completed',
        ]);

        foreach ($validated['determinations'] as $data) {
            $determination = SampleDetermination::find($data['id']);

            if ($determination->sample_id !== $sample->id || $determination->is_validated) {
                continue;
            }

            $updateData = [
                'result' => array_key_exists('result', $data) ? $data['result'] : $determination->result,
                'reference_value' => array_key_exists('reference_value', $data) ? $data['reference_value'] : $determination->reference_value,
                'reference_category_id' => array_key_exists('reference_category_id', $data) ? $data['reference_category_id'] : $determination->reference_category_id,
                'method' => array_key_exists('method', $data) ? $data['method'] : $determination->method,
                'observations' => array_key_exists('observations', $data) ? $data['observations'] : $determination->observations,
                'status' => array_key_exists('status', $data) ? $data['status'] : $determination->status,
            ];

            if ($data['status'] === 'completed' && ! $determination->analyzed_at) {
                $updateData['analyzed_at'] = now();
                $updateData['analyzed_by'] = auth()->id();
            }

            $determination->update($updateData);

            // Si es hijo, actualizar estado del padre
            $this->updateParentDeterminationStatus($determination, $sample);
        }

        // Actualizar estado del protocolo
        $allCompleted = $sample->determinations()->where('status', '!=', 'completed')->count() === 0;
        $anyInProgress = $sample->determinations()->where('status', '!=', 'pending')->count() > 0;

        if ($allCompleted) {
            $sample->update(['status' => 'completed']);
        } elseif ($anyInProgress) {
            $sample->update(['status' => 'in_progress']);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Resultados guardados correctamente.']);
        }

        return back()->with('success', 'Resultados guardados correctamente.');
    }

    /**
     * Muestra la vista de validación del protocolo
     */
    public function showValidation(Sample $sample)
    {
        $sample->load([
            'customer',
            'determinations.test.parentTest',
            'determinations.test.parentTests',
            'determinations.test.children',
            'determinations.test.childTests',
            'determinations.analyzer',
            'creator',
            'validator',
        ]);

        return view('sample.validate', compact('sample'));
    }

    /**
     * Valida el protocolo completo
     */
    public function processValidation(Request $request, Sample $sample)
    {
        // Verificar permiso de validaci?n
        if (! auth()->user()->can('samples.validate')) {
            return back()->with('error', 'No tiene permisos para validar protocolos.');
        }

        // Verificar que el protocolo pueda ser validado
        if (! $sample->canBeValidated()) {
            return back()->with('error', 'El protocolo no puede ser validado. Todas las determinaciones deben estar completadas.');
        }

        $validated = $request->validate([
            'action' => 'required|in:validate,reject',
            'validator_notes' => 'nullable|string',
        ]);

        if ($validated['action'] === 'validate') {
            // Marcar todas las determinaciones como validadas
            $sample->determinations()->update([
                'is_validated' => true,
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);

            $sample->update([
                'validation_status' => 'validated',
                'validated_by' => auth()->id(),
                'validated_at' => now(),
                'validator_notes' => $validated['validator_notes'],
            ]);

            return redirect()->route('sample.show', $sample)
                ->with('success', 'Protocolo validado correctamente. Ahora est? disponible para descarga.');
        } else {
            $sample->update([
                'validation_status' => 'rejected',
                'validated_by' => auth()->id(),
                'validated_at' => now(),
                'validator_notes' => $validated['validator_notes'],
            ]);

            return redirect()->route('sample.show', $sample)
                ->with('warning', 'Protocolo rechazado. Se requieren correcciones.');
        }
    }

    /**
     * Valida/invalida una determinación individual
     */
    public function toggleDeterminationValidation(Request $request, SampleDetermination $determination)
    {
        if (! auth()->user()->can('samples.validate')) {
            return back()->with('error', 'No tiene permisos para validar determinaciones.');
        }

        // Solo se pueden validar determinaciones completadas
        if ($determination->status !== 'completed') {
            return back()->with('error', 'Solo se pueden validar determinaciones completadas.');
        }

        // Toggle validation
        if ($determination->is_validated) {
            $determination->update([
                'is_validated' => false,
                'validated_by' => null,
                'validated_at' => null,
            ]);
            $message = 'Determinación marcada como no validada.';
        } else {
            $determination->update([
                'is_validated' => true,
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);
            $message = 'Determinación validada correctamente.';
        }

        // Actualizar estado del protocolo
        $sample = $determination->sample;
        $this->updateSampleValidationStatus($sample);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'is_validated' => $determination->is_validated,
                'sample_status' => $sample->validation_status,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Valida múltiples determinaciones a la vez
     */
    public function validateDeterminations(Request $request, Sample $sample)
    {
        if (! auth()->user()->can('samples.validate')) {
            return back()->with('error', 'No tiene permisos para validar determinaciones.');
        }

        $validated = $request->validate([
            'determinations' => 'nullable|array',
            'determinations.*' => 'exists:sample_determinations,id',
            'action' => 'required|in:validate,unvalidate',
            'validator_notes' => 'nullable|string',
        ]);

        // Si no hay determinaciones seleccionadas
        if (empty($validated['determinations'])) {
            return back()->with('error', 'Debe seleccionar al menos una determinación.');
        }

        $count = 0;
        foreach ($validated['determinations'] as $detId) {
            $determination = SampleDetermination::find($detId);

            if ($determination->sample_id !== $sample->id) {
                continue;
            }

            // Solo validar determinaciones completadas
            if ($determination->status !== 'completed' && $validated['action'] === 'validate') {
                continue;
            }

            if ($validated['action'] === 'validate') {
                $determination->update([
                    'is_validated' => true,
                    'validated_by' => auth()->id(),
                    'validated_at' => now(),
                ]);
            } else {
                $determination->update([
                    'is_validated' => false,
                    'validated_by' => null,
                    'validated_at' => null,
                ]);
            }
            $count++;
        }

        // Guardar notas del validador si se proporcionaron
        if (! empty($validated['validator_notes'])) {
            $sample->update(['validator_notes' => $validated['validator_notes']]);
        }

        // Actualizar estado del protocolo
        $this->updateSampleValidationStatus($sample);

        $actionText = $validated['action'] === 'validate' ? 'validadas' : 'desmarcadas';

        return back()->with('success', "{$count} determinaciones {$actionText} correctamente.");
    }

    /**
     * Actualiza el estado de validación del protocolo basado en sus determinaciones
     * - Validado: TODAS las determinaciones están validadas
     * - Completo: Todos los resultados cargados O algunas validadas
     * - Incompleto: No tiene todas las determinaciones con resultado
     */
    private function updateSampleValidationStatus(Sample $sample)
    {
        $sample->refresh();

        $total = $sample->determinations()->count();
        $totalCompleted = $sample->determinations()->where('status', 'completed')->count();
        $totalValidated = $sample->determinations()->where('is_validated', true)->count();

        // Determinar validation_status
        if ($total > 0 && $totalValidated === $total) {
            // TODAS validadas = Validado
            $sample->update([
                'validation_status' => 'validated',
                'status' => 'completed',
                'validated_by' => $sample->validated_by ?? auth()->id(),
                'validated_at' => $sample->validated_at ?? now(),
            ]);
        } elseif ($totalValidated > 0 || $totalCompleted === $total) {
            // Algunas validadas O todas completadas = Completo (parcialmente validado)
            $sample->update([
                'validation_status' => $totalValidated > 0 ? 'partial' : 'pending',
                'status' => 'completed',
                'validated_by' => $totalValidated > 0 ? ($sample->validated_by ?? auth()->id()) : null,
                'validated_at' => $totalValidated > 0 ? ($sample->validated_at ?? now()) : null,
            ]);
        } else {
            // Incompleto
            $sample->update([
                'validation_status' => 'pending',
                'status' => $totalCompleted > 0 ? 'in_progress' : 'pending',
                'validated_by' => null,
                'validated_at' => null,
            ]);
        }
    }

    /**
     * Actualiza el estado de la determinación padre basado en sus hijos
     * Si al menos un hijo está completado, el padre también lo está
     * Soporta múltiples padres (tabla pivote test_parents)
     */
    private function updateParentDeterminationStatus(SampleDetermination $determination, Sample $sample)
    {
        $test = $determination->test;
        if (! $test) {
            return;
        }

        // Obtener todos los padres de este test (legacy + nueva tabla pivote)
        $parentIds = collect();

        // Parent legacy
        if ($test->parent) {
            $parentIds->push($test->parent);
        }

        // Parents de la tabla pivote
        $pivotParentIds = $test->parentTests()->pluck('tests.id');
        $parentIds = $parentIds->merge($pivotParentIds)->unique();

        if ($parentIds->isEmpty()) {
            return;
        }

        // Actualizar cada padre
        foreach ($parentIds as $parentId) {
            $parentDetermination = $sample->determinations()
                ->where('test_id', $parentId)
                ->first();

            if (! $parentDetermination) {
                continue;
            }

            // Obtener todos los hijos de este padre que están en esta muestra
            $parentTest = Test::with(['children', 'childTests'])->find($parentId);
            $allChildIds = $parentTest->getAllChildren()->pluck('id');

            $childDeterminations = $sample->determinations()
                ->whereIn('test_id', $allChildIds)
                ->get();

            if ($childDeterminations->isEmpty()) {
                continue;
            }

            // Determinar el estado del padre basado en los hijos
            $hasCompleted = $childDeterminations->where('status', 'completed')->count() > 0;
            $hasInProgress = $childDeterminations->where('status', 'in_progress')->count() > 0;
            $allPending = $childDeterminations->where('status', 'pending')->count() === $childDeterminations->count();

            if ($hasCompleted) {
                $newStatus = 'completed';
            } elseif ($hasInProgress) {
                $newStatus = 'in_progress';
            } elseif ($allPending) {
                $newStatus = 'pending';
            } else {
                $newStatus = 'in_progress';
            }

            // Actualizar el padre si cambió el estado
            if ($parentDetermination->status !== $newStatus) {
                $updateData = ['status' => $newStatus];

                if ($newStatus === 'completed' && ! $parentDetermination->analyzed_at) {
                    $updateData['analyzed_at'] = now();
                    $updateData['analyzed_by'] = auth()->id();
                }

                $parentDetermination->update($updateData);
            }
        }
    }

    /**
     * Revierte la validación del protocolo (para correcciones)
     */
    public function revertValidation(Sample $sample)
    {
        if (! auth()->user()->can('samples.validate')) {
            return back()->with('error', 'No tiene permisos para modificar validaciones.');
        }

        $sample->determinations()->update([
            'is_validated' => false,
            'validated_by' => null,
            'validated_at' => null,
        ]);

        $sample->update([
            'validation_status' => 'pending',
            'validated_by' => null,
            'validated_at' => null,
            'validator_notes' => null,
        ]);

        return back()->with('success', 'Validaci?n revertida. El protocolo puede ser editado nuevamente.');
    }

    /**
     * Genera y descarga el PDF del protocolo
     */
    public function downloadPdf(Sample $sample)
    {
        $this->authorize('samples-reports.print');
        // Solo permitir descarga si tiene al menos una determinación validada
        $validatedCount = $sample->determinations()->where('is_validated', true)->count();
        if ($validatedCount === 0) {
            return back()->with('error', 'Debe validar al menos una determinación para poder descargar el informe.');
        }

        $sample->load([
            'customer',
            'determinations.test.parentTest',
            'determinations.test.parentTests',
            'determinations.test.children',
            'determinations.test.childTests',
            'determinations.test.defaultReferenceCategory',
            'determinations.referenceCategory',
            'determinations.determinationValidator',
            'creator',
            'validator',
        ]);

        // Usar mPDF para headers/footers repetidos en cada página
        $pdf = PDF::loadView('sample.pdf-mpdf', compact('sample'), [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        return $pdf->download($this->generatePdfFilename($sample));
    }

    /**
     * Muestra el PDF del protocolo en el navegador
     */
    public function viewPdf(Sample $sample)
    {
        $this->authorize('samples-reports.preview');
        // Solo permitir visualización si tiene al menos una determinación validada
        $validatedCount = $sample->determinations()->where('is_validated', true)->count();
        if ($validatedCount === 0) {
            return back()->with('error', 'Debe validar al menos una determinación para poder ver el informe.');
        }

        $sample->load([
            'customer',
            'determinations.test.parentTest',
            'determinations.test.parentTests',
            'determinations.test.children',
            'determinations.test.childTests',
            'determinations.test.defaultReferenceCategory',
            'determinations.referenceCategory',
            'determinations.determinationValidator',
            'creator',
            'validator',
        ]);

        // Usar mPDF para headers/footers repetidos en cada página
        $pdf = PDF::loadView('sample.pdf-mpdf', compact('sample'), [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        return $pdf->stream($this->generatePdfFilename($sample));
    }

    /**
     * Env?a el protocolo por email
     */
    public function sendEmail(Request $request, Sample $sample)
    {
        $this->authorize('samples-reports.send');
        if (! $sample->isValidated()) {
            return back()->with('error', 'El protocolo debe estar validado para poder enviarlo.');
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
                (new SampleResultMail($sample, $validated['message'] ?? null))
                    ->from($fromEmail, $fromName)
            );

        return back()->with('success', 'Protocolo enviado correctamente a '.$validated['email']);
    }

    /**
     * Construye el valor de referencia basado en los valores predefinidos o campos low/high del test
     *
     * @param  Test  $test  El test para el cual construir el valor de referencia
     * @param  int|null  $parentCategoryId  ID de la categoría predeterminada del padre (si aplica)
     * @return array{value: string|null, category_id: int|null}
     */
    private function buildReferenceValue(Test $test, ?int $parentCategoryId = null): array
    {
        if ($parentCategoryId) {
            $refValue = $test->referenceValues()
                ->where('reference_category_id', $parentCategoryId)
                ->first();
            if ($refValue) {
                return ['value' => $refValue->value, 'category_id' => $parentCategoryId];
            }
        }

        $defaultRef = $test->referenceValues()->where('is_default', true)->first();
        if ($defaultRef) {
            return ['value' => $defaultRef->value, 'category_id' => $defaultRef->reference_category_id];
        }

        if ($test->referenceValues()->count() > 0) {
            return ['value' => null, 'category_id' => null];
        }

        if (empty($test->low) && empty($test->high)) {
            if (! empty($test->other_reference)) {
                return ['value' => $test->other_reference, 'category_id' => null];
            }

            return ['value' => null, 'category_id' => null];
        }

        $value = null;
        if (empty($test->low) && ! empty($test->high)) {
            $value = "< {$test->high}".($test->unit ? " {$test->unit}" : '');
        } elseif (! empty($test->low) && empty($test->high)) {
            $value = "> {$test->low}".($test->unit ? " {$test->unit}" : '');
        } else {
            $value = "{$test->low} - {$test->high}".($test->unit ? " {$test->unit}" : '');
        }

        if (! empty($test->other_reference)) {
            $value = $value.' | '.$test->other_reference;
        }

        return ['value' => $value, 'category_id' => null];
    }

    /**
     * Obtiene las determinaciones ordenadas con padres e hijos agrupados
     * Soporta hijos que pertenecen a múltiples padres (se muestran bajo cada padre)
     */
    public function getOrderedDeterminations(Sample $sample)
    {
        if (! $sample->relationLoaded('determinations')) {
            $sample->load(['determinations.test.parentTest', 'determinations.test.parentTests', 'determinations.test.children', 'determinations.test.childTests']);
        }

        $determinations = $sample->determinations;

        $ordered = collect();
        $processedAsParent = [];
        $processedAsChild = [];

        // Primero identificar todos los tests que son padres en esta muestra
        $parentTestIds = [];
        foreach ($determinations as $det) {
            $test = $det->test;
            // Es padre si tiene hijos (legacy o pivote) y alguno está en la muestra
            $allChildren = $test->getAllChildren();
            $childIdsInSample = $allChildren->pluck('id')->intersect($determinations->pluck('test_id'));
            if ($childIdsInSample->count() > 0) {
                $parentTestIds[] = $det->test_id;
            }
        }

        foreach ($determinations as $det) {
            // Si ya fue procesada como padre, saltar
            if (in_array($det->id, $processedAsParent)) {
                continue;
            }

            // Verificar si es un padre (tiene hijos en esta muestra)
            if (in_array($det->test_id, $parentTestIds)) {
                $ordered->push($det);
                $processedAsParent[] = $det->id;

                // Buscar y agregar hijos de este padre
                $allChildren = $det->test->getAllChildren();
                foreach ($allChildren as $childTest) {
                    $childDet = $determinations->firstWhere('test_id', $childTest->id);
                    if ($childDet) {
                        // Marcar el hijo con el padre actual para la vista
                        $childDetClone = clone $childDet;
                        $childDetClone->current_parent_id = $det->test_id;
                        $ordered->push($childDetClone);
                        $processedAsChild[] = $childDet->id;
                    }
                }
            }
        }

        // Agregar cualquier determinación que no fue procesada (huérfanas o sin padre en muestra)
        foreach ($determinations as $det) {
            if (! in_array($det->id, $processedAsParent) && ! in_array($det->id, $processedAsChild)) {
                $ordered->push($det);
            }
        }

        return $ordered;
    }

    /**
     * Devuelve los datos de la muestra en JSON para generar la etiqueta ZPL
     */
    public function labelData(Sample $sample)
    {
        $sample->load(['customer', 'determinations.test']);

        $materials = $sample->determinations
            ->pluck('test.material_abbreviation')
            ->unique()
            ->filter()
            ->implode('/');

        return response()->json([
            'protocol_number' => $sample->protocol_number,
            'customer_name' => $sample->customer->name ?? 'N/A',
            'materials' => $materials ?: 'N/A',
            'sample_type' => strtoupper($sample->sample_type),
            'entry_date' => $sample->entry_date->format('d/m/Y'),
        ]);
    }

    /**
     * Vista HTML de la etiqueta (fallback para impresoras no-Zebra)
     */
    public function printLabel(Sample $sample)
    {
        $this->authorize('samples-labels.print');
        $sample->load(['customer', 'determinations.test']);

        $materials = $sample->determinations
            ->pluck('test.material_abbreviation')
            ->unique()
            ->filter()
            ->implode('/');

        $barcode = new \Picqer\Barcode\BarcodeGeneratorSVG;
        $barcodeSvg = $barcode->getBarcode($sample->protocol_number, $barcode::TYPE_CODE_128, 2, 60);

        return view('sample.label', [
            'sample' => $sample,
            'materials' => $materials ?: 'N/A',
            'barcodeSvg' => $barcodeSvg,
        ]);
    }

    private function generatePdfFilename(Sample $sample): string
    {
        $parts = [
            $sample->sample_type ?? 'Protocolo',
            $sample->customer?->name ?? 'SinCliente',
            $sample->sampling_date
                ? \Carbon\Carbon::parse($sample->sampling_date)->format('Y-m-d')
                : now()->format('Y-m-d'),
        ];

        $sanitized = collect($parts)->map(function ($part) {
            $clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $part);
            $clean = preg_replace('/[^A-Za-z0-9_-]/', '_', $clean);
            $clean = preg_replace('/_+/', '_', $clean);

            return trim($clean, '_');
        })->implode('-');

        return $sanitized.'.'.$sample->protocol_number.'.pdf';
    }
}
