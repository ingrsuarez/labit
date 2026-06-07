<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Insurance;
use App\Services\NbuRetroactivePricingService;
use App\Support\SyncsEntityEmails;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InsuranceController extends Controller
{
    /**
     * Lista todas las obras sociales (excluyendo nomencladores)
     */
    public function index()
    {
        // Excluir nomencladores - solo mostrar obras sociales, prepagas y particulares
        $insurances = Insurance::with('emails')
            ->where('type', '!=', 'nomenclador')
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
    public function store(Request $request, SyncsEntityEmails $syncsEntityEmails)
    {
        $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'type' => 'required|in:particular,obra_social,prepaga',
        ], $this->entityEmailRules()));

        try {
            $emailRows = $this->resolveEmailRows($request);

            DB::transaction(function () use ($request, $syncsEntityEmails, $emailRows) {
                $insurance = Insurance::create([
                    'name' => strtolower($request->name),
                    'short_name' => $request->short_name ? strtolower($request->short_name) : null,
                    'type' => $request->type,
                    'tax_id' => $request->tax_id,
                    'tax' => $request->tax,
                    'group' => $request->group,
                    'email' => null,
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

                $syncsEntityEmails->sync($insurance, $emailRows);
            });

            return redirect()->route('insurance.index')
                ->with('success', 'Cobertura creada correctamente');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear la cobertura: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Muestra el formulario de edición
     */
    public function edit(Insurance $insurance)
    {
        $insurance->load('emails');
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
    public function update(Request $request, Insurance $insurance, SyncsEntityEmails $syncsEntityEmails)
    {
        $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'type' => 'required|in:particular,obra_social,prepaga',
            'retroactive_from' => 'nullable|date|before_or_equal:today',
        ], $this->entityEmailRules()));

        try {
            $emailRows = $this->resolveEmailRows($request);
            $oldNbuValue = (float) ($insurance->nbu_value ?? 0);
            $newNbuValue = (float) $request->nbu_value;

            DB::transaction(function () use ($request, $insurance, $syncsEntityEmails, $emailRows) {
                $insurance->update([
                    'name' => strtolower($request->name),
                    'short_name' => $request->short_name ? strtolower($request->short_name) : null,
                    'type' => $request->type,
                    'tax_id' => $request->tax_id,
                    'tax' => $request->tax,
                    'group' => $request->group,
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

                $syncsEntityEmails->sync($insurance, $emailRows);
            });

            $retroResult = app(NbuRetroactivePricingService::class)
                ->applyClinicalIfRequested($request, $insurance->fresh(), $newNbuValue, $oldNbuValue);

            return redirect()->route('insurance.index')
                ->with('success', NbuRetroactivePricingService::flashMessage($retroResult));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar la cobertura: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * @return array<string, string>
     */
    private function entityEmailRules(): array
    {
        return [
            'emails' => 'nullable|array',
            'emails.*.email' => 'nullable|email|max:255',
            'emails.*.label_preset' => 'nullable|string|max:50',
            'emails.*.label_custom' => 'nullable|string|max:50',
            'emails.*.is_primary' => 'nullable|boolean',
            'email' => 'nullable|email',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveEmailRows(Request $request): array
    {
        $rows = $request->input('emails', []);

        if ($rows === [] && $request->filled('email')) {
            return [['email' => $request->input('email'), 'is_primary' => true]];
        }

        return is_array($rows) ? $rows : [];
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
                ->with('error', 'Error al eliminar la cobertura: '.$e->getMessage());
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
