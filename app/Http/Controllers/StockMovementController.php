<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Supply;
use App\Services\LabBranchResolver;
use App\Services\SupplyStockService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('stock-movements.index');

        $query = StockMovement::with(['supply', 'user', 'labBranch'])
            ->orderByDesc('created_at');

        if ($request->filled('lab_branch_id')) {
            $query->where('lab_branch_id', $request->lab_branch_id);
        }

        if ($request->filled('supply_id')) {
            $query->where('supply_id', $request->supply_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->paginate(20)->withQueryString();
        $supplies = Supply::orderBy('name')->get(['id', 'code', 'name']);
        $branches = LabBranchResolver::activeBranchesForForms();

        return view('stock-movements.index', compact('movements', 'supplies', 'branches'));
    }

    public function create()
    {
        $this->authorize('stock-movements.create');

        $supplies = Supply::active()->orderBy('name')->get();
        $branches = LabBranchResolver::activeBranchesForForms();

        return view('stock-movements.create', compact('supplies', 'branches'));
    }

    public function store(Request $request)
    {
        $this->authorize('stock-movements.create');

        $isAjuste = $request->input('type') === 'ajuste';

        $validated = $request->validate([
            'supply_id' => 'required|exists:supplies,id',
            'lab_branch_id' => [
                'required',
                'integer',
                Rule::exists('lab_branches', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'type' => 'required|in:entrada,salida,ajuste',
            'quantity' => [
                'required',
                'integer',
                Rule::when($isAjuste, ['min:0'], ['min:1']),
            ],
            'lot_number' => 'nullable|string|max:100',
            'expiration_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'supply_id.required' => 'Debe seleccionar un insumo.',
            'lab_branch_id.required' => 'Seleccioná la sede / depósito.',
            'lab_branch_id.exists' => 'La sede no es válida o está inactiva.',
            'type.required' => 'Debe seleccionar el tipo de movimiento.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
            'quantity.min' => $isAjuste
                ? 'La cantidad debe ser un número entero mayor o igual a 0.'
                : 'La cantidad debe ser al menos 1.',
        ]);

        $supply = Supply::findOrFail($validated['supply_id']);

        try {
            $branch = LabBranchResolver::requireDocumentBranch((int) $validated['lab_branch_id']);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        $payload = [
            'reason' => 'ajuste_manual',
            'lot_number' => $validated['lot_number'] ?? null,
            'expiration_date' => $validated['expiration_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'user_id' => auth()->id(),
        ];

        $stockSvc = app(SupplyStockService::class);

        try {
            match ($validated['type']) {
                'entrada' => $stockSvc->recordEntrada($supply, $branch->id, (float) $validated['quantity'], $payload),
                'salida' => $stockSvc->recordSalida($supply, $branch->id, (float) $validated['quantity'], $payload),
                'ajuste' => $stockSvc->recordAjuste($supply, $branch->id, (float) $validated['quantity'], $payload),
            };
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()
                ->withErrors($e->errors());
        }

        $supply->refresh();

        return redirect()->route('stock-movements.index')
            ->with('success', 'Movimiento de stock registrado. Nuevo stock global de "'.$supply->name.'": '.$supply->stock);
    }
}
