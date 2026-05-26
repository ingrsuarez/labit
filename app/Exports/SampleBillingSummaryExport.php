<?php

namespace App\Exports;

use App\Models\Customer;
use App\Services\BillingSummaryService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SampleBillingSummaryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Customer $customer;

    protected $rows;

    protected array $totals;

    protected bool $detailed;

    public function __construct(
        protected int $customerId,
        protected string $dateFrom,
        protected string $dateTo,
        protected string $format = 'summary',
    ) {
        $this->customer = Customer::findOrFail($customerId);
        $service = app(BillingSummaryService::class);
        [$from, $to] = $service->parseDateRange($dateFrom, $dateTo);
        $this->detailed = $service->normalizeFormat($format) === 'detailed';
        $built = $service->buildSample($this->customer, $from, $to, $format);
        $this->rows = $built['rows'];
        $this->totals = $built['totals'];
    }

    public function collection()
    {
        $data = $this->rows->map(fn (array $row) => (object) $row);

        if ($this->detailed) {
            $data->push((object) [
                'formatted_date' => '',
                'subject_label' => 'TOTAL',
                'code' => ($this->totals['line_count'] ?? 0).' práctica(s)',
                'practice' => '',
                'amount' => $this->totals['total_amount'],
            ]);
        } else {
            $data->push((object) [
                'formatted_date' => '',
                'name' => 'TOTAL',
                'codes' => $this->totals['protocol_count'].' protocolo(s)',
                'price' => $this->totals['total_amount'],
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return $this->detailed
            ? ['Fecha', 'Muestra', 'Código', 'Práctica', 'Monto']
            : ['Fecha', 'Muestra', 'Determinaciones', 'Precio'];
    }

    public function map($row): array
    {
        if ($this->detailed) {
            return [
                $row->formatted_date ?? '',
                $row->subject_label ?? '',
                $row->code ?? '',
                $row->practice ?? '',
                $row->amount ?? 0,
            ];
        }

        return [
            $row->formatted_date ?? '',
            $row->name ?? '',
            $row->codes ?? '',
            $row->price ?? 0,
        ];
    }

    public function title(): string
    {
        $from = Carbon::parse($this->dateFrom)->format('d-m-Y');
        $to = Carbon::parse($this->dateTo)->format('d-m-Y');
        $suffix = $this->detailed ? ' Detallado' : '';

        return ($this->customer->name ?? 'Cliente')."{$suffix} ({$from} a {$to})";
    }

    public function styles(Worksheet $sheet)
    {
        $col = $this->detailed ? 'E' : 'D';
        $headerRange = $this->detailed ? 'A1:E1' : 'A1:D1';
        $footerRange = $this->detailed ? 'A{lastRow}:E{lastRow}' : 'A{lastRow}:D{lastRow}';

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
        ]);

        $lastRow = $this->rows->count() + 2;
        $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle(str_replace('{lastRow}', (string) $lastRow, $footerRange))->applyFromArray(['font' => ['bold' => true]]);

        return [];
    }
}
