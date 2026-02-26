<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('name')->get();

        return view('service.index', compact('services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
        ]);

        $validated['price'] = $validated['price'] ?? 0;
        $validated['active'] = true;

        Service::create($validated);

        return back()->with('success', 'Servicio creado correctamente.');
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'active' => 'boolean',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
        ]);

        $validated['price'] = $validated['price'] ?? 0;
        $validated['active'] = $request->has('active');

        $service->update($validated);

        return back()->with('success', 'Servicio actualizado correctamente.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return back()->with('success', 'Servicio eliminado correctamente.');
    }
}
