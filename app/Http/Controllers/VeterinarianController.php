<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Veterinarian;
use Illuminate\Http\Request;

class VeterinarianController extends Controller
{
    public function index(Customer $customer)
    {
        return response()->json(
            $customer->veterinarians()->orderBy('name')->get()
        );
    }

    public function store(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'matricula' => 'nullable|string|max:50',
        ]);

        $vet = $customer->veterinarians()->create($validated);

        return response()->json($vet, 201);
    }

    public function update(Request $request, Customer $customer, Veterinarian $veterinarian)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'matricula' => 'nullable|string|max:50',
        ]);

        $veterinarian->update($validated);

        return response()->json($veterinarian);
    }

    public function destroy(Customer $customer, Veterinarian $veterinarian)
    {
        $veterinarian->delete();

        return response()->json(['ok' => true]);
    }
}
