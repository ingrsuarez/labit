<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;

class TestController extends Controller
{
    /**
     * Muestra el listado de determinaciones
     */
    public function index(Request $request)
    {
        $query = Test::with(['referenceValues', 'parentTests'])->orderBy('code');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $tests = $query->paginate(20);
        
        // Materiales activos para el select
        $materials = \App\Models\Material::active()->orderBy('name')->get();
        
        // Tests que pueden ser padres (no tienen padres asignados - tabla pivote vacía)
        $parents = Test::whereDoesntHave('parentTests')
            ->whereNull('parent')
            ->orderBy('name')
            ->get();

        return view('test.index', compact('tests', 'parents', 'materials'));
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

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:tests,code',
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'method' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'decimals' => 'nullable|integer|min:0|max:6',
            'nbu' => 'nullable|integer',
            'parent_ids' => 'nullable|array',
            'parent_ids.*' => 'integer|exists:tests,id',
            'low' => 'nullable|string|max:50',
            'high' => 'nullable|string|max:50',
            'material' => 'nullable|integer',
        ]);

        $test = Test::create([
            'code' => strtoupper($validated['code']),
            'name' => strtolower($validated['name']),
            'unit' => $validated['unit'] ?? null,
            'method' => $validated['method'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'decimals' => $validated['decimals'] ?? 2,
            'nbu' => $validated['nbu'] ?? null,
            'parent' => null, // Ya no usamos el campo legacy para nuevos registros
            'low' => $validated['low'] ?? null,
            'high' => $validated['high'] ?? null,
            'material' => $validated['material'] ?? null,
            'price' => 0,
            'cost' => 0,
        ]);

        // Asignar múltiples padres si se seleccionaron
        if (!empty($validated['parent_ids'])) {
            $test->parentTests()->sync($validated['parent_ids']);
        }

        // Redirigir mostrando la nueva determinación (buscar por su código)
        return redirect()->route('tests.index', ['search' => $test->code])
            ->with('success', 'Determinación "' . strtoupper($test->code) . '" creada correctamente.');
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

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:tests,code,' . $test->id,
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'method' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'decimals' => 'nullable|integer|min:0|max:6',
            'nbu' => 'nullable|integer',
            'parent_ids' => 'nullable|array',
            'parent_ids.*' => 'integer|exists:tests,id',
            'low' => 'nullable|string|max:50',
            'high' => 'nullable|string|max:50',
            'material' => 'nullable|integer',
        ]);

        $test->update([
            'code' => strtoupper($validated['code']),
            'name' => strtolower($validated['name']),
            'unit' => $validated['unit'],
            'method' => $validated['method'],
            'instructions' => $validated['instructions'],
            'decimals' => $validated['decimals'] ?? 2,
            'nbu' => $validated['nbu'],
            'parent' => null, // Limpiar campo legacy
            'low' => $validated['low'],
            'high' => $validated['high'],
            'material' => $validated['material'],
        ]);

        // Sincronizar múltiples padres (esto reemplaza los existentes)
        $parentIds = $validated['parent_ids'] ?? [];
        $test->parentTests()->sync($parentIds);

        return redirect()->route('tests.index')
            ->with('success', 'Determinación actualizada correctamente.');
    }

    /**
     * Elimina una determinación
     */
    public function destroy(Test $test)
    {
        // Verificar si tiene determinaciones asociadas a muestras
        if ($test->sampleDeterminations()->exists()) {
            return redirect()->route('tests.index')
                ->with('error', 'No se puede eliminar, tiene muestras asociadas.');
        }

        $test->delete();

        return redirect()->route('tests.index')
            ->with('success', 'Determinación eliminada correctamente.');
    }

    /**
     * Actualización rápida de configuración (unidad, valores de referencia, método)
     */
    public function quickUpdate(Request $request, Test $test)
    {
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
}
