<?php

namespace App\Http\Controllers;

use App\Exports\MonthlyInsuranceReportExport;
use App\Models\Insurance;
use App\Services\BillingSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LabReportController extends Controller
{
    public function __construct(
        protected BillingSummaryService $billingSummary,
    ) {}

    public function monthly(Request $request)
    {
        $this->authorize('lab-reports.index');

        $insurances = Insurance::where('type', '!=', 'nomenclador')
            ->orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $insuranceId = $request->get('insurance_id');
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $format = $this->billingSummary->normalizeFormat($request->get('format'));

        $rows = null;
        $totals = null;
        $selectedInsurance = null;
        $periodLabel = null;

        if ($insuranceId) {
            $selectedInsurance = Insurance::find($insuranceId);
            [$from, $to] = $this->billingSummary->parseDateRange($dateFrom, $dateTo);
            $built = $this->billingSummary->buildClinical($selectedInsurance, $from, $to, $format);
            $rows = $built['rows'];
            $totals = $built['totals'];
            $periodLabel = $from->format('d/m/Y').' — '.$to->format('d/m/Y');
        }

        return view('lab.reports.monthly', compact(
            'insurances',
            'insuranceId',
            'dateFrom',
            'dateTo',
            'format',
            'selectedInsurance',
            'rows',
            'totals',
            'periodLabel',
        ));
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('lab-reports.index');
        $validated = $this->validateClinicalFilters($request);

        $insurance = Insurance::findOrFail($validated['insurance_id']);
        [$from, $to] = $this->billingSummary->parseDateRange(
            $validated['date_from'],
            $validated['date_to'],
        );
        $format = $this->billingSummary->normalizeFormat($validated['format'] ?? 'summary');
        $prefix = $format === 'detailed' ? 'Resumen-Detallado' : 'Resumen';

        $filename = sprintf(
            '%s-%s_%s_%s.xlsx',
            $prefix,
            $insurance->id,
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        );

        return Excel::download(
            new MonthlyInsuranceReportExport(
                $insurance->id,
                $from->toDateString(),
                $to->toDateString(),
                $format,
            ),
            $filename,
        );
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('lab-reports.index');
        $validated = $this->validateClinicalFilters($request);

        $insurance = Insurance::findOrFail($validated['insurance_id']);
        [$from, $to] = $this->billingSummary->parseDateRange(
            $validated['date_from'],
            $validated['date_to'],
        );
        $format = $this->billingSummary->normalizeFormat($validated['format'] ?? 'summary');

        $built = $this->billingSummary->buildClinical($insurance, $from, $to, $format);

        $view = $format === 'detailed'
            ? 'lab.reports.monthly-detailed-pdf'
            : 'lab.reports.monthly-pdf';

        $pdf = Pdf::loadView($view, [
            'insurance' => $insurance,
            'rows' => $built['rows'],
            'totals' => $built['totals'],
            'periodLabel' => $from->format('d/m/Y').' al '.$to->format('d/m/Y'),
        ]);
        $pdf->setPaper('A4', 'landscape');

        $prefix = $format === 'detailed' ? 'Resumen-Detallado' : 'Resumen';
        $filename = sprintf(
            '%s-%s_%s_%s.pdf',
            $prefix,
            $insurance->id,
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        );

        return $pdf->download($filename);
    }

    /**
     * @return array{insurance_id: int, date_from: string, date_to: string, format?: string}
     */
    private function validateClinicalFilters(Request $request): array
    {
        return $request->validate([
            'insurance_id' => 'required|exists:insurances,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'format' => 'nullable|in:summary,detailed',
        ]);
    }
}
