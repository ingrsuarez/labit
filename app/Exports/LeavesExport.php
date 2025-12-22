<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class LeavesExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected Collection $data;
    protected ?int $year;
    protected ?int $month;

    public function __construct(Collection $data, ?int $year = null, ?int $month = null)
    {
        $this->data = $data;
        $this->year = $year;
        $this->month = $month;
    }

    public function collection(): Collection
    {
        return $this->data->map(function ($row) {
            return [
                'año' => $row->year,
                'mes' => $row->month,
                'empleado' => $row->employee,
                'cuil' => $row->cuil ?? '',
                'horas_semanales' => $row->weekly_hours ?? 0,
                'categoria' => $row->category ?? '',
                'tipo' => ucfirst($row->type ?? '-'),
                'cantidad' => $row->cantidad ?? 0,
                'total_dias' => $row->total_dias ?? 0,
                'horas_50' => $row->horas_50 ?? 0,
                'horas_100' => $row->horas_100 ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Año',
            'Mes',
            'Empleado',
            'CUIL',
            'Hs/Semana',
            'Categoría',
            'Tipo Novedad',
            'Cantidad',
            'Total Días',
            'Horas 50%',
            'Horas 100%',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Obtener última fila con datos
        $lastRow = $sheet->getHighestRow();
        $lastColumn = 'K';

        return [
            // Estilo del encabezado
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Bordes para toda la tabla
            "A1:{$lastColumn}{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ],
            // Alineación de columnas numéricas
            "A2:B{$lastRow}" => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            "E2:E{$lastRow}" => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            "H2:K{$lastRow}" => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ],
        ];
    }

    public function title(): string
    {
        if ($this->year && $this->month) {
            $monthName = \Carbon\Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('F');
            return "Novedades {$monthName} {$this->year}";
        }
        
        if ($this->year) {
            return "Novedades {$this->year}";
        }
        
        return 'Novedades';
    }
}

