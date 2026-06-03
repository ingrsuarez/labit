<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Services\SupplyLotBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('supplies.index');

        $query = Supply::with(['category', 'defaultSupplier', 'branchStocks.labBranch'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('supply_category_id', $request->category);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->whereColumn('stock', '<=', 'min_stock');
            } elseif ($request->stock_status === 'ok') {
                $query->whereColumn('stock', '>', 'min_stock');
            } elseif ($request->stock_status === 'zero') {
                $query->where('stock', '<=', 0);
            }
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active === '1');
        }

        $supplies = $query->paginate(15)->withQueryString();
        $categories = SupplyCategory::active()->orderBy('name')->get();

        $lowStockCount = Supply::active()->whereColumn('stock', '<=', 'min_stock')->count();

        return view('supplies.index', compact('supplies', 'categories', 'lowStockCount'));
    }

    public function create()
    {
        $this->authorize('supplies.create');

        $categories = SupplyCategory::active()->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('supplies.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request)
    {
        $this->authorize('supplies.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'supply_category_id' => 'nullable|exists:supply_categories,id',
            'unit' => 'required|string|max:50',
            'min_stock' => 'nullable|numeric|min:0',
            'default_supplier_id' => 'nullable|exists:suppliers,id',
        ], [
            'name.required' => 'El nombre del insumo es obligatorio.',
            'unit.required' => 'La unidad de medida es obligatoria.',
        ]);

        $validated['code'] = Supply::generateCode($validated['supply_category_id'] ?? null);
        $validated['stock'] = 0;
        $validated['last_price'] = 0;
        $validated['is_active'] = true;
        $validated['tracks_lot'] = $request->boolean('tracks_lot');
        $validated['min_stock'] = $validated['min_stock'] ?? 0;

        $supply = Supply::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'id' => $supply->id,
                'code' => $supply->code,
                'name' => $supply->name,
                'unit' => $supply->unit,
                'tracks_lot' => $supply->tracks_lot,
            ], 201);
        }

        return redirect()->route('supplies.index')
            ->with('success', 'Insumo "'.$supply->name.'" creado correctamente.');
    }

    public function show(Supply $supply)
    {
        $this->authorize('supplies.index');

        $supply->load(['category', 'defaultSupplier', 'branchStocks.labBranch']);
        $movements = $supply->stockMovements()
            ->with(['user', 'labBranch'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('supplies.show', compact('supply', 'movements'));
    }

    public function edit(Supply $supply)
    {
        $this->authorize('supplies.edit');

        $categories = SupplyCategory::active()->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('supplies.edit', compact('supply', 'categories', 'suppliers'));
    }

    public function update(Request $request, Supply $supply)
    {
        $this->authorize('supplies.edit');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'supply_category_id' => 'nullable|exists:supply_categories,id',
            'unit' => 'required|string|max:50',
            'min_stock' => 'nullable|numeric|min:0',
            'default_supplier_id' => 'nullable|exists:suppliers,id',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'El nombre del insumo es obligatorio.',
            'unit.required' => 'La unidad de medida es obligatoria.',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['tracks_lot'] = $request->boolean('tracks_lot');
        $validated['min_stock'] = $validated['min_stock'] ?? 0;

        $supply->update($validated);

        return redirect()->route('supplies.index')
            ->with('success', 'Insumo actualizado correctamente.');
    }

    public function destroy(Supply $supply)
    {
        $this->authorize('supplies.delete');

        if ($supply->stockMovements()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un insumo que tiene movimientos de stock.');
        }

        $name = $supply->name;
        $supply->delete();

        return redirect()->route('supplies.index')
            ->with('success', "Insumo \"{$name}\" eliminado.");
    }

    public function search(Request $request)
    {
        $this->authorize('supplies.index');

        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $supplies = Supply::active()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'code', 'name', 'brand', 'unit', 'stock', 'last_price', 'tracks_lot']);

        return response()->json($supplies);
    }

    /**
     * Lotes con saldo > 0 para un insumo en una sede (movimientos manuales / trazabilidad).
     */
    public function availableLots(Request $request, Supply $supply)
    {
        $this->authorize('stock-movements.create');

        if (! $supply->is_active) {
            abort(404);
        }

        $validated = $request->validate([
            'lab_branch_id' => [
                'required',
                'integer',
                Rule::exists('lab_branches', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
        ]);

        $rows = app(SupplyLotBalanceService::class)->availableLots(
            $supply->id,
            (int) $validated['lab_branch_id']
        );

        return response()->json(
            $rows->map(fn (object $r) => [
                'lot_number' => $r->lot_number,
                'expiration_date' => $r->expiration_date,
                'quantity' => max(0, (int) round($r->quantity)),
            ])->values()->all()
        );
    }

    /**
     * Conteos de referencias del insumo origen para mostrar en el preview del modal de unificación.
     */
    public function mergePreview(Request $request, Supply $supply): JsonResponse
    {
        $this->authorize('supplies.edit');

        return response()->json([
            'stock_movements' => DB::table('stock_movements')->where('supply_id', $supply->id)->count(),
            'purchase_invoice_items' => DB::table('purchase_invoice_items')->where('supply_id', $supply->id)->count(),
            'purchase_order_items' => DB::table('purchase_order_items')->where('supply_id', $supply->id)->count(),
            'delivery_note_items' => DB::table('delivery_note_items')->where('supply_id', $supply->id)->count(),
            'purchase_quotation_request_items' => DB::table('purchase_quotation_request_items')->where('supply_id', $supply->id)->count(),
            'purchase_credit_note_items' => DB::table('purchase_credit_note_items')->where('supply_id', $supply->id)->count(),
            'branch_stocks' => DB::table('supply_lab_branch_stock')->where('supply_id', $supply->id)->count(),
        ]);
    }

    /**
     * Unifica el insumo origen ($supply) en el destino: reasigna referencias, suma stock y desactiva el origen.
     * Operación irreversible — se ejecuta en una sola transacción.
     */
    public function merge(Request $request, Supply $supply): RedirectResponse
    {
        $this->authorize('supplies.edit');

        $request->validate([
            'target_id' => [
                'required',
                'integer',
                'exists:supplies,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($supply): void {
                    if ((int) $value === $supply->id) {
                        $fail('El insumo destino debe ser diferente al origen.');
                    }
                },
            ],
        ]);

        $target = Supply::findOrFail((int) $request->target_id);

        if (! $target->is_active) {
            return back()->with('error', 'El insumo destino está inactivo.');
        }

        DB::transaction(function () use ($supply, $target): void {
            DB::table('stock_movements')->where('supply_id', $supply->id)->update(['supply_id' => $target->id]);
            DB::table('purchase_invoice_items')->where('supply_id', $supply->id)->update(['supply_id' => $target->id]);
            DB::table('purchase_order_items')->where('supply_id', $supply->id)->update(['supply_id' => $target->id]);
            DB::table('delivery_note_items')->where('supply_id', $supply->id)->update(['supply_id' => $target->id]);
            DB::table('purchase_quotation_request_items')->where('supply_id', $supply->id)->update(['supply_id' => $target->id]);
            DB::table('purchase_credit_note_items')->where('supply_id', $supply->id)->whereNotNull('supply_id')->update(['supply_id' => $target->id]);

            $sourceBranchStocks = DB::table('supply_lab_branch_stock')
                ->where('supply_id', $supply->id)
                ->get();

            foreach ($sourceBranchStocks as $row) {
                $existing = DB::table('supply_lab_branch_stock')
                    ->where('supply_id', $target->id)
                    ->where('lab_branch_id', $row->lab_branch_id)
                    ->first();

                if ($existing) {
                    DB::table('supply_lab_branch_stock')
                        ->where('supply_id', $target->id)
                        ->where('lab_branch_id', $row->lab_branch_id)
                        ->update(['quantity' => $existing->quantity + $row->quantity]);
                    DB::table('supply_lab_branch_stock')
                        ->where('supply_id', $supply->id)
                        ->where('lab_branch_id', $row->lab_branch_id)
                        ->delete();
                } else {
                    DB::table('supply_lab_branch_stock')
                        ->where('supply_id', $supply->id)
                        ->where('lab_branch_id', $row->lab_branch_id)
                        ->update(['supply_id' => $target->id]);
                }
            }

            $target->increment('stock', (float) $supply->stock);

            if ((float) $supply->last_price > (float) $target->last_price) {
                $target->update(['last_price' => $supply->last_price]);
            }

            $supply->update([
                'is_active' => false,
                'name' => '[UNIFICADO→'.$target->code.'] '.$supply->name,
            ]);
        });

        return redirect()->route('supplies.index')
            ->with('success', "Insumo \"{$supply->name}\" unificado en \"{$target->name}\" correctamente.");
    }
}
