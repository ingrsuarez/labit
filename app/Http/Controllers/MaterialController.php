<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    /**
     * Mostrar listado de materiales
     */
    public function index()
    {
        $materials = Material::orderBy('name')->get();
        
        return view('materials.index', compact('materials'));
    }

    /**
     * Guardar nuevo material
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:materials,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
        ]);

        $validated['stock'] = $validated['stock'] ?? 0;
        $validated['min_stock'] = $validated['min_stock'] ?? 0;
        $validated['is_active'] = true;

        Material::create($validated);

        return back()->with('success', 'Material creado correctamente.');
    }

    /**
     * Actualizar material existente
     */
    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:materials,code,' . $material->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['stock'] = $validated['stock'] ?? 0;
        $validated['min_stock'] = $validated['min_stock'] ?? 0;
        $validated['is_active'] = $request->has('is_active');

        $material->update($validated);

        return back()->with('success', 'Material actualizado correctamente.');
    }

    /**
     * Eliminar material
     */
    public function destroy(Material $material)
    {
        $material->delete();

        return back()->with('success', 'Material eliminado correctamente.');
    }
}



