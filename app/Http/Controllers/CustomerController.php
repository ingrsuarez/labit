<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Muestra el listado de clientes
     */
    public function index(Request $request)
    {
        $query = Customer::orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('taxId', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->paginate(15);

        return view('customer.index', compact('customers'));
    }

    /**
     * Muestra el formulario para crear un nuevo cliente
     */
    public function create()
    {
        return view('customer.create');
    }

    /**
     * Almacena un nuevo cliente
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'taxId' => 'required|string|max:20|unique:customers,taxId',
            'tax' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:customers,email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal' => 'nullable|string|max:20',
        ]);

        $validated['status'] = 'activo';

        Customer::create($validated);

        return redirect()->route('customer.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    /**
     * Muestra el formulario para editar un cliente
     */
    public function edit(Customer $customer)
    {
        return view('customer.edit', compact('customer'));
    }

    /**
     * Actualiza un cliente
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'taxId' => 'required|string|max:20|unique:customers,taxId,' . $customer->id,
            'tax' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal' => 'nullable|string|max:20',
            'status' => 'required|in:activo,inactivo',
        ]);

        $customer->update($validated);

        return redirect()->route('customer.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }
}
