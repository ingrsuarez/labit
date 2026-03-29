<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('supplies.index');

        $query = Supply::with(['category', 'defaultSupplier'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('supply_category_id', $request->category);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->whereColumn('stock', '<=', 'min_stock');
            } elseif ($request->stock_status === 'ok') {
                $query->whereColumn('stock', '>', 'min_stock');
            } elseif ($request->stock_status === 'zero') {
                $query->where('stock', '<=', 0);
            }
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active === '1');
        }

        $supplies = $query->paginate(15)->withQueryString();
        $categories = SupplyCategory::active()->orderBy('name')->get();

        $lowStockCount = Supply::active()->whereColumn('stock', '<=', 'min_stock')->count();

        return view('supplies.index', compact('supplies', 'categories', 'lowStockCount'));
    }

    public function create()
    {
        $this->authorize('supplies.create');

        $categories = SupplyCategory::active()->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        return view('supplies.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request)
    {
        $this->authorize('supplies.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'supply_category_id' => 'nullable|exists:supply_categories,id',
            'unit' => 'required|string|max:50',
            'min_stock' => 'nullable|numeric|min:0',
            'default_supplier_id' => 'nullable|exists:suppliers,id',
        ], [
            'name.required' => 'El nombre del insumo es obligatorio.',
            'unit.required' => 'La unidad de medida es obligatoria.',
        ]);

        $validated['code'] = Supply::generateCode($validated['supply_category_id'] ?? null);
        $validated['stock'] = 0;
        $validated['last_price'] = 0;
        $validated['is_active'] = true;
        $validated['tracks_lot'] = $request->has('tracks_lot');
        $validated['min_stock'] = $validated['min_stock'] ?? 0;

        $supply = Supply::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'id' => $supply->id,
                'code' => $supply->code,
                'name' => $supply->name,
                'unit' => $supply->unit,
                'tracks_lot' => $supply->tracks_lot,
            ], 201);
        }

        return redirect()->route('supplies.index')
            ->with('success', 'Insumo "' . $supply->name . '" creado correctamente.');
    }

    public function show(Supply $supply)
    {
        $this->authorize('supplies.index');

        $supply->load(['category', 'defaultSupplier']);
        $movements = $supply->stockMovements()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('supplies.show', compact('supply', 'movements'));
    }

    public function edit(Supply $supply)
    {
        $this->authorize('supplies.edit');

        $categories = SupplyCategory::active()->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        return view('supplies.edit', compact('supply', 'categories', 'suppliers'));
    }

    public function update(Request $request, Supply $supply)
    {
        $this->authorize('supplies.edit');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'supply_category_id' => 'nullable|exists:supply_categories,id',
            'unit' => 'required|string|max:50',
            'min_stock' => 'nullable|numeric|min:0',
            'default_supplier_id' => 'nullable|exists:suppliers,id',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'El nombre del insumo es obligatorio.',
            'unit.required' => 'La unidad de medida es obligatoria.',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['tracks_lot'] = $request->has('tracks_lot');
        $validated['min_stock'] = $validated['min_stock'] ?? 0;

        $supply->update($validated);

        return redirect()->route('supplies.index')
            ->with('success', 'Insumo actualizado correctamente.');
    }

    public function destroy(Supply $supply)
    {
        $this->authorize('supplies.delete');

        if ($supply->stockMovements()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un insumo que tiene movimientos de stock.');
        }

        $name = $supply->name;
        $supply->delete();

        return redirect()->route('supplies.index')
            ->with('success', "Insumo \"{$name}\" eliminado.");
    }

    public function search(Request $request)
    {
        $this->authorize('supplies.index');

        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $supplies = Supply::active()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            })
            ->limit(15)
            ->get(['id', 'code', 'name', 'unit', 'stock', 'last_price']);

        return response()->json($supplies);
    }
}
