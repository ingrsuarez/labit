<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\BankAccount;
use App\Models\Company;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $this->authorize('contabilidad.bank_accounts.index');

        $companyId = session('current_company_id');
        $query = BankAccount::with('accountingAccount')->withCount('statements')->orderBy('bank_name');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $accounts = $query->get();

        return view('accounting.bank-accounts.index', compact('accounts'));
    }

    public function create()
    {
        $this->authorize('contabilidad.bank_accounts.create');

        $companies = auth()->user()->companies ?? Company::where('is_active', true)->get();
        $accountingAccounts = AccountingAccount::active()
            ->imputable()
            ->byType('activo')
            ->where('code', 'like', '1.1.0%')
            ->orderBy('code')
            ->get();

        return view('accounting.bank-accounts.create', compact('companies', 'accountingAccounts'));
    }

    public function store(Request $request)
    {
        $this->authorize('contabilidad.bank_accounts.create');

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_type' => 'required|in:cuenta_corriente,caja_ahorro',
            'cbu' => 'nullable|string|size:22',
            'alias' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:3',
            'accounting_account_id' => 'nullable|exists:accounting_accounts,id',
        ]);

        $validated['currency'] = $validated['currency'] ?? 'ARS';

        $exists = BankAccount::where('company_id', $validated['company_id'])
            ->where('account_number', $validated['account_number'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['account_number' => 'Ya existe una cuenta con ese número para esta empresa.'])->withInput();
        }

        BankAccount::create($validated);

        return redirect()->route('accounting.bank-accounts.index')
            ->with('success', 'Cuenta bancaria creada correctamente.');
    }

    public function edit(BankAccount $bank_account)
    {
        $this->authorize('contabilidad.bank_accounts.edit');

        $companies = auth()->user()->companies ?? Company::where('is_active', true)->get();
        $accountingAccounts = AccountingAccount::active()
            ->imputable()
            ->byType('activo')
            ->where('code', 'like', '1.1.0%')
            ->orderBy('code')
            ->get();

        return view('accounting.bank-accounts.edit', compact('bank_account', 'companies', 'accountingAccounts'));
    }

    public function update(Request $request, BankAccount $bank_account)
    {
        $this->authorize('contabilidad.bank_accounts.edit');

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_type' => 'required|in:cuenta_corriente,caja_ahorro',
            'cbu' => 'nullable|string|size:22',
            'alias' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:3',
            'accounting_account_id' => 'nullable|exists:accounting_accounts,id',
        ]);

        $exists = BankAccount::where('company_id', $validated['company_id'])
            ->where('account_number', $validated['account_number'])
            ->where('id', '!=', $bank_account->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['account_number' => 'Ya existe otra cuenta con ese número para esta empresa.'])->withInput();
        }

        $bank_account->update($validated);

        return redirect()->route('accounting.bank-accounts.index')
            ->with('success', 'Cuenta bancaria actualizada correctamente.');
    }

    public function destroy(BankAccount $bank_account)
    {
        $this->authorize('contabilidad.bank_accounts.delete');

        if ($bank_account->statements()->exists()) {
            return back()->with('error', 'No se puede eliminar una cuenta con extractos importados. Desactivela en su lugar.');
        }

        $bank_account->update(['is_active' => false]);

        return redirect()->route('accounting.bank-accounts.index')
            ->with('success', 'Cuenta bancaria desactivada.');
    }
}
