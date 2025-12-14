<?php

namespace App\Http\Controllers;

use App\Models\ReferenceCategory;
use Illuminate\Http\Request;

class ReferenceCategoryController extends Controller
{
    /**
     * Muestra el listado de categorías
     */
    public function index()
    {
        $categories = ReferenceCategory::with('referenceValues')->orderBy('order')->orderBy('name')->get();
        return view('reference-category.index', compact('categories'));
    }

    /**
     * Guarda una nueva categoría
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:reference_categories,code',
            'description' => 'nullable|string',
        ]);

        ReferenceCategory::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'] ?? '',
            'is_active' => true,
            'order' => ReferenceCategory::max('order') + 1,
        ]);

        return back()->with('success', 'Categoría creada correctamente.');
    }

    /**
     * Actualiza una categoría
     */
    public function update(Request $request, ReferenceCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:reference_categories,code,' . $category->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'] ?? '',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Categoría actualizada.');
    }

    /**
     * Elimina una categoría
     */
    public function destroy(ReferenceCategory $category)
    {
        if ($category->referenceValues()->count() > 0) {
            return back()->with('error', 'No se puede eliminar: hay valores de referencia usando esta categoría.');
        }

        $category->delete();
        return back()->with('success', 'Categoría eliminada.');
    }
}



