<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Services\BankStatementImporter;
use Illuminate\Http\Request;

class BankStatementController extends Controller
{
    public function index(BankAccount $bankAccount)
    {
        $this->authorize('contabilidad.bank_statements.index');

        $statements = $bankAccount->statements()
            ->orderByDesc('period_to')
            ->get();

        return view('accounting.bank-statements.index', compact('bankAccount', 'statements'));
    }

    public function create(BankAccount $bankAccount)
    {
        $this->authorize('contabilidad.bank_statements.import');

        return view('accounting.bank-statements.create', compact('bankAccount'));
    }

    public function store(Request $request, BankAccount $bankAccount)
    {
        $this->authorize('contabilidad.bank_statements.import');

        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx|max:5120',
        ]);

        try {
            $importer = new BankStatementImporter;
            $statement = $importer->import($bankAccount, $request->file('file'), auth()->user());

            return redirect()->route('accounting.bank-statements.show', [$bankAccount, $statement])
                ->with('success', "Extracto importado: {$statement->movements_count} movimientos del {$statement->period_from->format('d/m/Y')} al {$statement->period_to->format('d/m/Y')}.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(BankAccount $bankAccount, BankStatement $statement)
    {
        $this->authorize('contabilidad.bank_statements.index');

        $query = $statement->movements()->orderByDesc('date')->orderByDesc('id');

        $filterCategory = request('category');
        $filterType = request('type');
        $filterStatus = request('status');

        if ($filterCategory) {
            $query->where('category', $filterCategory);
        }
        if ($filterType === 'credit') {
            $query->where('credit', '>', 0);
        } elseif ($filterType === 'debit') {
            $query->where('debit', '>', 0);
        }
        if ($filterStatus) {
            $query->where('reconciliation_status', $filterStatus);
        }

        $movements = $query->get();

        $categories = $statement->movements()
            ->whereNotNull('category')
            ->selectRaw('category, count(*) as cnt, sum(credit) as total_credit, sum(debit) as total_debit')
            ->groupBy('category')
            ->orderByDesc('cnt')
            ->get();

        return view('accounting.bank-statements.show', compact('bankAccount', 'statement', 'movements', 'categories', 'filterCategory', 'filterType', 'filterStatus'));
    }

    public function destroy(BankAccount $bankAccount, BankStatement $statement)
    {
        $this->authorize('contabilidad.bank_statements.delete');

        if ($statement->movements()->where('reconciliation_status', 'matched')->exists()) {
            return back()->with('error', 'No se puede eliminar un extracto con movimientos conciliados.');
        }

        $statement->delete();

        return redirect()->route('accounting.bank-statements.index', $bankAccount)
            ->with('success', 'Extracto eliminado correctamente.');
    }
}
