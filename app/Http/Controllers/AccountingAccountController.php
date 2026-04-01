<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use Illuminate\Http\Request;

class AccountingAccountController extends Controller
{
    public function index()
    {
        $this->authorize('contabilidad.accounts.index');

        $accounts = AccountingAccount::with('children.children')
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();

        return view('accounting.accounts.index', compact('accounts'));
    }

    public function create()
    {
        $this->authorize('contabilidad.accounts.create');

        $parentAccounts = AccountingAccount::active()
            ->where('is_header', true)
            ->orderBy('code')
            ->get();

        $types = [
            'activo' => 'Activo',
            'pasivo' => 'Pasivo',
            'patrimonio_neto' => 'Patrimonio Neto',
            'resultado_positivo' => 'Resultado Positivo',
            'resultado_negativo' => 'Resultado Negativo',
        ];

        return view('accounting.accounts.create', compact('parentAccounts', 'types'));
    }

    public function store(Request $request)
    {
        $this->authorize('contabilidad.accounts.create');

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:accounting_accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:activo,pasivo,patrimonio_neto,resultado_positivo,resultado_negativo',
            'parent_id' => 'nullable|exists:accounting_accounts,id',
            'level' => 'required|integer|min:1|max:4',
            'is_header' => 'boolean',
        ]);

        if ($validated['parent_id']) {
            $parent = AccountingAccount::findOrFail($validated['parent_id']);
            $validated['type'] = $parent->type;
            $validated['level'] = $parent->level + 1;
        }

        $validated['is_header'] = $request->boolean('is_header');

        AccountingAccount::create($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Cuenta contable creada correctamente.');
    }

    public function edit(AccountingAccount $account)
    {
        $this->authorize('contabilidad.accounts.edit');

        $parentAccounts = AccountingAccount::active()
            ->where('is_header', true)
            ->where('id', '!=', $account->id)
            ->orderBy('code')
            ->get();

        $types = [
            'activo' => 'Activo',
            'pasivo' => 'Pasivo',
            'patrimonio_neto' => 'Patrimonio Neto',
            'resultado_positivo' => 'Resultado Positivo',
            'resultado_negativo' => 'Resultado Negativo',
        ];

        return view('accounting.accounts.edit', compact('account', 'parentAccounts', 'types'));
    }

    public function update(Request $request, AccountingAccount $account)
    {
        $this->authorize('contabilidad.accounts.edit');

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:accounting_accounts,code,' . $account->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:activo,pasivo,patrimonio_neto,resultado_positivo,resultado_negativo',
            'parent_id' => 'nullable|exists:accounting_accounts,id',
            'level' => 'required|integer|min:1|max:4',
            'is_header' => 'boolean',
        ]);

        if ($account->hasMovements() && $validated['type'] !== $account->type) {
            return back()->withErrors(['type' => 'No se puede cambiar el tipo de una cuenta con movimientos.']);
        }

        if ($validated['parent_id']) {
            $parent = AccountingAccount::findOrFail($validated['parent_id']);
            $validated['type'] = $parent->type;
            $validated['level'] = $parent->level + 1;
        }

        $validated['is_header'] = $request->boolean('is_header');

        $account->update($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Cuenta contable actualizada correctamente.');
    }

    public function destroy(AccountingAccount $account)
    {
        $this->authorize('contabilidad.accounts.delete');

        if ($account->hasMovements()) {
            return back()->withErrors(['delete' => 'No se puede desactivar una cuenta con movimientos contables.']);
        }

        if ($account->children()->where('is_active', true)->exists()) {
            return back()->withErrors(['delete' => 'No se puede desactivar una cuenta que tiene subcuentas activas.']);
        }

        $account->update(['is_active' => false]);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Cuenta contable desactivada correctamente.');
    }
}
