<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountingLedgerController extends Controller
{
    protected function accountingCompanyId(): int
    {
        $id = active_company_id() ?? auth()->user()->companies()->first()?->id;
        abort_if($id === null, 403, 'No hay empresa activa.');

        return (int) $id;
    }

    public function index(Request $request)
    {
        $this->authorize('contabilidad.ledger.index');

        $companyId = $this->accountingCompanyId();
        $accounts = AccountingAccount::active()->imputable()->orderBy('code')->get(['id', 'code', 'name', 'type']);

        $movements = collect();
        $account = null;
        $openBalance = 0.0;
        $closeBalance = 0.0;
        $deudora = true;

        if ($request->filled('account_id')) {
            $account = AccountingAccount::query()->findOrFail($request->account_id);

            $year = $request->integer('year', now()->year);
            $month = $request->integer('month', now()->month);

            $periodStart = Carbon::create($year, $month, 1)->startOfDay();
            $periodEnd = $periodStart->copy()->endOfMonth()->endOfDay();

            $before = JournalEntryLine::query()
                ->where('accounting_account_id', $account->id)
                ->whereHas('journalEntry', fn ($q) => $q
                    ->where('company_id', $companyId)
                    ->whereDate('date', '<', $periodStart->toDateString())
                )
                ->selectRaw('COALESCE(SUM(debit),0) as total_debit, COALESCE(SUM(credit),0) as total_credit')
                ->first();

            $deudora = in_array($account->type, ['activo', 'resultado_negativo'], true);
            $td = (float) ($before->total_debit ?? 0);
            $tc = (float) ($before->total_credit ?? 0);
            $openBalance = $deudora ? ($td - $tc) : ($tc - $td);

            $lines = JournalEntryLine::query()
                ->where('accounting_account_id', $account->id)
                ->whereHas('journalEntry', fn ($q) => $q
                    ->where('company_id', $companyId)
                    ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                )
                ->with(['journalEntry' => fn ($q) => $q->select('id', 'date', 'number', 'description', 'source_type', 'source_id', 'is_automatic')])
                ->get();

            $lines = $lines->sortBy(function (JournalEntryLine $line) {
                $je = $line->journalEntry;

                return $je->date->format('Y-m-d').'-'.str_pad((string) $je->number, 8, '0', STR_PAD_LEFT).'-'.$line->id;
            })->values();

            $running = $openBalance;
            $movements = $lines->map(function (JournalEntryLine $line) use ($deudora, &$running) {
                $delta = $deudora
                    ? ((float) $line->debit - (float) $line->credit)
                    : ((float) $line->credit - (float) $line->debit);
                $running += $delta;
                $line->running_balance = $running;

                return $line;
            });

            $closeBalance = $running;
        }

        return view('accounting.ledger.index', compact(
            'accounts', 'account', 'movements', 'openBalance', 'closeBalance', 'deudora'
        ));
    }
}
