<?php

namespace App\Http\Controllers;

use App\Models\NonConformity;
use App\Models\NonConformityFollowUp;
use App\Models\Employee;
use Illuminate\Http\Request;

class NonConformityController extends Controller
{
    /**
     * Listado de no conformidades
     */
    public function index(Request $request)
    {
        $query = NonConformity::with(['employee', 'reporter'])
            ->orderBy('date', 'desc');

        // Filtros
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $nonConformities = $query->paginate(15);
        $employees = Employee::orderBy('lastName')->get();

        // Estadísticas
        $stats = [
            'total' => NonConformity::count(),
            'abiertas' => NonConformity::where('status', 'abierta')->count(),
            'en_proceso' => NonConformity::where('status', 'en_proceso')->count(),
            'cerradas' => NonConformity::where('status', 'cerrada')->count(),
        ];

        return view('non-conformity.index', compact('nonConformities', 'employees', 'stats'));
    }

    /**
     * Formulario de creación
     */
    public function create()
    {
        $employees = Employee::orderBy('lastName')->get();
        $code = NonConformity::generateCode();

        return view('non-conformity.create', compact('employees', 'code'));
    }

    /**
     * Guardar nueva NC
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'type' => 'required|in:procedimiento,capacitacion,seguridad,calidad,otro',
            'severity' => 'required|in:leve,moderada,grave',
            'description' => 'required|string',
            'procedure_name' => 'nullable|string|max:255',
            'training_name' => 'nullable|string|max:255',
            'corrective_action' => 'nullable|string',
            'preventive_action' => 'nullable|string',
        ]);

        $validated['code'] = NonConformity::generateCode();
        $validated['reported_by'] = auth()->id();
        $validated['status'] = 'abierta';

        $nc = NonConformity::create($validated);

        return redirect()->route('non-conformity.show', $nc)
            ->with('success', "No Conformidad {$nc->code} creada correctamente.");
    }

    /**
     * Ver detalle de NC
     */
    public function show(NonConformity $nonConformity)
    {
        $nonConformity->load(['employee', 'reporter', 'closer', 'followUps.user']);

        return view('non-conformity.show', compact('nonConformity'));
    }

    /**
     * Formulario de edición
     */
    public function edit(NonConformity $nonConformity)
    {
        $employees = Employee::orderBy('lastName')->get();

        return view('non-conformity.edit', compact('nonConformity', 'employees'));
    }

    /**
     * Actualizar NC
     */
    public function update(Request $request, NonConformity $nonConformity)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'type' => 'required|in:procedimiento,capacitacion,seguridad,calidad,otro',
            'severity' => 'required|in:leve,moderada,grave',
            'description' => 'required|string',
            'procedure_name' => 'nullable|string|max:255',
            'training_name' => 'nullable|string|max:255',
            'corrective_action' => 'nullable|string',
            'preventive_action' => 'nullable|string',
            'status' => 'required|in:abierta,en_proceso,cerrada',
        ]);

        // Si se cierra, registrar quién y cuándo
        if ($validated['status'] === 'cerrada' && $nonConformity->status !== 'cerrada') {
            $validated['closed_at'] = now();
            $validated['closed_by'] = auth()->id();
        }

        // Si se reabre, limpiar campos de cierre
        if ($validated['status'] !== 'cerrada') {
            $validated['closed_at'] = null;
            $validated['closed_by'] = null;
        }

        $nonConformity->update($validated);

        return redirect()->route('non-conformity.show', $nonConformity)
            ->with('success', 'No Conformidad actualizada correctamente.');
    }

    /**
     * Eliminar NC
     */
    public function destroy(NonConformity $nonConformity)
    {
        $code = $nonConformity->code;
        $nonConformity->delete();

        return redirect()->route('non-conformity.index')
            ->with('success', "No Conformidad {$code} eliminada.");
    }

    /**
     * Agregar seguimiento
     */
    public function addFollowUp(Request $request, NonConformity $nonConformity)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
            'status_change' => 'nullable|in:abierta,en_proceso,cerrada',
        ]);

        $validated['non_conformity_id'] = $nonConformity->id;
        $validated['user_id'] = auth()->id();

        // Si hay cambio de estado
        if (!empty($validated['status_change'])) {
            $oldStatus = $nonConformity->status;
            $newStatus = $validated['status_change'];
            
            $nonConformity->status = $newStatus;
            
            if ($newStatus === 'cerrada') {
                $nonConformity->closed_at = now();
                $nonConformity->closed_by = auth()->id();
            } elseif ($oldStatus === 'cerrada') {
                $nonConformity->closed_at = null;
                $nonConformity->closed_by = null;
            }
            
            $nonConformity->save();
            $validated['status_change'] = "Cambio de estado: {$oldStatus} → {$newStatus}";
        }

        NonConformityFollowUp::create($validated);

        return back()->with('success', 'Seguimiento agregado correctamente.');
    }

    /**
     * Generar PDF de la NC
     */
    public function pdf(NonConformity $nonConformity)
    {
        $nonConformity->load(['employee', 'reporter', 'closer']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('non-conformity.pdf', compact('nonConformity'));
        
        return $pdf->stream("NC-{$nonConformity->code}.pdf");
    }

    /**
     * Imprimir (vista para impresión)
     */
    public function print(NonConformity $nonConformity)
    {
        $nonConformity->load(['employee', 'reporter', 'closer']);

        return view('non-conformity.pdf', compact('nonConformity'));
    }
}
