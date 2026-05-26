<?php

namespace App\Exports;

use App\Exports\Concerns\InsertsBillingSummaryLabHeader;
use App\Models\Insurance;
use App\Services\BillingSummaryService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyInsuranceReportExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use InsertsBillingSummaryLabHeader;

    protected Insurance $insurance;

    protected $rows;

    protected array $totals;

    protected bool $detailed;

    public function __construct(
        protected int $insuranceId,
        protected string $dateFrom,
        protected string $dateTo,
        protected string $format = 'summary',
    ) {
        $this->insurance = Insurance::findOrFail($insuranceId);
        $service = app(BillingSummaryService::class);
        [$from, $to] = $service->parseDateRange($dateFrom, $dateTo);
        $this->detailed = $service->normalizeFormat($format) === 'detailed';
        $built = $service->buildClinical($this->insurance, $from, $to, $format);
        $this->rows = $built['rows'];
        $this->totals = $built['totals'];
    }

    public function collection()
    {
        $data = $this->rows->map(fn (array $row) => (object) $row);

        if ($this->detailed) {
            $data->push((object) [
                'formatted_date' => '',
                'patient_label' => 'TOTAL',
                'dni' => ($this->totals['line_count'] ?? 0).' práctica(s)',
                'code' => '',
                'practice' => '',
                'amount' => $this->totals['total_amount'],
            ]);
        } else {
            $data->push((object) [
                'formatted_date' => '',
                'name' => 'TOTAL',
                'dni' => $this->totals['protocol_count'].' protocolo(s)',
                'affiliate' => '',
                'codes' => '',
                'price' => $this->totals['total_amount'],
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        if ($this->detailed) {
            return ['Fecha', 'Paciente', 'DNI', 'Código', 'Práctica', 'Monto'];
        }

        return ['Fecha', 'Paciente', 'DNI', 'Afiliado', 'Determinaciones', 'Precio'];
    }

    public function map($row): array
    {
        if ($this->detailed) {
            return [
                $row->formatted_date ?? '',
                $row->patient_label ?? '',
                $row->dni ?? '',
                $row->code ?? '',
                $row->practice ?? '',
                $row->amount ?? 0,
            ];
        }

        return [
            $row->formatted_date ?? '',
            $row->name ?? '',
            $row->dni ?? '',
            $row->affiliate ?? '',
            $row->codes ?? '',
            $row->price ?? 0,
        ];
    }

    public function title(): string
    {
        $from = Carbon::parse($this->dateFrom)->format('d-m-Y');
        $to = Carbon::parse($this->dateTo)->format('d-m-Y');
        $suffix = $this->detailed ? ' Detallado' : '';

        return strtoupper($this->insurance->name ?? 'Reporte')."{$suffix} ({$from} a {$to})";
    }

    public function registerEvents(): array
    {
        $from = Carbon::parse($this->dateFrom)->format('d/m/Y');
        $to = Carbon::parse($this->dateTo)->format('d/m/Y');

        return $this->billingSummaryExcelEvents(
            'Facturación — '.$from.' al '.$to,
            'Obra social: '.$this->insurance->billingDisplayName(),
        );
    }

    public function styles(Worksheet $sheet)
    {
        $tableHead = $this->billingSummaryTableHeaderRow();
        $lastRow = $tableHead + $this->rows->count() + 1;

        $sheet->getStyle("A{$tableHead}:F{$tableHead}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
        ]);

        $sheet->getStyle('F'.($tableHead + 1).":F{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        $sheet->getStyle("A{$lastRow}:F{$lastRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D4EDDA'],
            ],
        ]);

        return [];
    }
}
