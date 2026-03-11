<?php

namespace App\Http\Controllers;

use App\Models\SupplyCategory;
use Illuminate\Http\Request;

class SupplyCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('supply-categories.index');

        $categories = SupplyCategory::withCount('supplies')->orderBy('name')->get();
        return view('supply-categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorize('supply-categories.create');

        return view('supply-categories.create');
    }

    public function store(Request $request)
    {
        $this->authorize('supply-categories.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:supply_categories,name',
            'code_prefix' => 'required|string|size:3|alpha|unique:supply_categories,code_prefix',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.unique' => 'Ya existe una categoría con ese nombre.',
            'code_prefix.required' => 'El prefijo de código es obligatorio.',
            'code_prefix.size' => 'El prefijo debe tener exactamente 3 letras.',
            'code_prefix.alpha' => 'El prefijo solo puede contener letras.',
            'code_prefix.unique' => 'Ya existe una categoría con ese prefijo.',
        ]);

        $validated['code_prefix'] = strtoupper($validated['code_prefix']);
        $validated['is_active'] = true;

        SupplyCategory::create($validated);

        return redirect()->route('supply-categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit(SupplyCategory $supplyCategory)
    {
        $this->authorize('supply-categories.edit');

        return view('supply-categories.edit', compact('supplyCategory'));
    }

    public function update(Request $request, SupplyCategory $supplyCategory)
    {
        $this->authorize('supply-categories.edit');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:supply_categories,name,' . $supplyCategory->id,
            'code_prefix' => 'required|string|size:3|alpha|unique:supply_categories,code_prefix,' . $supplyCategory->id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.unique' => 'Ya existe una categoría con ese nombre.',
            'code_prefix.required' => 'El prefijo de código es obligatorio.',
            'code_prefix.size' => 'El prefijo debe tener exactamente 3 letras.',
            'code_prefix.alpha' => 'El prefijo solo puede contener letras.',
            'code_prefix.unique' => 'Ya existe una categoría con ese prefijo.',
        ]);

        $validated['code_prefix'] = strtoupper($validated['code_prefix']);
        $validated['is_active'] = $request->has('is_active');

        $supplyCategory->update($validated);

        return redirect()->route('supply-categories.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(SupplyCategory $supplyCategory)
    {
        $this->authorize('supply-categories.delete');

        if ($supplyCategory->supplies()->count() > 0) {
            return back()->with('error', 'No se puede eliminar una categoría que tiene insumos asociados.');
        }

        $supplyCategory->delete();

        return redirect()->route('supply-categories.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }
}
