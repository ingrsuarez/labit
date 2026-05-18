<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Support\TestFormulaDefinition;
use App\Support\TestNbuInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Nomenclador veterinario: solo prácticas raíz con categoría veterinario.
     */
    public function indexVeterinary(Request $request)
    {
        $query = Test::with(['referenceValues', 'parentTests', 'speciesReferences'])
            ->whereJsonContains('categories', 'veterinario')
            ->whereDoesntHave('parentTests')
            ->orderBy('code');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $tests = $query->paginate(20);

        $materials = \App\Models\Material::active()->orderBy('name')->get();

        $parents = Test::whereJsonContains('categories', 'veterinario')
            ->whereDoesntHave('parentTests')
            ->orderBy('name')
            ->get();

        $vetNomenclator = true;
        $testsFetchUrl = route('lab.veterinario.nomenclador');

        return view('test.index', compact('tests', 'parents', 'materials', 'vetNomenclator', 'testsFetchUrl'));
    }

    /**
     * Muestra el listado de determinaciones
     */
    public function index(Request $request)
    {
        $query = Test::with(['referenceValues', 'parentTests', 'speciesReferences'])->orderBy('code');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $tests = $query->paginate(20);

        // Materiales activos para el select
        $materials = \App\Models\Material::active()->orderBy('name')->get();

        // Tests que pueden ser padres (no tienen padres asignados - tabla pivote vacía)
        $parents = Test::orderBy('name')->get();

        $testsFetchUrl = route('tests.index');

        return view('test.index', compact('tests', 'parents', 'materials', 'testsFetchUrl'));
    }

    /**
     * Muestra el formulario para crear una determinación
     */
    public function create()
    {
        $parents = Test::whereNull('parent')->orderBy('name')->get();

        return view('test.create', compact('parents'));
    }

    /**
     * Almacena una nueva determinación
     */
    public function store(Request $request)
    {
        // Convertir strings vacíos a null (excepto arrays)
        $data = $request->except('parent_ids');
        $data = array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $data);
        $request->merge($data);
        $this->mergeNormalizedNbu($request);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:tests,code',
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'method' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'decimals' => 'nullable|integer|min:0|max:6',
            'nbu' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d)?$/'],
            'parent_ids' => 'nullable|array',
            'parent_ids.*' => 'integer|exists:tests,id',
            'low' => 'nullable|string|max:50',
            'high' => 'nullable|string|max:50',
            'other_reference' => 'nullable|string|max:500',
            'material' => 'nullable|integer',
            'price' => 'nullable|numeric|min:0',
            'categories' => 'nullable|array',
            'categories.*' => 'string|in:clinico,aguas_alimentos,veterinario',
            'sort_order' => 'nullable|integer|min:0',
            'empty_result_exempt' => 'nullable|boolean',
            'formula_enabled' => 'nullable|boolean',
            'formula_json' => 'nullable|string',
            '_context' => 'nullable|string|in:vet_nomenclator',
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'Este código ya está en uso. Por favor, elija otro.',
            'code.max' => 'El código no puede tener más de 50 caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'unit.max' => 'La unidad no puede tener más de 50 caracteres.',
            'method.max' => 'El método no puede tener más de 255 caracteres.',
            'decimals.integer' => 'Los decimales deben ser un número entero.',
            'decimals.min' => 'Los decimales no pueden ser negativos.',
            'decimals.max' => 'Los decimales no pueden ser más de 6.',
            'nbu.regex' => 'El NBU admite como máximo un decimal (ej. 1,5).',
            'parent_ids.*.exists' => 'Uno de los análisis padre seleccionados no existe.',
            'low.max' => 'El valor mínimo no puede tener más de 50 caracteres.',
            'high.max' => 'El valor máximo no puede tener más de 50 caracteres.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
        ]);

        $formula = TestFormulaDefinition::fromRequest(
            $request->input('formula_json'),
            $request->boolean('formula_enabled')
        );

        $test = Test::create([
            'code' => strtoupper($validated['code']),
            'name' => strtolower($validated['name']),
            'unit' => $validated['unit'] ?? null,
            'method' => $validated['method'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'decimals' => $validated['decimals'] ?? 2,
            'nbu' => $validated['nbu'] ?? null,
            'parent' => null,
            'low' => $validated['low'] ?? null,
            'high' => $validated['high'] ?? null,
            'other_reference' => $validated['other_reference'] ?? null,
            'material' => $validated['material'] ?? null,
            'price' => $validated['price'] ?? 0,
            'cost' => 0,
            'categories' => $validated['categories'] ?? ['clinico'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'empty_result_exempt' => $request->boolean('empty_result_exempt'),
            'formula' => $formula,
        ]);

        // Asignar múltiples padres si se seleccionaron
        if (! empty($validated['parent_ids'])) {
            $test->parentTests()->sync($validated['parent_ids']);
        }

        return $this->redirectAfterTestMutation(
            $request,
            ['search' => $test->code],
            'Determinación "'.strtoupper($test->code).'" creada correctamente.'
        );
    }

    /**
     * Muestra el formulario para editar una determinación
     */
    public function edit(Test $test)
    {
        $parents = Test::whereNull('parent')->where('id', '!=', $test->id)->orderBy('name')->get();

        return view('test.edit', compact('test', 'parents'));
    }

    /**
     * Actualiza una determinación
     */
    public function update(Request $request, Test $test)
    {
        // Convertir strings vacíos a null (excepto arrays)
        $data = $request->except('parent_ids');
        $data = array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $data);
        $request->merge($data);
        $this->mergeNormalizedNbu($request);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:tests,code,'.$test->id,
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'method' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'decimals' => 'nullable|integer|min:0|max:6',
            'nbu' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d)?$/'],
            'parent_ids' => 'nullable|array',
            'parent_ids.*' => 'integer|exists:tests,id',
            'low' => 'nullable|string|max:50',
            'high' => 'nullable|string|max:50',
            'other_reference' => 'nullable|string|max:500',
            'material' => 'nullable|integer',
            'price' => 'nullable|numeric|min:0',
            'categories' => 'nullable|array',
            'categories.*' => 'string|in:clinico,aguas_alimentos,veterinario',
            'sort_order' => 'nullable|integer|min:0',
            'empty_result_exempt' => 'nullable|boolean',
            'formula_enabled' => 'nullable|boolean',
            'formula_json' => 'nullable|string',
            '_context' => 'nullable|string|in:vet_nomenclator',
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'Este código ya está en uso. Por favor, elija otro.',
            'code.max' => 'El código no puede tener más de 50 caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'unit.max' => 'La unidad no puede tener más de 50 caracteres.',
            'method.max' => 'El método no puede tener más de 255 caracteres.',
            'decimals.integer' => 'Los decimales deben ser un número entero.',
            'decimals.min' => 'Los decimales no pueden ser negativos.',
            'decimals.max' => 'Los decimales no pueden ser más de 6.',
            'nbu.regex' => 'El NBU admite como máximo un decimal (ej. 1,5).',
            'parent_ids.*.exists' => 'Uno de los análisis padre seleccionados no existe.',
            'low.max' => 'El valor mínimo no puede tener más de 50 caracteres.',
            'high.max' => 'El valor máximo no puede tener más de 50 caracteres.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
        ]);

        $formula = TestFormulaDefinition::fromRequest(
            $request->input('formula_json'),
            $request->boolean('formula_enabled'),
            $test->id
        );

        $test->update([
            'code' => strtoupper($validated['code']),
            'name' => strtolower($validated['name']),
            'unit' => $validated['unit'],
            'method' => $validated['method'],
            'instructions' => $validated['instructions'],
            'decimals' => $validated['decimals'] ?? 2,
            'nbu' => $validated['nbu'],
            'parent' => null,
            'low' => $validated['low'],
            'high' => $validated['high'],
            'other_reference' => $validated['other_reference'],
            'material' => $validated['material'],
            'price' => $validated['price'] ?? $test->price,
            'categories' => $validated['categories'] ?? $test->categories ?? ['clinico'],
            'sort_order' => $validated['sort_order'] ?? $test->sort_order,
            'empty_result_exempt' => $request->boolean('empty_result_exempt'),
            'formula' => $formula,
        ]);

        // Sincronizar múltiples padres (esto reemplaza los existentes)
        $parentIds = $validated['parent_ids'] ?? [];
        $test->parentTests()->sync($parentIds);

        return $this->redirectAfterTestMutation(
            $request,
            [],
            'Determinación actualizada correctamente.',
            'success'
        );
    }

    /**
     * Elimina una determinación
     */
    public function destroy(Request $request, Test $test)
    {
        // Verificar si tiene determinaciones asociadas a muestras
        if ($test->sampleDeterminations()->exists()) {
            return $this->redirectAfterTestMutation(
                $request,
                [],
                'No se puede eliminar, tiene muestras asociadas.',
                'error'
            );
        }

        $test->delete();

        return $this->redirectAfterTestMutation(
            $request,
            [],
            'Determinación eliminada correctamente.',
            'success'
        );
    }

    /**
     * Actualización rápida de configuración (unidad, valores de referencia, método)
     */
    public function quickUpdate(Request $request, Test $test)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'unit' => 'nullable|string|max:50',
            'low' => 'nullable|string|max:50',
            'high' => 'nullable|string|max:50',
            'method' => 'nullable|string|max:255',
        ]);

        $test->update([
            'unit' => $validated['unit'],
            'low' => $validated['low'],
            'high' => $validated['high'],
            'method' => $validated['method'],
        ]);

        return redirect()->back()
            ->with('success', 'Determinación configurada correctamente.');
    }

    private function mergeNormalizedNbu(Request $request): void
    {
        if (! $request->has('nbu')) {
            return;
        }

        $request->merge([
            'nbu' => TestNbuInput::normalize(
                $request->input('nbu') === null ? null : (string) $request->input('nbu')
            ),
        ]);
    }

    /**
     * @param  'success'|'error'  $flashType
     */
    private function redirectAfterTestMutation(Request $request, array $query, string $message, string $flashType = 'success'): RedirectResponse
    {
        if ($request->input('_context') === 'vet_nomenclator' || $request->query('_context') === 'vet_nomenclator') {
            return redirect()->route('lab.veterinario.nomenclador', $query)->with($flashType, $message);
        }

        if ($flashType === 'error') {
            return redirect()->route('tests.index', $query)->with('error', $message);
        }

        return redirect()->route('tests.index', $query)->with('success', $message);
    }
}
