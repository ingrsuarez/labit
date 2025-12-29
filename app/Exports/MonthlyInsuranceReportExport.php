<?php

namespace App\Exports;

use App\Models\Admission;
use App\Models\Insurance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class MonthlyInsuranceReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $insuranceId;
    protected $month;
    protected $year;
    protected $insurance;
    protected $total = 0;
    protected $rowCount = 0;

    public function __construct(int $insuranceId, int $month, int $year)
    {
        $this->insuranceId = $insuranceId;
        $this->month = $month;
        $this->year = $year;
        $this->insurance = Insurance::find($insuranceId);
    }

    public function collection()
    {
        $admissions = Admission::with(['patient', 'admissionTests.test'])
            ->where('insurance', $this->insuranceId)
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $data = collect();

        foreach ($admissions as $admission) {
            foreach ($admission->admissionTests as $admissionTest) {
                // Solo incluir prácticas que paga la OS
                if (!$admissionTest->paid_by_patient && $admissionTest->authorization_status !== 'rejected') {
                    $amount = $admissionTest->price - $admissionTest->copago;
                    $this->total += $amount;
                    $this->rowCount++;
                    
                    $data->push([
                        'date' => $admission->date,
                        'patient_name' => $admission->patient?->full_name ?? 'N/A',
                        'patient_id' => $admission->patient?->patientId ?? 'N/A',
                        'affiliate_number' => $admission->affiliate_number ?? '',
                        'test_code' => $admissionTest->test->code,
                        'test_name' => $admissionTest->test->name,
                        'amount' => $amount,
                    ]);
                }
            }
        }

        // Agregar fila de total
        $data->push([
            'date' => null,
            'patient_name' => '',
            'patient_id' => '',
            'affiliate_number' => '',
            'test_code' => '',
            'test_name' => 'TOTAL',
            'amount' => $this->total,
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Paciente',
            'DNI',
            'Afiliado',
            'Código',
            'Práctica',
            'Monto',
        ];
    }

    public function map($row): array
    {
        return [
            $row['date'] ? Carbon::parse($row['date'])->format('d/m/Y') : '',
            $row['patient_name'],
            $row['patient_id'],
            $row['affiliate_number'],
            $row['test_code'],
            $row['test_name'],
            $row['amount'],
        ];
    }

    public function title(): string
    {
        $monthName = Carbon::create($this->year, $this->month, 1)->locale('es')->translatedFormat('F');
        return strtoupper($this->insurance->name ?? 'Reporte') . ' - ' . ucfirst($monthName) . ' ' . $this->year;
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo del encabezado
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
        ]);

        // Formato de moneda para la columna de monto
        $lastRow = $this->rowCount + 2; // +1 para encabezado, +1 para total
        $sheet->getStyle("G2:G{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // Estilo de la fila de total
        $sheet->getStyle("A{$lastRow}:G{$lastRow}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D4EDDA'],
            ],
        ]);

        return [];
    }
}

