<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Insurance;
use App\Models\Group;
use Exception;

class InsuranceController extends Controller
{
    /**
     * Lista todas las obras sociales
     */
    public function index()
    {
        $insurances = Insurance::orderBy('type')->orderBy('name')->get();
        $groups = Group::all();
        
        return view('insurance.index', compact('insurances', 'groups'));
    }

    /**
     * Muestra el formulario de creación
     */
    public function create()
    {
        $groups = Group::all();
        return view('insurance.create', compact('groups'));
    }

    /**
     * Almacena una nueva obra social
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:particular,obra_social,prepaga',
        ]);

        try {
            Insurance::create([
                'name' => strtolower($request->name),
                'type' => $request->type,
                'tax_id' => $request->tax_id,
                'tax' => $request->tax,
                'group' => $request->group,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => strtolower($request->address),
                'price' => $request->price,
                'nbu' => $request->nbu,
                'nbu_value' => $request->nbu_value,
                'instructions' => strtolower($request->instructions),
                'country' => $request->country,
                'state' => $request->state,
            ]);

            return redirect()->route('insurance.index')
                ->with('success', 'Cobertura creada correctamente');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear la cobertura: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Muestra el formulario de edición
     */
    public function edit(Insurance $insurance)
    {
        $groups = Group::all();
        return view('insurance.edit', compact('insurance', 'groups'));
    }

    /**
     * Actualiza una obra social
     */
    public function update(Request $request, Insurance $insurance)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:particular,obra_social,prepaga',
        ]);

        try {
            $insurance->update([
                'name' => strtolower($request->name),
                'type' => $request->type,
                'tax_id' => $request->tax_id,
                'tax' => $request->tax,
                'group' => $request->group,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => strtolower($request->address),
                'price' => $request->price,
                'nbu' => $request->nbu,
                'nbu_value' => $request->nbu_value,
                'instructions' => strtolower($request->instructions),
                'country' => $request->country,
                'state' => $request->state,
            ]);

            return redirect()->route('insurance.index')
                ->with('success', 'Cobertura actualizada correctamente');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar la cobertura: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Elimina una obra social
     */
    public function destroy(Insurance $insurance)
    {
        try {
            // Verificar si tiene admisiones o pacientes asociados
            if ($insurance->admissions()->count() > 0) {
                return redirect()->route('insurance.index')
                    ->with('error', 'No se puede eliminar: tiene admisiones asociadas');
            }

            $insurance->delete();

            return redirect()->route('insurance.index')
                ->with('success', 'Cobertura eliminada correctamente');
        } catch (Exception $e) {
            return redirect()->route('insurance.index')
                ->with('error', 'Error al eliminar la cobertura: ' . $e->getMessage());
        }
    }

    /**
     * Retorna los tipos de cobertura disponibles
     */
    public static function types(): array
    {
        return [
            'particular' => 'Particular',
            'obra_social' => 'Obra Social',
            'prepaga' => 'Prepaga',
        ];
    }
}
