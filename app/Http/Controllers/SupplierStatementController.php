<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SupplierStatementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('compras.section');

        $companyId = active_company_id();
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'tax_id']);

        $movements = collect();
        $supplier = null;
        $openBalance = 0;
        $closeBalance = 0;
        $totalHaber = 0;
        $totalDebe = 0;

        if ($request->filled('supplier_id')) {
            $data = $this->buildStatementData(
                (int) $request->supplier_id,
                $companyId,
                $request->date_from,
                $request->date_to
            );

            $supplier = $data['supplier'];
            $movements = $data['movements'];
            $openBalance = $data['openBalance'];
            $closeBalance = $data['closeBalance'];
            $totalHaber = $data['totalHaber'];
            $totalDebe = $data['totalDebe'];
        }

        return view('suppliers.statement', compact(
            'suppliers', 'supplier', 'movements',
            'openBalance', 'closeBalance',
            'totalHaber', 'totalDebe',
            'request'
        ));
    }

    public function pdf(Request $request)
    {
        $this->authorize('compras.section');

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $data = $this->buildStatementData(
            (int) $request->supplier_id,
            active_company_id(),
            $request->date_from,
            $request->date_to
        );

        $data['company'] = active_company();

        $pdf = \PDF::loadView('suppliers.statement-pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'cuenta-corriente-'.\Str::slug($data['supplier']->name).'-'.now()->format('Ymd').'.pdf';

        return $pdf->download($filename);
    }

    private function buildStatementData(int $supplierId, int $companyId, ?string $dateFrom, ?string $dateTo): array
    {
        $supplier = Supplier::findOrFail($supplierId);

        $dateFromCarbon = $dateFrom
            ? Carbon::parse($dateFrom)->startOfDay()
            : now()->startOfYear();

        $dateToCarbon = $dateTo
            ? Carbon::parse($dateTo)->endOfDay()
            : now()->endOfDay();

        $openBalance = $this->calculateOpenBalance($supplierId, $companyId, $dateFromCarbon);

        $invoices = PurchaseInvoice::where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->whereBetween('issue_date', [$dateFromCarbon, $dateToCarbon])
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get(['id', 'issue_date', 'total', 'point_of_sale', 'invoice_number', 'voucher_type']);

        $payments = PaymentOrder::where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->where('status', 'pagada')
            ->whereBetween('date', [$dateFromCarbon, $dateToCarbon])
            ->with('paymentLines')
            ->orderBy('date')
            ->orderBy('id')
            ->get(['id', 'date', 'total', 'number', 'payment_method']);

        $creditNotes = PurchaseCreditNote::where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->whereBetween('issue_date', [$dateFromCarbon->toDateString(), $dateToCarbon->toDateString()])
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get(['id', 'issue_date', 'total', 'credit_note_number', 'point_of_sale', 'voucher_type', 'purchase_invoice_id']);

        $movements = collect();

        foreach ($invoices as $inv) {
            $pv = $inv->point_of_sale ? str_pad($inv->point_of_sale, 5, '0', STR_PAD_LEFT).'-' : '';
            $movements->push([
                'date' => Carbon::parse($inv->issue_date),
                'type' => 'invoice',
                'reference' => "FC {$inv->voucher_type} {$pv}{$inv->invoice_number}",
                'detail' => 'Factura de compra',
                'debe' => 0,
                'haber' => (float) $inv->total,
                'sort_key' => $inv->issue_date.'_0_'.str_pad($inv->id, 10, '0', STR_PAD_LEFT),
            ]);
        }

        foreach ($payments as $pay) {
            $movements->push([
                'date' => Carbon::parse($pay->date),
                'type' => 'payment',
                'reference' => "OP #{$pay->number}",
                'detail' => 'Orden de pago — '.$pay->paymentMethodsLabel(),
                'debe' => (float) $pay->total,
                'haber' => 0,
                'sort_key' => $pay->date.'_1_'.str_pad($pay->id, 10, '0', STR_PAD_LEFT),
            ]);
        }

        foreach ($creditNotes as $cn) {
            $pv = $cn->point_of_sale !== null && $cn->point_of_sale !== ''
                ? str_pad((string) $cn->point_of_sale, 5, '0', STR_PAD_LEFT).'-'
                : '';
            $ref = "NC {$cn->voucher_type} {$pv}{$cn->credit_note_number}";
            $detail = $cn->purchase_invoice_id
                ? 'Nota de crédito (aplicada a factura)'
                : 'Nota de crédito (sin aplicar a factura)';
            $movements->push([
                'date' => Carbon::parse($cn->issue_date),
                'type' => 'credit_note',
                'reference' => $ref,
                'detail' => $detail,
                'debe' => (float) $cn->total,
                'haber' => 0,
                'sort_key' => $cn->issue_date->format('Y-m-d').'_2_'.str_pad((string) $cn->id, 10, '0', STR_PAD_LEFT),
            ]);
        }

        $movements = $movements->sortBy('sort_key')->values();

        $running = $openBalance;
        $movements = $movements->map(function ($mov) use (&$running) {
            $running += $mov['haber'] - $mov['debe'];
            $mov['saldo'] = $running;

            return $mov;
        });

        $totalHaber = $movements->sum('haber');
        $totalDebe = $movements->sum('debe');
        $closeBalance = $openBalance + $totalHaber - $totalDebe;

        return compact(
            'supplier', 'movements', 'openBalance', 'closeBalance',
            'totalHaber', 'totalDebe', 'dateFromCarbon', 'dateToCarbon'
        );
    }

    private function calculateOpenBalance(int $supplierId, int $companyId, Carbon $dateFrom): float
    {
        $invoicesBefore = PurchaseInvoice::where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->where('issue_date', '<', $dateFrom->toDateString())
            ->sum('total');

        $paymentsBefore = PaymentOrder::where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->where('status', 'pagada')
            ->where('date', '<', $dateFrom->toDateString())
            ->sum('total');

        $creditNotesBefore = PurchaseCreditNote::where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->where('issue_date', '<', $dateFrom->toDateString())
            ->sum('total');

        return (float) $invoicesBefore - (float) $paymentsBefore - (float) $creditNotesBefore;
    }
}
