<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Insurance;
use App\Exports\MonthlyInsuranceReportExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LabReportController extends Controller
{
    /**
     * Vista principal del reporte mensual
     */
    public function monthly(Request $request)
    {
        $insurances = Insurance::orderBy('name')->get();
        
        // Valores por defecto: mes actual
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $insuranceId = $request->get('insurance_id');

        $report = null;
        $totals = null;
        $selectedInsurance = null;

        if ($insuranceId) {
            $selectedInsurance = Insurance::find($insuranceId);
            
            // Obtener admisiones del mes para la obra social
            $admissions = Admission::with(['patient', 'admissionTests.test'])
                ->where('insurance', $insuranceId)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->orderBy('date')
                ->orderBy('id')
                ->get();

            // Construir el reporte
            $report = collect();
            
            foreach ($admissions as $admission) {
                foreach ($admission->admissionTests as $admissionTest) {
                    // Solo incluir prácticas que paga la OS (no rechazadas ni pagadas por paciente)
                    if (!$admissionTest->paid_by_patient && $admissionTest->authorization_status !== 'rejected') {
                        $report->push([
                            'date' => $admission->date,
                            'formatted_date' => Carbon::parse($admission->date)->format('d/m/Y'),
                            'patient_name' => $admission->patient?->full_name ?? 'N/A',
                            'patient_id' => $admission->patient?->patientId ?? 'N/A',
                            'affiliate_number' => $admission->affiliate_number ?? 'N/A',
                            'test_code' => $admissionTest->test->code,
                            'test_name' => $admissionTest->test->name,
                            'price' => $admissionTest->price - $admissionTest->copago, // Monto que paga la OS
                            'copago' => $admissionTest->copago,
                            'admission_id' => $admission->id,
                            'protocol' => $admission->protocol_number,
                        ]);
                    }
                }
            }

            // Calcular totales
            $totals = [
                'total_amount' => $report->sum('price'),
                'total_copago' => $report->sum('copago'),
                'total_practices' => $report->count(),
                'total_admissions' => $admissions->count(),
                'total_patients' => $admissions->pluck('patient_id')->unique()->count(),
            ];
        }

        // Años disponibles para el selector
        $years = range(date('Y') - 2, date('Y'));

        return view('lab.reports.monthly', compact(
            'insurances',
            'month',
            'year',
            'years',
            'insuranceId',
            'selectedInsurance',
            'report',
            'totals'
        ));
    }

    /**
     * Exportar reporte a Excel
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'insurance_id' => 'required|exists:insurances,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $insurance = Insurance::findOrFail($request->insurance_id);
        $month = $request->month;
        $year = $request->year;

        $monthName = Carbon::create($year, $month, 1)->locale('es')->translatedFormat('F');
        $filename = "ObraSocial-{$insurance->id}_{$monthName}_{$year}.xlsx";

        return Excel::download(
            new MonthlyInsuranceReportExport($insurance->id, $month, $year),
            $filename
        );
    }
}

