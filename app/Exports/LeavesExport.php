<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class LeavesExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;
    protected $year;
    protected $month;

    public function __construct(Collection $data, $year = null, $month = null)
    {
        $this->data = $data;
        $this->year = $year;
        $this->month = $month;
    }

    public function collection()
    {
        // Agrupar por empleado y consolidar tipos de novedades
        $grouped = $this->data->groupBy('employee_id');
        
        return $grouped->map(function ($items) {
            $first = $items->first();
            
            // Calcular totales por tipo
            $diasVacaciones = $items->where('type', 'vacaciones')->sum('total_dias');
            $diasEnfermedad = $items->where('type', 'enfermedad')->sum('total_dias');
            $diasEmbarazo = $items->where('type', 'embarazo')->sum('total_dias');
            $diasCapacitacion = $items->where('type', 'capacitacion')->sum('total_dias');
            $horas50 = $items->sum('horas_50');
            $horas100 = $items->sum('horas_100');
            
            return [
                'periodo' => $this->year && $this->month 
                    ? str_pad($this->month, 2, '0', STR_PAD_LEFT) . '/' . $this->year 
                    : ($first->year ?? '-') . '/' . ($first->month ?? '-'),
                'empleado' => $first->employee ?? trim(($first->lastName ?? '') . ' ' . ($first->name ?? '')),
                'cuil' => $first->cuil ?? $first->employeeId ?? '-',
                'horas_semanales' => (int)($first->weekly_hours ?? 0),
                'categoria' => $first->category ?? $first->position ?? '-',
                'dias_vacaciones' => (int)$diasVacaciones,
                'dias_enfermedad' => (int)$diasEnfermedad,
                'dias_embarazo' => (int)$diasEmbarazo,
                'dias_capacitacion' => (int)$diasCapacitacion,
                'horas_50' => (int)$horas50,
                'horas_100' => (int)$horas100,
                'total_dias_ausencia' => (int)($diasVacaciones + $diasEnfermedad + $diasEmbarazo + $diasCapacitacion),
            ];
        })->sortBy('empleado')->values();
    }

    public function headings(): array
    {
        return [
            'Período',
            'Empleado',
            'CUIL',
            'Hs. Semanales',
            'Categoría',
            'Días Vacaciones',
            'Días Enfermedad',
            'Días Embarazo',
            'Días Capacitación',
            'Horas Extra 50%',
            'Horas Extra 100%',
            'Total Días Ausencia',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        return [
            // Encabezado
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // Columnas numéricas centradas
            'F2:L' . $lastRow => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        $title = 'Liquidacion';
        if ($this->year && $this->month) {
            $title .= " " . str_pad($this->month, 2, '0', STR_PAD_LEFT) . "-{$this->year}";
        } elseif ($this->year) {
            $title .= " {$this->year}";
        }
        return $title;
    }
}

