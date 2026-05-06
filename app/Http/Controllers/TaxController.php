<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaxController extends Controller
{
    public function index(): View
    {
        $this->authorize('taxes.manage');

        $taxes = Tax::query()
            ->where('company_id', active_company_id())
            ->with('liabilityAccount')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25);

        return view('taxes.index', compact('taxes'));
    }

    public function create(): View
    {
        $this->authorize('taxes.manage');

        $liabilityAccounts = AccountingAccount::query()
            ->active()
            ->imputable()
            ->byType('pasivo')
            ->orderBy('code')
            ->get();

        return view('taxes.create', compact('liabilityAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('taxes.manage');

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('taxes', 'name')->where(fn ($q) => $q->where('company_id', active_company_id())),
            ],
            'jurisdiction' => ['nullable', 'string', 'max:255'],
            'liability_account_id' => ['required', 'exists:accounting_accounts,id'],
            'frequency' => ['required', 'in:monthly,quarterly,annual'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['company_id'] = active_company_id();
        $validated['sort_order'] = $validated['sort_order']
            ?? (Tax::where('company_id', active_company_id())->max('sort_order') + 10);
        $validated['is_active'] = $request->boolean('is_active', true);

        Tax::create($validated);

        return redirect()->route('taxes.index')->with('success', 'Impuesto creado.');
    }

    public function edit(Tax $tax): View
    {
        $this->authorize('taxes.manage');
        $this->ensureCompany($tax);

        $liabilityAccounts = AccountingAccount::query()
            ->active()
            ->imputable()
            ->byType('pasivo')
            ->orderBy('code')
            ->get();

        return view('taxes.edit', compact('tax', 'liabilityAccounts'));
    }

    public function update(Request $request, Tax $tax): RedirectResponse
    {
        $this->authorize('taxes.manage');
        $this->ensureCompany($tax);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('taxes', 'name')->where(fn ($q) => $q->where('company_id', active_company_id()))
                    ->ignore($tax->id),
            ],
            'jurisdiction' => ['nullable', 'string', 'max:255'],
            'liability_account_id' => ['required', 'exists:accounting_accounts,id'],
            'frequency' => ['required', 'in:monthly,quarterly,annual'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);

        $tax->update($validated);

        return redirect()->route('taxes.index')->with('success', 'Impuesto actualizado.');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        $this->authorize('taxes.manage');
        $this->ensureCompany($tax);

        if ($tax->perceptions()->exists() || $tax->taxReturns()->exists()) {
            return back()->with('error', 'No se puede eliminar: hay percepciones o declaraciones asociadas.');
        }

        $tax->delete();

        return redirect()->route('taxes.index')->with('success', 'Impuesto eliminado.');
    }

    private function ensureCompany(Tax $tax): void
    {
        abort_if((int) $tax->company_id !== (int) active_company_id(), 403);
    }
}
