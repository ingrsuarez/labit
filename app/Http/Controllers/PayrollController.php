<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Category;
use App\Models\SalaryItem;
use App\Models\Leave;
use Illuminate\Support\Carbon;

class PayrollController extends Controller
{
    /**
     * Mostrar vista de liquidación
     */
    public function index(Request $request)
    {
        $employees = Employee::with(['jobs.category'])
            ->orderBy('lastName')
            ->get();

        $selectedEmployee = null;
        $payroll = null;
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        if ($request->filled('employee_id')) {
            $selectedEmployee = Employee::with(['jobs.category'])->find($request->employee_id);
            
            if ($selectedEmployee) {
                $payroll = $this->calculatePayroll($selectedEmployee, $year, $month);
            }
        }

        return view('payroll.index', [
            'employees' => $employees,
            'selectedEmployee' => $selectedEmployee,
            'payroll' => $payroll,
            'filters' => [
                'employee_id' => $request->employee_id,
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }

    /**
     * Calcular liquidación de un empleado para un período
     */
    private function calculatePayroll(Employee $employee, int $year, int $month)
    {
        // Obtener categoría y salario básico del empleado
        $category = $this->getEmployeeCategory($employee);
        $basicoCategoria = $category ? (float)$category->wage : 0;
        
        // Calcular básico proporcional según horas semanales del empleado
        // Horas base de la categoría (jornada completa)
        $horasBaseCategoria = $category ? ($category->base_weekly_hours ?? 48) : 48;
        $horasSemanalesEmpleado = $employee->weekly_hours ?? $horasBaseCategoria;
        
        // Básico proporcional = básico categoría × (horas empleado / horas base categoría)
        $basicSalary = $horasBaseCategoria > 0 
            ? $basicoCategoria * ($horasSemanalesEmpleado / $horasBaseCategoria)
            : $basicoCategoria;

        // Obtener novedades del período
        $leaves = $this->getEmployeeLeaves($employee->id, $year, $month);

        // Obtener conceptos activos que aplican en este período
        $haberes = SalaryItem::haberes()->active()->forPeriod($month, $year)->orderBy('order')->get();
        $deducciones = SalaryItem::deducciones()->active()->forPeriod($month, $year)->orderBy('order')->get();

        // Calcular haberes
        $haberesCalculados = [];
        $basicoOriginal = $basicSalary; // Guardamos el básico proporcional como referencia
        
        // Obtener días de licencias
        $diasVacaciones = $leaves['dias_vacaciones'] ?? 0;
        $diasEnfermedad = $leaves['dias_enfermedad'] ?? 0;
        $diasInasistencia = $leaves['dias_inasistencia'] ?? 0;
        
        // Calcular días trabajados (30 - vacaciones - enfermedad - inasistencia)
        $diasTrabajados = 30 - $diasVacaciones - $diasEnfermedad - $diasInasistencia;
        
        // Sueldo básico proporcional a días trabajados
        $basicoTrabajado = $basicoOriginal * ($diasTrabajados / 30);
        $totalHaberes = $basicoTrabajado;
        
        $haberesCalculados[] = [
            'nombre' => 'Sueldo Básico',
            'porcentaje' => $diasTrabajados < 30 ? $diasTrabajados . ' días' : null,
            'importe' => $basicoTrabajado,
            'tipo' => 'fixed',
        ];
        
        // Vacaciones: se pagan con divisor 25 (mejor que días normales)
        // vacaciones = basico * (dias_vacaciones / 25)
        if ($diasVacaciones > 0) {
            $importeVacaciones = $basicoOriginal * ($diasVacaciones / 25);
            $haberesCalculados[] = [
                'nombre' => 'Vacaciones',
                'porcentaje' => $diasVacaciones . ' días',
                'importe' => $importeVacaciones,
                'tipo' => 'fixed',
            ];
            $totalHaberes += $importeVacaciones;
        }
        
        // Días de enfermedad (con certificado y aprobados): se pagan igual que días normales
        if ($diasEnfermedad > 0) {
            $importeEnfermedad = $basicoOriginal * ($diasEnfermedad / 30);
            $haberesCalculados[] = [
                'nombre' => 'Días Enfermedad',
                'porcentaje' => $diasEnfermedad . ' días',
                'importe' => $importeEnfermedad,
                'tipo' => 'fixed',
            ];
            $totalHaberes += $importeEnfermedad;
        }
        
        // Días de inasistencia: NO se pagan (sin certificado o no aprobados)
        if ($diasInasistencia > 0) {
            $importeInasistencia = $basicoOriginal * ($diasInasistencia / 30);
            $haberesCalculados[] = [
                'nombre' => 'Inasistencia',
                'porcentaje' => $diasInasistencia . ' días',
                'importe' => -$importeInasistencia, // Negativo porque es descuento (no se paga)
                'tipo' => 'descuento',
            ];
            // No se suma al total porque ya está descontado del básico
        }
        
        // El básico para cálculos de otros conceptos es el básico reducido
        // (sin vacaciones, enfermedad ni inasistencia)
        $basicSalary = $basicoTrabajado;
        
        // A partir de aquí, $basicSalary es el básico reducido
        // Todos los demás conceptos se calculan sobre este nuevo básico

        // 1. PRIMERO: Calcular horas extras (sobre el básico)
        $horasExtras = $this->calculateHorasExtras($employee, $basicSalary, $leaves);
        $totalHorasExtras = $horasExtras['total'];
        
        if ($totalHorasExtras > 0) {
            if ($horasExtras['horas_50'] > 0) {
                $haberesCalculados[] = [
                    'nombre' => 'Horas Extras 50%',
                    'porcentaje' => $leaves['horas_50'] . ' hs',
                    'importe' => $horasExtras['horas_50'],
                    'tipo' => 'hours',
                ];
            }
            if ($horasExtras['horas_100'] > 0) {
                $haberesCalculados[] = [
                    'nombre' => 'Horas Extras 100%',
                    'porcentaje' => $leaves['horas_100'] . ' hs',
                    'importe' => $horasExtras['horas_100'],
                    'tipo' => 'hours',
                ];
            }
            $totalHaberes += $totalHorasExtras;
        }

        // 2. SEGUNDO: Calcular conceptos que afectan la base de antigüedad (ej: Adicional Título)
        $baseParaAntiguedad = $basicSalary + $totalHorasExtras;
        $conceptosAntiguedad = [];
        
        // Buscar haberes que se incluyen en la base de antigüedad
        $haberesAntiguedad = $haberes->where('includes_in_antiguedad_base', true);
        foreach ($haberesAntiguedad as $haber) {
            // Si requiere asignación, verificar que el empleado lo tenga
            if ($haber->requires_assignment && !$employee->hasSalaryItem($haber->id)) {
                continue;
            }
            
            $importe = $this->calculateItem($haber, $basicSalary, $leaves, $employee);
            if ($importe > 0) {
                $conceptosAntiguedad[] = [
                    'nombre' => $haber->name,
                    'porcentaje' => $haber->calculation_type === 'percentage' ? $haber->value . '%' : null,
                    'importe' => $importe,
                    'tipo' => $haber->calculation_type,
                    'remunerativo' => $haber->is_remunerative,
                ];
                $baseParaAntiguedad += $importe;
                $totalHaberes += $importe;
            }
        }
        
        // Agregar conceptos que afectan antigüedad al listado de haberes
        foreach ($conceptosAntiguedad as $concepto) {
            $haberesCalculados[] = $concepto;
        }
        
        // 3. TERCERO: Calcular antigüedad sobre (básico + horas extras + conceptos especiales)
        $antiguedad = $this->calculateAntiguedad($employee, $baseParaAntiguedad, $year, $month);
        
        if ($antiguedad > 0) {
            $haberesCalculados[] = [
                'nombre' => 'Antigüedad',
                'porcentaje' => '2%', // Según ley argentina: 2% por año de antigüedad
                'importe' => $antiguedad,
                'tipo' => 'percentage',
            ];
            $totalHaberes += $antiguedad;
        }

        // 4. CUARTO: Calcular haberes adicionales según su base de cálculo
        // Preparar las diferentes bases
        $bases = [
            'basic' => $basicSalary,
            'basic_antiguedad' => $basicSalary + $antiguedad,
            'basic_hours' => $basicSalary + $totalHorasExtras,
            'basic_hours_antiguedad' => $basicSalary + $totalHorasExtras + $antiguedad,
        ];

        // Separar haberes remunerativos y no remunerativos
        $totalRemunerativo = $totalHaberes; // El básico, horas extras, conceptos especiales y antigüedad son remunerativos
        $totalNoRemunerativo = 0;

        // IDs de haberes ya procesados (los que afectan la base de antigüedad)
        $haberesYaProcesados = $haberesAntiguedad->pluck('id')->toArray();

        foreach ($haberes as $haber) {
            // Saltar haberes que ya se procesaron (afectan base de antigüedad)
            if (in_array($haber->id, $haberesYaProcesados)) {
                continue;
            }
            
            // Si el concepto requiere asignación, verificar que el empleado lo tenga
            if ($haber->requires_assignment && !$employee->hasSalaryItem($haber->id)) {
                continue; // Saltar este concepto si no está asignado al empleado
            }
            
            // Obtener la base correcta para este concepto
            $baseCalculo = $bases[$haber->calculation_base] ?? $bases['basic_antiguedad'];
            
            // Verificar si hay un valor personalizado para este empleado
            $customValue = null;
            if ($haber->requires_assignment) {
                $assignment = $employee->salaryItems()->where('salary_item_id', $haber->id)->first();
                if ($assignment && $assignment->pivot->custom_value) {
                    $customValue = $assignment->pivot->custom_value;
                }
            }
            
            $importe = $customValue ?? $this->calculateItem($haber, $baseCalculo, $leaves, $employee);
            if ($importe > 0) {
                $haberesCalculados[] = [
                    'nombre' => $haber->name,
                    'porcentaje' => $haber->calculation_type === 'percentage' ? $haber->value . '%' : null,
                    'importe' => $importe,
                    'tipo' => $haber->calculation_type,
                    'remunerativo' => $haber->is_remunerative,
                    'base' => $haber->calculation_base,
                ];
                $totalHaberes += $importe;
                
                // Separar remunerativo de no remunerativo
                if ($haber->is_remunerative) {
                    $totalRemunerativo += $importe;
                } else {
                    $totalNoRemunerativo += $importe;
                }
            }
        }

        // Subtotal remunerativo (base para deducciones)
        $subtotalRemunerativo = $totalRemunerativo;
        
        // Subtotal total (incluye no remunerativo)
        $subtotal = $totalHaberes;

        // Calcular deducciones - SOLO sobre el BRUTO REMUNERATIVO (excluye no remunerativos)
        $deduccionesCalculadas = [];
        $totalDeducciones = 0;

        foreach ($deducciones as $deduccion) {
            // Si el concepto requiere asignación, verificar que el empleado lo tenga
            if ($deduccion->requires_assignment && !$employee->hasSalaryItem($deduccion->id)) {
                continue;
            }
            
            // Las deducciones se calculan sobre el SUBTOTAL REMUNERATIVO (excluye no remunerativos)
            $importe = $this->calculateItem($deduccion, $subtotalRemunerativo, $leaves, $employee);
            if ($importe > 0) {
                $deduccionesCalculadas[] = [
                    'nombre' => $deduccion->name,
                    'porcentaje' => $deduccion->calculation_type === 'percentage' ? $deduccion->value . '%' : null,
                    'importe' => $importe,
                    'tipo' => $deduccion->calculation_type,
                ];
                $totalDeducciones += $importe;
            }
        }

        // Neto a cobrar = Total haberes - Deducciones
        $netoACobrar = $subtotal - $totalDeducciones;

        // Redondear importes de haberes solo al final
        $haberesRedondeados = array_map(function($haber) {
            $haber['importe'] = round($haber['importe'], 2);
            return $haber;
        }, $haberesCalculados);

        // Redondear importes de deducciones solo al final
        $deduccionesRedondeadas = array_map(function($deduccion) {
            $deduccion['importe'] = round($deduccion['importe'], 2);
            return $deduccion;
        }, $deduccionesCalculadas);

        return [
            'empleado' => [
                'nombre' => $employee->name . ' ' . $employee->lastName,
                'cuil' => $employee->employeeId,
                'categoria' => $category ? $category->name : 'Sin categoría',
                'convenio' => $category ? $category->agreement : 'CCT 108/75',
                'fecha_ingreso' => $employee->start_date,
                'antiguedad_anos' => $this->calculateAntiguedadYears($employee, $year, $month),
            ],
            'periodo' => [
                'year' => $year,
                'month' => $month,
                'label' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
            ],
            'salario_basico' => round($basicSalary, 2),
            'haberes' => $haberesRedondeados,
            'total_haberes' => round($totalHaberes, 2),
            'total_remunerativo' => round($totalRemunerativo, 2),
            'total_no_remunerativo' => round($totalNoRemunerativo, 2),
            'subtotal' => round($subtotal, 2),
            'subtotal_remunerativo' => round($subtotalRemunerativo, 2),
            'deducciones' => $deduccionesRedondeadas,
            'total_deducciones' => round($totalDeducciones, 2),
            'neto_a_cobrar' => round($netoACobrar, 2),
            'novedades' => $leaves,
        ];
    }

    /**
     * Obtener categoría del empleado (desde su puesto)
     */
    private function getEmployeeCategory(Employee $employee): ?Category
    {
        // Buscar la categoría a través del puesto del empleado
        $job = $employee->jobs->first();
        if ($job && $job->category) {
            return $job->category;
        }

        // Si no tiene puesto, buscar por el campo position
        if ($employee->position) {
            return Category::where('name', 'like', '%' . $employee->position . '%')->first();
        }

        return null;
    }

    /**
     * Obtener novedades del empleado para el período
     */
    private function getEmployeeLeaves(int $employeeId, int $year, int $month): array
    {
        $leaves = Leave::where('employee_id', $employeeId)
            ->whereYear('start', $year)
            ->whereMonth('start', $month)
            ->get();

        // Separar licencias por enfermedad: válidas (con certificado y aprobadas) vs inasistencias
        $enfermedadLeaves = $leaves->where('type', 'enfermedad');
        
        // Días de enfermedad válidos: con archivo Y aprobado
        $diasEnfermedadValidos = $enfermedadLeaves
            ->filter(fn($l) => !empty($l->file) && $l->status === 'aprobado')
            ->sum(fn($l) => $l->days ?? (Carbon::parse($l->end)->diffInDays(Carbon::parse($l->start)) + 1));
        
        // Días de inasistencia: sin archivo O no aprobado
        $diasInasistencia = $enfermedadLeaves
            ->filter(fn($l) => empty($l->file) || $l->status !== 'aprobado')
            ->sum(fn($l) => $l->days ?? (Carbon::parse($l->end)->diffInDays(Carbon::parse($l->start)) + 1));

        return [
            'dias_vacaciones' => $leaves->where('type', 'vacaciones')->sum(fn($l) => $l->days ?? (Carbon::parse($l->end)->diffInDays(Carbon::parse($l->start)) + 1)),
            'dias_enfermedad' => $diasEnfermedadValidos,
            'dias_inasistencia' => $diasInasistencia,
            'dias_embarazo' => $leaves->where('type', 'embarazo')->sum(fn($l) => $l->days ?? (Carbon::parse($l->end)->diffInDays(Carbon::parse($l->start)) + 1)),
            'horas_50' => $leaves->sum('hour_50') ?? 0,
            'horas_100' => $leaves->sum('hour_100') ?? 0,
        ];
    }

    /**
     * Calcular el importe de un concepto
     */
    private function calculateItem(SalaryItem $item, float $base, array $leaves, ?Employee $employee = null): float
    {
        return match($item->calculation_type) {
            'percentage' => $base * ($item->value / 100),
            'fixed' => $item->value,
            'fixed_proportional' => $this->calculateFixedProportional($item, $employee),
            'hours' => 0, // Las horas se calculan aparte
            default => 0,
        };
    }

    /**
     * Calcular monto fijo proporcional según horas del empleado
     */
    private function calculateFixedProportional(SalaryItem $item, ?Employee $employee): float
    {
        if (!$employee) {
            return $item->value;
        }

        $employeeHours = $employee->weekly_hours ?? 48;
        $category = $employee->jobs->first()?->category;
        $categoryHours = $category?->base_weekly_hours ?? 48;

        if ($categoryHours <= 0) {
            return $item->value;
        }

        return $item->value * ($employeeHours / $categoryHours);
    }

    /**
     * Calcular antigüedad según CCT 108/75
     * 2% por año de antigüedad sobre el básico
     */
    private function calculateAntiguedad(Employee $employee, float $basicSalary, int $year, int $month): float
    {
        $years = $this->calculateAntiguedadYears($employee, $year, $month);
        $percentage = $years * 2; // 2% por año completo
        return $basicSalary * ($percentage / 100);
    }

    private function getAntiguedadPercentage(Employee $employee, int $year, int $month): int
    {
        $years = $this->calculateAntiguedadYears($employee, $year, $month);
        return $years * 2; // 2% por año completo
    }

    /**
     * Calcular años completos de antigüedad al último día del período
     * Solo cuenta años completos, no parciales
     */
    private function calculateAntiguedadYears(Employee $employee, int $year, int $month): int
    {
        if (!$employee->start_date) {
            return 0;
        }
        
        // Calcular al último día del mes del período
        $endOfPeriod = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $startDate = Carbon::parse($employee->start_date);
        
        // diffInYears retorna solo años completos (enteros)
        return (int) $startDate->diffInYears($endOfPeriod);
    }

    /**
     * Calcular horas extras según CCT 108/75
     * 50% días hábiles, 100% feriados/fines de semana
     */
    private function calculateHorasExtras(Employee $employee, float $basicSalary, array $leaves): array
    {
        // Valor hora según CCT 108/75 FATSA-CADIME/CEDIM
        // Divisor: 204 (48 hs semanales × 4.25 semanas = 204)
        $valorHora = $basicSalary / 204;

        // 50% días hábiles, 100% feriados/fines de semana
        // Sin redondeo intermedio para mayor precisión
        $horas50 = ($leaves['horas_50'] ?? 0) * $valorHora * 1.5;
        $horas100 = ($leaves['horas_100'] ?? 0) * $valorHora * 2;

        return [
            'horas_50' => $horas50,
            'horas_100' => $horas100,
            'total' => $horas50 + $horas100,
        ];
    }

    /**
     * Liquidación masiva - todos los empleados
     */
    public function bulk(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $employees = Employee::with(['jobs.category'])->orderBy('lastName')->get();
        
        $payrolls = [];
        foreach ($employees as $employee) {
            $payrolls[] = $this->calculatePayroll($employee, $year, $month);
        }

        return view('payroll.bulk', [
            'payrolls' => $payrolls,
            'year' => $year,
            'month' => $month,
            'totals' => [
                'total_bruto' => collect($payrolls)->sum('subtotal'),
                'total_deducciones' => collect($payrolls)->sum('total_deducciones'),
                'total_neto' => collect($payrolls)->sum('neto_a_cobrar'),
            ],
        ]);
    }
}

