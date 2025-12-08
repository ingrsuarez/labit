<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\TestReferenceValue;
use App\Models\ReferenceCategory;
use Illuminate\Http\Request;

class TestReferenceValueController extends Controller
{
    /**
     * Muestra los valores de referencia de un test
     */
    public function index(Test $test)
    {
        $test->load('referenceValues.category');
        $categories = ReferenceCategory::where('is_active', true)->orderBy('name')->get();
        
        return view('test.reference-values', compact('test', 'categories'));
    }

    /**
     * Guarda un nuevo valor de referencia
     */
    public function store(Request $request, Test $test)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:reference_categories,id',
            'category_name' => 'nullable|string|max:255',
            'value' => 'required|string|max:255',
            'min_value' => 'nullable|string|max:50',
            'max_value' => 'nullable|string|max:50',
            'is_default' => 'boolean',
        ]);

        // Si se proporciona un nuevo nombre de categoría, crearla
        $categoryId = $validated['category_id'] ?? null;
        if (empty($categoryId) && !empty($validated['category_name'])) {
            // Generar código único
            $code = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $validated['category_name']), 0, 3));
            $code = $code ?: 'CAT';
            $count = ReferenceCategory::where('code', 'like', $code . '%')->count();
            if ($count > 0) {
                $code = $code . ($count + 1);
            }
            
            $category = ReferenceCategory::create([
                'name' => $validated['category_name'],
                'code' => $code,
                'description' => '',
                'is_active' => true,
            ]);
            $categoryId = $category->id;
        }

        // Si es default, quitar default de los demás
        if ($request->boolean('is_default')) {
            $test->referenceValues()->update(['is_default' => false]);
        }

        TestReferenceValue::create([
            'test_id' => $test->id,
            'reference_category_id' => $categoryId,
            'value' => $validated['value'],
            'min_value' => $validated['min_value'] ?? null,
            'max_value' => $validated['max_value'] ?? null,
            'is_default' => $request->boolean('is_default'),
        ]);

        return back()->with('success', 'Valor de referencia creado correctamente.');
    }

    /**
     * Actualiza un valor de referencia
     */
    public function update(Request $request, Test $test, TestReferenceValue $referenceValue)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:reference_categories,id',
            'value' => 'required|string|max:255',
            'min_value' => 'nullable|string|max:50',
            'max_value' => 'nullable|string|max:50',
            'is_default' => 'boolean',
        ]);

        // Si es default, quitar default de los demás
        if ($request->boolean('is_default')) {
            $test->referenceValues()->where('id', '!=', $referenceValue->id)->update(['is_default' => false]);
        }

        $referenceValue->update([
            'reference_category_id' => $validated['category_id'] ?? null,
            'value' => $validated['value'],
            'min_value' => $validated['min_value'] ?? null,
            'max_value' => $validated['max_value'] ?? null,
            'is_default' => $request->boolean('is_default'),
        ]);

        return back()->with('success', 'Valor de referencia actualizado.');
    }

    /**
     * Elimina un valor de referencia
     */
    public function destroy(Test $test, TestReferenceValue $referenceValue)
    {
        $referenceValue->delete();
        
        return back()->with('success', 'Valor de referencia eliminado.');
    }

    /**
     * Establece un valor como default
     */
    public function setDefault(Test $test, TestReferenceValue $referenceValue)
    {
        $test->referenceValues()->update(['is_default' => false]);
        $referenceValue->update(['is_default' => true]);
        
        return back()->with('success', 'Valor de referencia establecido como predeterminado.');
    }
}
