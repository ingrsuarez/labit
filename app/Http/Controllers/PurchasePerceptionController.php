<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\PurchasePerception;
use App\Services\PurchasePerceptionBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchasePerceptionController extends Controller
{
    public function index(): View
    {
        $this->authorize('purchase-perceptions.index');

        $perceptions = PurchasePerception::where('company_id', active_company_id())
            ->with('accountingAccount')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        $accounts = AccountingAccount::where('code', 'like', '1.1.%')
            ->active()
            ->orderBy('code')
            ->get();

        return view('purchase-perceptions.index', compact('perceptions', 'accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('purchase-perceptions.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'jurisdiction' => ['nullable', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0'],
            'accounting_account_id' => ['required', 'exists:accounting_accounts,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['company_id'] = active_company_id();
        $validated['sort_order'] = $validated['sort_order']
            ?? (PurchasePerception::where('company_id', active_company_id())->max('sort_order') + 10);
        $validated['is_active'] = isset($validated['is_active']) ? (bool) $validated['is_active'] : true;

        PurchasePerception::create($validated);

        return redirect()->route('purchase-perceptions.index')
            ->with('success', 'Percepción creada correctamente.');
    }

    public function edit(PurchasePerception $purchasePerception): View
    {
        $this->authorize('purchase-perceptions.edit');
        $this->ensureCompany($purchasePerception);

        $accounts = AccountingAccount::where('code', 'like', '1.1.%')
            ->active()
            ->orderBy('code')
            ->get();

        return view('purchase-perceptions.edit', compact('purchasePerception', 'accounts'));
    }

    public function update(Request $request, PurchasePerception $purchasePerception): RedirectResponse
    {
        $this->authorize('purchase-perceptions.edit');
        $this->ensureCompany($purchasePerception);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'jurisdiction' => ['nullable', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0'],
            'accounting_account_id' => ['required', 'exists:accounting_accounts,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = isset($validated['is_active']) ? (bool) $validated['is_active'] : false;

        $purchasePerception->update($validated);

        return redirect()->route('purchase-perceptions.index')
            ->with('success', 'Percepción actualizada correctamente.');
    }

    public function destroy(PurchasePerception $purchasePerception): RedirectResponse
    {
        $this->authorize('purchase-perceptions.destroy');
        $this->ensureCompany($purchasePerception);

        if ($purchasePerception->invoicePerceptions()->exists()) {
            return back()->with('error', 'No se puede eliminar esta percepción porque tiene líneas históricas en facturas.');
        }

        $purchasePerception->delete();

        return redirect()->route('purchase-perceptions.index')
            ->with('success', 'Percepción eliminada.');
    }

    public function toggleActive(PurchasePerception $purchasePerception): RedirectResponse
    {
        $this->authorize('purchase-perceptions.edit');
        $this->ensureCompany($purchasePerception);

        $purchasePerception->update(['is_active' => ! $purchasePerception->is_active]);

        $label = $purchasePerception->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Percepción {$label}.");
    }

    public function balances(Request $request): View
    {
        $this->authorize('purchase-perceptions.index');

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        $balances = (new PurchasePerceptionBalanceService)
            ->getBalances(active_company_id(), $from, $to);

        return view('purchase-perceptions.balances', compact('balances', 'from', 'to'));
    }

    private function ensureCompany(PurchasePerception $perception): void
    {
        abort_if($perception->company_id !== active_company_id(), 403);
    }
}
