<?php

namespace App\Http\Controllers;

use App\Services\FinancialDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private FinancialDashboardService $service) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->employee && $user->roles->count() === 0 && $user->permissions->count() === 0) {
            return redirect()->route('portal.dashboard');
        }

        if ($user->hasAnyRole(['recepcion-lab', 'tecnico-lab', 'bioquimico'])
            && ! $user->hasAnyRole(['admin', 'contador', 'compras', 'ventas'])) {
            return redirect()->route('lab.section.clinico');
        }

        if ($user->hasRole('compras')
            && ! $user->hasAnyRole(['admin', 'contador'])) {
            return redirect()->route('purchases.section');
        }

        if ($user->hasRole('ventas')
            && ! $user->hasAnyRole(['admin', 'contador'])) {
            return redirect()->route('sales.section');
        }

        $companyId = active_company_id();

        $ventas = $this->service->ventas($companyId);
        $compras = $this->service->compras($companyId);
        $ingresos = $this->service->ingresos($companyId);
        $egresos = $this->service->egresos($companyId);

        return view('dashboard', compact('ventas', 'compras', 'ingresos', 'egresos'));
    }
}
