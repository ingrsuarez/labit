<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use Illuminate\Http\Request;

class PointOfSaleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('points-of-sale.index');

        $query = PointOfSale::orderBy('code');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $pointsOfSale = $query->withCount('salesInvoices')->paginate(15)->withQueryString();

        return view('points-of-sale.index', compact('pointsOfSale'));
    }

    public function create()
    {
        $this->authorize('points-of-sale.create');

        return view('points-of-sale.create');
    }

    public function store(Request $request)
    {
        $this->authorize('points-of-sale.create');

        $validated = $request->validate([
            'code' => 'required|string|max:5|unique:points_of_sale,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['code'] = str_pad($validated['code'], 5, '0', STR_PAD_LEFT);

        PointOfSale::create($validated);

        return redirect()->route('points-of-sale.index')
            ->with('success', 'Punto de venta creado correctamente.');
    }

    public function edit(PointOfSale $pointOfSale)
    {
        $this->authorize('points-of-sale.edit');

        return view('points-of-sale.edit', compact('pointOfSale'));
    }

    public function update(Request $request, PointOfSale $pointOfSale)
    {
        $this->authorize('points-of-sale.edit');

        $validated = $request->validate([
            'code' => 'required|string|max:5|unique:points_of_sale,code,' . $pointOfSale->id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['code'] = str_pad($validated['code'], 5, '0', STR_PAD_LEFT);

        $pointOfSale->update($validated);

        return redirect()->route('points-of-sale.index')
            ->with('success', 'Punto de venta actualizado correctamente.');
    }

    public function destroy(PointOfSale $pointOfSale)
    {
        $this->authorize('points-of-sale.delete');

        if ($pointOfSale->salesInvoices()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene facturas asociadas.');
        }

        $pointOfSale->delete();

        return redirect()->route('points-of-sale.index')
            ->with('success', 'Punto de venta eliminado correctamente.');
    }
}
