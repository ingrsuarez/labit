<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\BankMovement;
use App\Models\JournalEntry;

class AccountingSectionController extends Controller
{
    public function index()
    {
        $this->authorize('contabilidad.section');

        $totalAccounts = AccountingAccount::active()->count();
        $pendingMovements = BankMovement::where('reconciliation_status', 'pending')->count();

        $companyId = active_company_id() ?? auth()->user()->companies()->first()?->id;
        $entriesThisMonth = 0;
        $autoCount = 0;
        $manualCount = 0;
        if ($companyId) {
            $entriesThisMonth = JournalEntry::query()
                ->where('company_id', $companyId)
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->count();
            $autoCount = JournalEntry::query()
                ->where('company_id', $companyId)
                ->where('is_automatic', true)
                ->count();
            $manualCount = JournalEntry::query()
                ->where('company_id', $companyId)
                ->where('is_automatic', false)
                ->count();
        }

        $section = [
            'title' => 'Contabilidad',
            'description' => 'Plan de cuentas, libro diario y reportes contables',
            'stats' => [
                ['label' => 'Cuentas activas', 'value' => $totalAccounts],
                ['label' => 'Asientos este mes (empresa activa)', 'value' => $entriesThisMonth],
                ['label' => 'Asientos automáticos', 'value' => $autoCount],
                ['label' => 'Asientos manuales', 'value' => $manualCount],
                ['label' => 'Mov. pendientes conciliar', 'value' => $pendingMovements],
            ],
            'items' => [
                [
                    'name' => 'Plan de Cuentas',
                    'description' => 'Estructura de cuentas contables',
                    'route' => route('accounting.accounts.index'),
                    'icon' => 'M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
                    'active' => true,
                ],
                [
                    'name' => 'Conciliación Bancaria',
                    'description' => 'Cuentas bancarias y extractos',
                    'route' => route('accounting.bank-accounts.index'),
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                    'active' => true,
                ],
                [
                    'name' => 'Libro Diario',
                    'description' => 'Registro cronológico de asientos',
                    'route' => route('accounting.journal.index'),
                    'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                    'active' => true,
                ],
                [
                    'name' => 'Libro Mayor',
                    'description' => 'Movimientos por cuenta',
                    'route' => route('accounting.ledger'),
                    'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'active' => true,
                ],
                [
                    'name' => 'Tesorería',
                    'description' => 'Movimientos de caja y bancos',
                    'route' => null,
                    'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                    'active' => false,
                ],
            ],
        ];

        return view('accounting.index', compact('section'));
    }
}
