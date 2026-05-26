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

    public function __construct(
        protected int $customerId,
        protected string $dateFrom,
        protected string $dateTo,
    ) {
        $this->customer = Customer::findOrFail($customerId);
        [$from, $to] = app(BillingSummaryService::class)->parseDateRange($dateFrom, $dateTo);
        $built = app(BillingSummaryService::class)->buildSampleRows($this->customer, $from, $to);
        $this->rows = $built['rows'];
        $this->totals = $built['totals'];
    }

    public function collection()
    {
        $data = $this->rows->map(fn (array $row) => (object) $row);
        $data->push((object) [
            'formatted_date' => '',
            'name' => 'TOTAL',
            'codes' => $this->totals['protocol_count'].' protocolo(s)',
            'price' => $this->totals['total_amount'],
        ]);

        return $data;
    }

    public function headings(): array
    {
        return ['Fecha', 'Muestra', 'Determinaciones', 'Precio'];
    }

    public function map($row): array
    {
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

        return ($this->customer->name ?? 'Cliente')." ({$from} a {$to})";
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
        ]);

        $lastRow = $this->rows->count() + 2;
        $sheet->getStyle("D2:D{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle("A{$lastRow}:D{$lastRow}")->applyFromArray(['font' => ['bold' => true]]);

        return [];
    }
}
