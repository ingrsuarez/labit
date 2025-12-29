<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\Test;
use Illuminate\Http\Request;

class InsuranceNomenclatorController extends Controller
{
    /**
     * Muestra el listado de obras sociales para seleccionar
     */
    public function index()
    {
        $insurances = Insurance::withCount('nomenclator')->orderBy('name')->get();
        return view('lab.nomenclator.index', compact('insurances'));
    }

    /**
     * Muestra el nomenclador de una obra social específica
     */
    public function show(Insurance $insurance)
    {
        $nomenclator = $insurance->nomenclator()
            ->with('test')
            ->orderBy('id')
            ->get();

        // Obtener prácticas que no están en el nomenclador
        $existingTestIds = $nomenclator->pluck('test_id')->toArray();
        $availableTests = Test::whereNotIn('id', $existingTestIds)
            ->whereNull('parent') // Solo prácticas padre (no sub-tests)
            ->orderBy('code')
            ->get();

        return view('lab.nomenclator.show', compact('insurance', 'nomenclator', 'availableTests'));
    }

    /**
     * Editar valor NBU de la obra social
     */
    public function updateNbuValue(Request $request, Insurance $insurance)
    {
        $request->validate([
            'nbu_value' => 'required|numeric|min:0',
        ]);

        $insurance->update([
            'nbu_value' => $request->nbu_value,
        ]);

        // Recalcular precios de todas las prácticas
        foreach ($insurance->nomenclator as $item) {
            if (!$item->price || $request->recalculate_prices) {
                $item->update([
                    'price' => $item->nbu_units * $request->nbu_value,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Valor NBU actualizado correctamente.');
    }

    /**
     * Agregar una práctica al nomenclador
     */
    public function store(Request $request, Insurance $insurance)
    {
        $request->validate([
            'test_id' => 'required|exists:tests,id',
            'nbu_units' => 'required|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'requires_authorization' => 'boolean',
            'copago' => 'nullable|numeric|min:0',
        ]);

        // Verificar que no exista ya
        $exists = InsuranceTest::where('insurance_id', $insurance->id)
            ->where('test_id', $request->test_id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Esta práctica ya existe en el nomenclador.');
        }

        // Calcular precio si no se proporciona
        $price = $request->price ?? ($request->nbu_units * ($insurance->nbu_value ?? 0));

        InsuranceTest::create([
            'insurance_id' => $insurance->id,
            'test_id' => $request->test_id,
            'nbu_units' => $request->nbu_units,
            'price' => $price,
            'requires_authorization' => $request->boolean('requires_authorization'),
            'copago' => $request->copago ?? 0,
            'observations' => $request->observations,
        ]);

        return redirect()->back()->with('success', 'Práctica agregada al nomenclador.');
    }

    /**
     * Actualizar una práctica del nomenclador
     */
    public function update(Request $request, Insurance $insurance, InsuranceTest $insuranceTest)
    {
        $request->validate([
            'nbu_units' => 'required|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'requires_authorization' => 'boolean',
            'copago' => 'nullable|numeric|min:0',
        ]);

        $insuranceTest->update([
            'nbu_units' => $request->nbu_units,
            'price' => $request->price ?? ($request->nbu_units * ($insurance->nbu_value ?? 0)),
            'requires_authorization' => $request->boolean('requires_authorization'),
            'copago' => $request->copago ?? 0,
            'observations' => $request->observations,
        ]);

        return redirect()->back()->with('success', 'Práctica actualizada correctamente.');
    }

    /**
     * Eliminar una práctica del nomenclador
     */
    public function destroy(Insurance $insurance, InsuranceTest $insuranceTest)
    {
        $insuranceTest->delete();
        return redirect()->back()->with('success', 'Práctica eliminada del nomenclador.');
    }

    /**
     * Agregar múltiples prácticas al nomenclador
     */
    public function bulkAdd(Request $request, Insurance $insurance)
    {
        $request->validate([
            'test_ids' => 'required|array',
            'test_ids.*' => 'exists:tests,id',
        ]);

        $nbuValue = $insurance->nbu_value ?? 0;
        $added = 0;

        foreach ($request->test_ids as $testId) {
            $test = Test::find($testId);
            if (!$test) continue;

            // Verificar que no exista
            $exists = InsuranceTest::where('insurance_id', $insurance->id)
                ->where('test_id', $testId)
                ->exists();

            if ($exists) continue;

            $nbuUnits = $test->nbu ?? 1;
            InsuranceTest::create([
                'insurance_id' => $insurance->id,
                'test_id' => $testId,
                'nbu_units' => $nbuUnits,
                'price' => $nbuUnits * $nbuValue,
                'requires_authorization' => false,
                'copago' => 0,
            ]);
            $added++;
        }

        return redirect()->back()->with('success', "Se agregaron {$added} prácticas al nomenclador.");
    }

    /**
     * Recalcular todos los precios basados en NBU
     */
    public function recalculatePrices(Insurance $insurance)
    {
        $nbuValue = $insurance->nbu_value ?? 0;

        foreach ($insurance->nomenclator as $item) {
            $item->update([
                'price' => $item->nbu_units * $nbuValue,
            ]);
        }

        return redirect()->back()->with('success', 'Precios recalculados correctamente.');
    }

    /**
     * API: Buscar prácticas para agregar
     */
    public function searchTests(Request $request, Insurance $insurance)
    {
        $search = $request->get('q', '');
        
        $existingTestIds = $insurance->nomenclator()->pluck('test_id')->toArray();
        
        $tests = Test::whereNotIn('id', $existingTestIds)
            ->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'code', 'name', 'nbu', 'price']);

        return response()->json($tests);
    }
}

