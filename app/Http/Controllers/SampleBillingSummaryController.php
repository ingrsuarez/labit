<?php

namespace App\Http\Controllers;

use App\Exports\SampleBillingSummaryExport;
use App\Models\Customer;
use App\Services\BillingSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SampleBillingSummaryController extends Controller
{
    public function __construct(
        protected BillingSummaryService $billingSummary,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('sales-invoices.index');

        $customers = Customer::where('status', 'activo')
            ->whereJsonContains('type', 'aguas')
            ->orderBy('name')
            ->get();

        $customerId = $request->get('customer_id');
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        $rows = null;
        $totals = null;
        $selectedCustomer = null;
        $periodLabel = null;

        if ($customerId) {
            $selectedCustomer = Customer::find($customerId);
            [$from, $to] = $this->billingSummary->parseDateRange($dateFrom, $dateTo);
            $built = $this->billingSummary->buildSampleRows($selectedCustomer, $from, $to);
            $rows = $built['rows'];
            $totals = $built['totals'];
            $periodLabel = $from->format('d/m/Y').' — '.$to->format('d/m/Y');
        }

        return view('sample.billing-summary', compact(
            'customers',
            'customerId',
            'dateFrom',
            'dateTo',
            'selectedCustomer',
            'rows',
            'totals',
            'periodLabel',
        ));
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('sales-invoices.index');
        $validated = $this->validateFilters($request);

        $customer = Customer::findOrFail($validated['customer_id']);
        [$from, $to] = $this->billingSummary->parseDateRange(
            $validated['date_from'],
            $validated['date_to'],
        );

        $filename = sprintf(
            'Resumen-Muestras-%s_%s_%s.xlsx',
            $customer->id,
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        );

        return Excel::download(
            new SampleBillingSummaryExport($customer->id, $validated['date_from'], $validated['date_to']),
            $filename,
        );
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('sales-invoices.index');
        $validated = $this->validateFilters($request);

        $customer = Customer::findOrFail($validated['customer_id']);
        [$from, $to] = $this->billingSummary->parseDateRange(
            $validated['date_from'],
            $validated['date_to'],
        );

        $built = $this->billingSummary->buildSampleRows($customer, $from, $to);

        $pdf = Pdf::loadView('sample.billing-summary-pdf', [
            'customer' => $customer,
            'rows' => $built['rows'],
            'totals' => $built['totals'],
            'periodLabel' => $from->format('d/m/Y').' al '.$to->format('d/m/Y'),
        ]);
        $pdf->setPaper('A4', 'landscape');

        $filename = sprintf(
            'Resumen-Muestras-%s_%s_%s.pdf',
            $customer->id,
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        );

        return $pdf->download($filename);
    }

    /**
     * @return array{customer_id: int, date_from: string, date_to: string}
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);
    }
}
