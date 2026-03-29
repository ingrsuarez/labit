<?php

namespace App\Http\Controllers;

use App\Models\Species;
use Illuminate\Http\Request;

class SpeciesController extends Controller
{
    public function index()
    {
        $species = Species::orderBy('name')->get();

        return view('species.index', compact('species'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:species,name',
            'code' => 'required|string|max:20|alpha_dash|unique:species,code',
        ]);

        Species::create($validated);

        return redirect()->route('species.index')->with('success', 'Especie creada correctamente.');
    }

    public function update(Request $request, Species $species)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:species,name,'.$species->id,
            'code' => 'required|string|max:20|alpha_dash|unique:species,code,'.$species->id,
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $species->update($validated);

        return redirect()->route('species.index')->with('success', 'Especie actualizada correctamente.');
    }

    public function destroy(Species $species)
    {
        $species->delete();

        return redirect()->route('species.index')->with('success', 'Especie eliminada correctamente.');
    }
}
