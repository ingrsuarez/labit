<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

trait InsertsBillingSummaryLabHeader
{
    protected function billingSummaryHeaderRowCount(): int
    {
        return 6;
    }

    protected function billingSummaryTableHeaderRow(): int
    {
        return $this->billingSummaryHeaderRowCount() + 1;
    }

    protected function billingSummaryExcelEvents(string $reportTitle, ?string $counterpartyLabel = null): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) use ($reportTitle, $counterpartyLabel) {
                $this->insertBillingSummaryLabHeader($event, $reportTitle, $counterpartyLabel);
            },
        ];
    }

    protected function insertBillingSummaryLabHeader(
        AfterSheet $event,
        string $reportTitle,
        ?string $counterpartyLabel = null,
    ): void {
        $sheet = $event->sheet->getDelegate();
        $insertRows = $this->billingSummaryHeaderRowCount();
        $sheet->insertNewRowBefore(1, $insertRows);

        $lab = billing_summary_lab();
        $row = 1;

        $sheet->setCellValue("A{$row}", $reportTitle);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $row++;

        $sheet->setCellValue("A{$row}", $lab['name']);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $row++;

        $sheet->setCellValue("A{$row}", 'CUIT: '.$lab['cuit']);
        $row++;

        $sheet->setCellValue("A{$row}", 'Domicilio: '.$lab['address_line']);
        $row++;

        if ($counterpartyLabel) {
            $sheet->setCellValue("A{$row}", $counterpartyLabel);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        $sheet->getStyle('A1:F'.$insertRows)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        if ($lab['has_logo']) {
            $drawing = new Drawing;
            $drawing->setName('IPAC');
            $drawing->setDescription('Logo IPAC');
            $drawing->setPath($lab['logo_path']);
            $drawing->setHeight(48);
            $drawing->setCoordinates('F1');
            $drawing->setOffsetX(10);
            $drawing->setWorksheet($sheet);
        }
    }
}
