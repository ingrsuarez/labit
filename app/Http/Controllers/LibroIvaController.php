<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Services\LibroIvaService;
use Illuminate\Http\Request;

class LibroIvaController extends Controller
{
    public function index()
    {
        $this->authorize('ventas.section');

        return view('libro-iva.index');
    }

    public function preview(Request $request)
    {
        $this->authorize('ventas.section');
        $request->validate([
            'year'  => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $companyId = active_company_id();
        $year = (int) $request->year;
        $month = (int) $request->month;

        $ventasCount = SalesInvoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)->whereMonth('issue_date', $month)
            ->whereNotNull('cae')->count();

        $ncCount = CreditNote::where('company_id', $companyId)
            ->whereYear('issue_date', $year)->whereMonth('issue_date', $month)
            ->whereNotNull('cae')->count();

        $comprasCount = PurchaseInvoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)->whereMonth('issue_date', $month)->count();

        $ventasTotal = SalesInvoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)->whereMonth('issue_date', $month)
            ->whereNotNull('cae')->sum('total');

        $ncTotal = CreditNote::where('company_id', $companyId)
            ->whereYear('issue_date', $year)->whereMonth('issue_date', $month)
            ->whereNotNull('cae')->sum('total');

        $comprasTotal = PurchaseInvoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)->whereMonth('issue_date', $month)->sum('total');

        $debitoFiscal = SalesInvoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)->whereMonth('issue_date', $month)
            ->whereNotNull('cae')
            ->selectRaw('COALESCE(SUM(iva_21), 0) + COALESCE(SUM(iva_10_5), 0) + COALESCE(SUM(iva_27), 0) as total_iva')
            ->value('total_iva') ?? 0;

        $debitoNC = CreditNote::where('company_id', $companyId)
            ->whereYear('issue_date', $year)->whereMonth('issue_date', $month)
            ->whereNotNull('cae')
            ->selectRaw('COALESCE(SUM(iva_21), 0) + COALESCE(SUM(iva_10_5), 0) + COALESCE(SUM(iva_27), 0) as total_iva')
            ->value('total_iva') ?? 0;

        $debitoFiscal = $debitoFiscal - $debitoNC;

        $creditoFiscal = PurchaseInvoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)->whereMonth('issue_date', $month)
            ->selectRaw('COALESCE(SUM(iva_21), 0) + COALESCE(SUM(iva_10_5), 0) + COALESCE(SUM(iva_27), 0) as total_iva')
            ->value('total_iva') ?? 0;

        $company = active_company();

        return view('libro-iva.preview', compact(
            'year', 'month', 'ventasCount', 'ncCount', 'comprasCount',
            'ventasTotal', 'ncTotal', 'comprasTotal',
            'debitoFiscal', 'creditoFiscal', 'company'
        ));
    }

    public function download(Request $request)
    {
        $this->authorize('ventas.section');
        $request->validate([
            'year'  => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $service = new LibroIvaService();
        $files = $service->generate(active_company_id(), (int) $request->year, (int) $request->month);

        $periodo = $request->year.str_pad($request->month, 2, '0', STR_PAD_LEFT);

        $zipPath = tempnam(sys_get_temp_dir(), 'libro_iva_').'.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);

        foreach ($files as $name => $content) {
            $zip->addFromString("{$name}_{$periodo}.txt", $content);
        }

        $zip->close();

        return response()->download($zipPath, "libro_iva_{$periodo}.zip")
            ->deleteFileAfterSend(true);
    }
}
