<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Insurance;
use App\Models\Group;
use Exception;

class InsuranceController extends Controller
{
    /**
     * Lista todas las obras sociales (excluyendo nomencladores)
     */
    public function index()
    {
        // Excluir nomencladores - solo mostrar obras sociales, prepagas y particulares
        $insurances = Insurance::where('type', '!=', 'nomenclador')
            ->orderBy('type')
            ->orderBy('name')
            ->get();
        $groups = Group::all();
        
        return view('insurance.index', compact('insurances', 'groups'));
    }

    /**
     * Muestra el formulario de creación
     */
    public function create()
    {
        $groups = Group::all();
        $nomenclators = Insurance::where('type', 'nomenclador')
            ->orderBy('name')
            ->get();
        return view('insurance.create', compact('groups', 'nomenclators'));
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
                'nomenclator_id' => $request->nomenclator_id,
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
        
        // Obtener nomencladores disponibles (solo si no es un nomenclador)
        $nomenclators = [];
        if ($insurance->type !== 'nomenclador') {
            $nomenclators = Insurance::where('type', 'nomenclador')
                ->orderBy('name')
                ->get();
        }
        
        return view('insurance.edit', compact('insurance', 'groups', 'nomenclators'));
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
                'nomenclator_id' => $request->nomenclator_id,
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
     * Retorna los tipos de cobertura disponibles (para clientes)
     */
    public static function types(): array
    {
        return [
            'particular' => 'Particular',
            'obra_social' => 'Obra Social',
            'prepaga' => 'Prepaga',
        ];
    }

    /**
     * Retorna todos los tipos incluyendo nomencladores (para uso interno)
     */
    public static function allTypes(): array
    {
        return [
            'particular' => 'Particular',
            'obra_social' => 'Obra Social',
            'prepaga' => 'Prepaga',
            'nomenclador' => 'Nomenclador',
        ];
    }
}
