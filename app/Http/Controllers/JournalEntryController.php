<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\CollectionReceipt;
use App\Models\CreditNote;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PaymentOrder;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryController extends Controller
{
    protected function accountingCompanyId(): int
    {
        $id = active_company_id() ?? auth()->user()->companies()->first()?->id;
        abort_if($id === null, 403, 'No hay empresa activa.');

        return (int) $id;
    }

    protected function ensureJournalCompany(JournalEntry $journalEntry): void
    {
        abort_unless((int) $journalEntry->company_id === $this->accountingCompanyId(), 403);
    }

    /**
     * @return array{label: string, url: ?string}|null
     */
    public static function sourcePresentation(?string $sourceType, ?int $sourceId): ?array
    {
        if ($sourceType === null || $sourceId === null) {
            return null;
        }

        return match ($sourceType) {
            SalesInvoice::class => ['label' => 'FC Venta', 'url' => route('sales-invoices.show', $sourceId)],
            PurchaseInvoice::class => ['label' => 'FC Compra', 'url' => route('purchase-invoices.show', $sourceId)],
            CollectionReceipt::class => ['label' => 'Recibo de cobro', 'url' => route('collection-receipts.show', $sourceId)],
            CreditNote::class => ['label' => 'Nota de crédito', 'url' => route('credit-notes.show', $sourceId)],
            PaymentOrder::class => ['label' => 'Orden de pago', 'url' => route('payment-orders.show', $sourceId)],
            default => ['label' => class_basename($sourceType).' #'.$sourceId, 'url' => null],
        };
    }

    public function index(Request $request)
    {
        $this->authorize('contabilidad.entries.index');

        $companyId = $this->accountingCompanyId();

        $query = JournalEntry::with(['lines.account', 'creator'])
            ->where('company_id', $companyId)
            ->orderByDesc('date')
            ->orderByDesc('number');

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('type')) {
            $query->where('is_automatic', $request->type === 'automatic');
        }
        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('entry_number')) {
            $query->where('number', (int) $request->entry_number);
        }

        $entries = $query->paginate(20)->withQueryString();

        return view('accounting.journal.index', compact('entries'));
    }

    public function create()
    {
        $this->authorize('contabilidad.entries.create');

        $accounts = AccountingAccount::active()->imputable()->orderBy('code')->get(['id', 'code', 'name']);

        return view('accounting.journal.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $this->authorize('contabilidad.entries.create');

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.accounting_account_id' => 'required|exists:accounting_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        $lines = collect($request->lines)->map(fn ($l) => [
            'accounting_account_id' => (int) $l['accounting_account_id'],
            'debit' => (float) ($l['debit'] ?? 0),
            'credit' => (float) ($l['credit'] ?? 0),
            'description' => $l['description'] ?? null,
        ]);

        foreach ($lines as $i => $line) {
            if ($line['debit'] <= 0 && $line['credit'] <= 0) {
                throw ValidationException::withMessages([
                    'lines' => 'Cada línea debe tener importe en Debe o en Haber (fila '.($i + 1).').',
                ]);
            }
            if ($line['debit'] > 0 && $line['credit'] > 0) {
                throw ValidationException::withMessages([
                    'lines' => 'No puede haber importe en Debe y Haber a la vez (fila '.($i + 1).').',
                ]);
            }
        }

        $totalDebit = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withInput()
                ->withErrors(['lines' => 'El asiento no está balanceado: Debe $'.number_format($totalDebit, 2).' ≠ Haber $'.number_format($totalCredit, 2)]);
        }

        $companyId = $this->accountingCompanyId();
        $date = Carbon::parse($request->date);

        DB::transaction(function () use ($lines, $companyId, $date, $request) {
            $entry = JournalEntry::create([
                'company_id' => $companyId,
                'date' => $date,
                'number' => JournalEntry::nextNumber($companyId, (int) $date->format('Y')),
                'description' => $request->description,
                'source_type' => null,
                'source_id' => null,
                'is_automatic' => false,
                'created_by' => auth()->id(),
            ]);

            foreach ($lines as $line) {
                JournalEntryLine::create(array_merge($line, ['journal_entry_id' => $entry->id]));
            }
        });

        return redirect()->route('accounting.journal.index')
            ->with('success', 'Asiento registrado correctamente.');
    }

    public function edit(JournalEntry $journal)
    {
        $this->authorize('contabilidad.entries.edit');
        $this->ensureJournalCompany($journal);

        if ($journal->is_automatic) {
            return redirect()->route('accounting.journal.index')
                ->with('error', 'Los asientos automáticos no se pueden editar.');
        }

        $journal->load('lines');
        $accounts = AccountingAccount::active()->imputable()->orderBy('code')->get(['id', 'code', 'name']);

        return view('accounting.journal.edit', compact('journal', 'accounts'));
    }

    public function update(Request $request, JournalEntry $journal)
    {
        $this->authorize('contabilidad.entries.edit');
        $this->ensureJournalCompany($journal);

        if ($journal->is_automatic) {
            return redirect()->route('accounting.journal.index')
                ->with('error', 'Los asientos automáticos no se pueden editar.');
        }

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.accounting_account_id' => 'required|exists:accounting_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        $lines = collect($request->lines)->map(fn ($l) => [
            'accounting_account_id' => (int) $l['accounting_account_id'],
            'debit' => (float) ($l['debit'] ?? 0),
            'credit' => (float) ($l['credit'] ?? 0),
            'description' => $l['description'] ?? null,
        ]);

        foreach ($lines as $i => $line) {
            if ($line['debit'] <= 0 && $line['credit'] <= 0) {
                throw ValidationException::withMessages([
                    'lines' => 'Cada línea debe tener importe en Debe o en Haber (fila '.($i + 1).').',
                ]);
            }
            if ($line['debit'] > 0 && $line['credit'] > 0) {
                throw ValidationException::withMessages([
                    'lines' => 'No puede haber importe en Debe y Haber a la vez (fila '.($i + 1).').',
                ]);
            }
        }

        $totalDebit = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withInput()
                ->withErrors(['lines' => 'El asiento no está balanceado: Debe $'.number_format($totalDebit, 2).' ≠ Haber $'.number_format($totalCredit, 2)]);
        }

        $date = Carbon::parse($request->date);

        DB::transaction(function () use ($lines, $journal, $date, $request) {
            $journal->lines()->delete();
            $journal->update([
                'date' => $date,
                'description' => $request->description,
            ]);
            foreach ($lines as $line) {
                JournalEntryLine::create(array_merge($line, ['journal_entry_id' => $journal->id]));
            }
        });

        return redirect()->route('accounting.journal.index')
            ->with('success', 'Asiento actualizado.');
    }

    public function destroy(JournalEntry $journal)
    {
        $this->authorize('contabilidad.entries.delete');
        $this->ensureJournalCompany($journal);

        if ($journal->is_automatic) {
            return redirect()->route('accounting.journal.index')
                ->with('error', 'Los asientos automáticos no se pueden eliminar.');
        }

        $journal->lines()->delete();
        $journal->delete();

        return redirect()->route('accounting.journal.index')
            ->with('success', 'Asiento eliminado.');
    }
}
