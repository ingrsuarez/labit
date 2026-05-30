<?php

namespace App\Http\Controllers;

use App\Services\FinancialDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private FinancialDashboardService $service) {}

    public function financial(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'contador'])) {
            abort(403);
        }

        $companyId = active_company_id();

        $ventas = $this->service->ventas($companyId);
        $compras = $this->service->compras($companyId);
        $ingresos = $this->service->ingresos($companyId);
        $egresos = $this->service->egresos($companyId);

        return view('dashboard', compact('ventas', 'compras', 'ingresos', 'egresos'));
    }
}
