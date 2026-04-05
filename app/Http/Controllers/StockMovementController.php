<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Supply;
use App\Services\LabBranchResolver;
use App\Services\SupplyStockService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('stock-movements.index');

        $query = StockMovement::with(['supply', 'user'])
            ->orderByDesc('created_at');

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

        return view('stock-movements.index', compact('movements', 'supplies'));
    }

    public function create()
    {
        $this->authorize('stock-movements.create');

        $supplies = Supply::active()->orderBy('name')->get();

        return view('stock-movements.create', compact('supplies'));
    }

    public function store(Request $request)
    {
        $this->authorize('stock-movements.create');

        $validated = $request->validate([
            'supply_id' => 'required|exists:supplies,id',
            'type' => 'required|in:entrada,salida,ajuste',
            'quantity' => 'required|numeric|min:0.01',
            'lot_number' => 'nullable|string|max:100',
            'expiration_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'supply_id.required' => 'Debe seleccionar un insumo.',
            'type.required' => 'Debe seleccionar el tipo de movimiento.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
        ]);

        $supply = Supply::findOrFail($validated['supply_id']);
        $branch = LabBranchResolver::requireActiveBranchForStock();

        $payload = [
            'reason' => 'ajuste_manual',
            'lot_number' => $validated['lot_number'] ?? null,
            'expiration_date' => $validated['expiration_date'] ?? null,
            'notes' => $validated['notes'],
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
