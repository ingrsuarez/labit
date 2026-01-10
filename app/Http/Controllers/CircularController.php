<?php

namespace App\Http\Controllers;

use App\Models\Circular;
use Illuminate\Http\Request;

class CircularController extends Controller
{
    /**
     * Listado de circulares con búsqueda
     */
    public function index(Request $request)
    {
        $query = Circular::with(['creator', 'signatures'])
            ->withCount(['signatures as signed_count' => function($q) {
                $q->whereNotNull('signed_at');
            }])
            ->orderBy('date', 'desc');

        // Búsqueda por descripción/título/código
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por sector
        if ($request->filled('sector')) {
            $query->where('sector', $request->sector);
        }

        $circulars = $query->paginate(15);

        // Estadísticas
        $stats = [
            'total' => Circular::count(),
            'activas' => Circular::where('status', 'activa')->count(),
            'inactivas' => Circular::where('status', 'inactiva')->count(),
        ];

        return view('circular.index', compact('circulars', 'stats'));
    }

    /**
     * Formulario de creación
     */
    public function create()
    {
        $code = Circular::generateCode();

        return view('circular.create', compact('code'));
    }

    /**
     * Guardar nueva circular
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'sector' => 'required|in:' . implode(',', array_keys(Circular::sectors())),
            'description' => 'required|string',
        ]);

        $validated['code'] = Circular::generateCode();
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'activa';

        $circular = Circular::create($validated);

        return redirect()->route('circular.show', $circular)
            ->with('success', "Circular {$circular->code} creada correctamente.");
    }

    /**
     * Ver detalle de circular
     */
    public function show(Circular $circular)
    {
        $circular->load('creator');

        return view('circular.show', compact('circular'));
    }

    /**
     * Formulario de edición
     */
    public function edit(Circular $circular)
    {
        return view('circular.edit', compact('circular'));
    }

    /**
     * Actualizar circular
     */
    public function update(Request $request, Circular $circular)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'sector' => 'required|in:' . implode(',', array_keys(Circular::sectors())),
            'description' => 'required|string',
            'status' => 'required|in:activa,inactiva',
        ]);

        $circular->update($validated);

        return redirect()->route('circular.show', $circular)
            ->with('success', 'Circular actualizada correctamente.');
    }

    /**
     * Eliminar circular
     */
    public function destroy(Circular $circular)
    {
        $code = $circular->code;
        $circular->delete();

        return redirect()->route('circular.index')
            ->with('success', "Circular {$code} eliminada.");
    }

    /**
     * Generar PDF de la circular
     */
    public function pdf(Circular $circular)
    {
        $circular->load('creator');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('circular.pdf', compact('circular'));
        
        return $pdf->stream("Circular-{$circular->code}.pdf");
    }

    /**
     * Vista para impresión
     */
    public function print(Circular $circular)
    {
        $circular->load('creator');

        return view('circular.pdf', compact('circular'));
    }

    /**
     * Ver seguimiento de firmas de una circular
     */
    public function signatures(Circular $circular)
    {
        $circular->load(['creator', 'signatures.employee']);
        
        // Obtener todos los empleados activos
        $allEmployees = \App\Models\Employee::where('status', 'active')->get();
        
        // Firmas realizadas
        $signedEmployees = $circular->signatures()
            ->whereNotNull('signed_at')
            ->with('employee')
            ->orderBy('signed_at', 'desc')
            ->get();
        
        // Empleados que han leído pero no firmado
        $readOnly = $circular->signatures()
            ->whereNotNull('read_at')
            ->whereNull('signed_at')
            ->with('employee')
            ->get();
        
        // Empleados pendientes (no han ni leído)
        $pendingEmployees = $allEmployees->filter(function($employee) use ($circular) {
            return !$circular->signatures()->where('employee_id', $employee->id)->exists();
        });

        return view('circular.signatures', compact(
            'circular',
            'signedEmployees',
            'readOnly',
            'pendingEmployees',
            'allEmployees'
        ));
    }
}
