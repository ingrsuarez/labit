<?php

namespace App\Http\Controllers;

use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Customer;
use App\Models\Test;
use Illuminate\Http\Request;

class SampleController extends Controller
{
    /**
     * Muestra el listado de muestras/protocolos
     */
    public function index(Request $request)
    {
        $query = Sample::with(['customer', 'determinations'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sample_type')) {
            $query->where('sample_type', $request->sample_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('protocol_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $samples = $query->paginate(15);

        return view('sample.index', compact('samples'));
    }

    /**
     * Muestra el formulario para crear una nueva muestra
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $tests = Test::orderBy('name')->get();

        return view('sample.create', compact('customers', 'tests'));
    }

    /**
     * Almacena una nueva muestra
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sample_type' => 'required|in:agua,alimento',
            'entry_date' => 'required|date',
            'sampling_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'location' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'batch' => 'nullable|string|max:100',
            'product_name' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'determinations' => 'required|array|min:1',
            'determinations.*' => 'exists:tests,id',
        ]);

        // Generar número de protocolo
        $validated['protocol_number'] = Sample::generateProtocolNumber();
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending';

        // Crear la muestra
        $sample = Sample::create($validated);

        // Agregar las determinaciones
        foreach ($request->determinations as $testId) {
            $test = Test::find($testId);
            SampleDetermination::create([
                'sample_id' => $sample->id,
                'test_id' => $testId,
                'unit' => $test->unit,
                'method' => $test->method,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('sample.show', $sample)
            ->with('success', 'Protocolo ' . $sample->protocol_number . ' creado correctamente.');
    }

    /**
     * Muestra los detalles de una muestra
     */
    public function show(Sample $sample)
    {
        $sample->load(['customer', 'determinations.test', 'creator']);
        
        return view('sample.show', compact('sample'));
    }

    /**
     * Muestra el formulario para editar una muestra
     */
    public function edit(Sample $sample)
    {
        $customers = Customer::orderBy('name')->get();
        $tests = Test::orderBy('name')->get();
        $sample->load('determinations');

        return view('sample.edit', compact('sample', 'customers', 'tests'));
    }

    /**
     * Actualiza una muestra
     */
    public function update(Request $request, Sample $sample)
    {
        $validated = $request->validate([
            'sample_type' => 'required|in:agua,alimento',
            'entry_date' => 'required|date',
            'sampling_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'location' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'batch' => 'nullable|string|max:100',
            'product_name' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $sample->update($validated);

        return redirect()->route('sample.show', $sample)
            ->with('success', 'Protocolo actualizado correctamente.');
    }

    /**
     * Agrega una determinación a una muestra existente
     */
    public function addDetermination(Request $request, Sample $sample)
    {
        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
        ]);

        // Verificar que no exista ya esta determinación
        $exists = $sample->determinations()->where('test_id', $validated['test_id'])->exists();
        
        if ($exists) {
            return back()->with('error', 'Esta determinación ya existe en el protocolo.');
        }

        $test = Test::find($validated['test_id']);
        
        SampleDetermination::create([
            'sample_id' => $sample->id,
            'test_id' => $validated['test_id'],
            'unit' => $test->unit,
            'method' => $test->method,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Determinación agregada correctamente.');
    }

    /**
     * Elimina una determinación de una muestra
     */
    public function removeDetermination(Sample $sample, SampleDetermination $determination)
    {
        if ($determination->sample_id !== $sample->id) {
            abort(403);
        }

        $determination->delete();

        return back()->with('success', 'Determinación eliminada correctamente.');
    }

    /**
     * Actualiza el resultado de una determinación
     */
    public function updateDetermination(Request $request, SampleDetermination $determination)
    {
        $validated = $request->validate([
            'result' => 'nullable|string|max:255',
            'reference_value' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        if ($validated['status'] === 'completed' && !$determination->analyzed_at) {
            $validated['analyzed_at'] = now();
            $validated['analyzed_by'] = auth()->id();
        }

        $determination->update($validated);

        // Verificar si todas las determinaciones están completadas
        $sample = $determination->sample;
        $allCompleted = $sample->determinations()->where('status', '!=', 'completed')->count() === 0;
        
        if ($allCompleted && $sample->status !== 'completed') {
            $sample->update(['status' => 'completed']);
        } elseif (!$allCompleted && $sample->status === 'pending') {
            $sample->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Determinación actualizada correctamente.');
    }
}
