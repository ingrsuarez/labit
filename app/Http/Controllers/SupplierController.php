<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('suppliers.index');
        $query = Supplier::orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhere('tax_id', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $suppliers = $query->paginate(15)->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $this->authorize('suppliers.create');
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $this->authorize('suppliers.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:20|unique:suppliers,tax_id',
            'tax_condition' => 'nullable|in:responsable_inscripto,monotributo,exento,consumidor_final',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal' => 'nullable|string|max:20',
            'cbu' => 'nullable|string|max:30',
            'bank_alias' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'tax_id.unique' => 'Este CUIT ya está registrado.',
            'email.email' => 'El email no tiene un formato válido.',
        ]);

        $validated['code'] = Supplier::generateCode();
        $validated['status'] = 'activo';

        $supplier = Supplier::create($validated);

        return redirect()->route('suppliers.index')
            ->with('success', 'Proveedor "' . $supplier->name . '" creado correctamente.');
    }

    public function show(Supplier $supplier)
    {
        $this->authorize('suppliers.index');
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        $this->authorize('suppliers.edit');
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authorize('suppliers.edit');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:20|unique:suppliers,tax_id,' . $supplier->id,
            'tax_condition' => 'nullable|in:responsable_inscripto,monotributo,exento,consumidor_final',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal' => 'nullable|string|max:20',
            'cbu' => 'nullable|string|max:30',
            'bank_alias' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'required|in:activo,inactivo',
        ], [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'tax_id.unique' => 'Este CUIT ya está registrado.',
            'email.email' => 'El email no tiene un formato válido.',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index')
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorize('suppliers.delete');
        $name = $supplier->name;
        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', "Proveedor \"{$name}\" eliminado.");
    }

    public function search(Request $request)
    {
        $this->authorize('suppliers.index');
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $suppliers = Supplier::where('status', 'activo')
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('business_name', 'like', "%{$search}%")
                      ->orWhere('tax_id', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'code', 'name', 'business_name', 'tax_id']);

        return response()->json($suppliers);
    }

    public function findByCuit(string $cuit)
    {
        $normalized = str_replace('-', '', $cuit);

        $supplier = Supplier::where('tax_id', $cuit)
            ->orWhere('tax_id', $normalized)
            ->orWhere('tax_id', substr($normalized, 0, 2).'-'.substr($normalized, 2, 8).'-'.substr($normalized, 10))
            ->first();

        if (! $supplier) {
            return response()->json(['message' => 'Proveedor no encontrado'], 404);
        }

        return response()->json([
            'id' => $supplier->id,
            'name' => $supplier->name,
            'tax_id' => $supplier->tax_id,
        ]);
    }
}
