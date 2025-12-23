<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Category;
use App\Models\SalaryItem;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\PayrollItem;
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
                    'porcentaje' => ($haber->calculation_type === 'percentage' && !$haber->hide_percentage_in_receipt) ? $haber->value . '%' : null,
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
        // Calcular el total de conceptos fijos que se incluyen en base de zona
        // (conceptos con includes_in_antiguedad_base = true, como Adicional Título)
        $totalConceptosFijosZona = collect($conceptosAntiguedad)->sum('importe');
        
        // Preparar las diferentes bases
        // Según CCT 108/75 FATSA, el 30% de zona se calcula sobre:
        // Básico + Antigüedad + Adicional Título (conceptos fijos convencionales)
        $bases = [
            'basic' => $basicSalary,
            'basic_antiguedad' => $basicSalary + $antiguedad,
            'basic_hours' => $basicSalary + $totalHorasExtras,
            'basic_hours_antiguedad' => $basicSalary + $totalHorasExtras + $antiguedad,
            // Nueva base para Zona 30% según CCT 108/75: incluye conceptos fijos como Adicional Título
            'basic_antiguedad_titulo' => $basicSalary + $antiguedad + $totalConceptosFijosZona,
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
                    'porcentaje' => ($haber->calculation_type === 'percentage' && !$haber->hide_percentage_in_receipt) ? $haber->value . '%' : null,
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
                    'porcentaje' => ($deduccion->calculation_type === 'percentage' && !$deduccion->hide_percentage_in_receipt) ? $deduccion->value . '%' : null,
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

        // Separar licencias por enfermedad: válidas (justificadas o con certificado y aprobadas) vs inasistencias
        $enfermedadLeaves = $leaves->where('type', 'enfermedad');
        
        // Días de enfermedad válidos: justificada O (con archivo Y aprobado)
        $diasEnfermedadValidos = $enfermedadLeaves
            ->filter(fn($l) => $l->is_justified || (!empty($l->file) && $l->status === 'aprobado'))
            ->sum(fn($l) => $l->days ?? (Carbon::parse($l->end)->diffInDays(Carbon::parse($l->start)) + 1));
        
        // Días de inasistencia: NO justificada Y (sin archivo O no aprobado)
        $diasInasistencia = $enfermedadLeaves
            ->filter(fn($l) => !$l->is_justified && (empty($l->file) || $l->status !== 'aprobado'))
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
     * Calcular SAC (Sueldo Anual Complementario / Medio Aguinaldo)
     * Según Ley 20.744, Art. 122: 50% de la mejor remuneración mensual del semestre
     * 
     * @param Employee $employee
     * @param int $year
     * @param int $month Debe ser 6 (junio) o 12 (diciembre)
     * @param float|null $currentMonthRemunerativo Total remunerativo del mes actual (para incluir en comparación)
     * @return array
     */
    private function calculateSAC(Employee $employee, int $year, int $month, ?float $currentMonthRemunerativo = null): array
    {
        // Determinar el semestre
        $semester = $month <= 6 ? 1 : 2;
        $startMonth = $semester === 1 ? 1 : 7;
        $endMonth = $semester === 1 ? 6 : 12;
        
        $inicioSemestre = Carbon::createFromDate($year, $startMonth, 1);
        $finSemestre = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth();
        
        // Determinar meses trabajados basándose en la FECHA DE INGRESO del empleado
        $mesesTrabajados = 6; // Por defecto, semestre completo
        $esProporcional = false;
        
        if ($employee->start_date) {
            $fechaIngreso = Carbon::parse($employee->start_date);
            
            // Si ingresó DURANTE este semestre, calcular meses proporcionales
            if ($fechaIngreso->between($inicioSemestre, $finSemestre)) {
                // Calcular meses desde el ingreso hasta el fin del semestre
                $mesesTrabajados = $fechaIngreso->diffInMonths($finSemestre) + 1;
                $esProporcional = true;
            } elseif ($fechaIngreso->gt($finSemestre)) {
                // Si ingresó después del fin del semestre, no corresponde SAC
                return [
                    'mejor_sueldo' => 0,
                    'meses_trabajados' => 0,
                    'es_proporcional' => true,
                    'sac_bruto' => 0,
                    'semestre' => $semester,
                    'periodo' => $semester === 1 ? '1er Semestre' : '2do Semestre',
                ];
            }
            // Si ingresó ANTES del inicio del semestre, trabajó los 6 meses completos
        }
        
        // Buscar liquidaciones guardadas del semestre para obtener el mejor sueldo
        $payrolls = Payroll::where('employee_id', $employee->id)
            ->where('year', $year)
            ->whereBetween('month', [$startMonth, $endMonth])
            ->where('month', '!=', $month) // Excluir el mes actual
            ->get();
        
        // Obtener los totales remunerativos de cada mes guardado
        $sueldosRemunerativos = $payrolls->pluck('total_remunerativo')
            ->map(fn($v) => (float) $v)
            ->toArray();
        
        // Agregar el sueldo remunerativo del mes actual
        if ($currentMonthRemunerativo !== null && $currentMonthRemunerativo > 0) {
            $sueldosRemunerativos[] = $currentMonthRemunerativo;
        }
        
        // Si no hay sueldos guardados, usar el sueldo actual como referencia
        if (empty($sueldosRemunerativos)) {
            return [
                'mejor_sueldo' => 0,
                'meses_trabajados' => $mesesTrabajados,
                'es_proporcional' => $esProporcional,
                'sac_bruto' => 0,
                'semestre' => $semester,
                'periodo' => $semester === 1 ? '1er Semestre' : '2do Semestre',
                'mensaje' => 'No hay sueldos registrados en el semestre',
            ];
        }
        
        // Obtener el mejor sueldo remunerativo del semestre
        $mejorSueldo = max($sueldosRemunerativos);
        
        // SAC = Mejor sueldo / 2 (o proporcional si no trabajó todo el semestre)
        if ($esProporcional) {
            // Proporcional: (Mejor sueldo × Meses trabajados) / 12
            $sacBruto = ($mejorSueldo * $mesesTrabajados) / 12;
        } else {
            // Completo: Mejor sueldo / 2
            $sacBruto = $mejorSueldo / 2;
        }
        
        return [
            'mejor_sueldo' => round($mejorSueldo, 2),
            'meses_trabajados' => $mesesTrabajados,
            'es_proporcional' => $esProporcional,
            'sac_bruto' => round($sacBruto, 2),
            'semestre' => $semester,
            'periodo' => $semester === 1 ? '1er Semestre' : '2do Semestre',
        ];
    }

    /**
     * Verificar si el mes corresponde a liquidación de SAC
     */
    private function isSACMonth(int $month): bool
    {
        return in_array($month, [6, 12]); // Junio o Diciembre
    }

    /**
     * Calcular recibo de SAC (Sueldo Anual Complementario) como recibo separado
     * El SAC tiene sus propias deducciones aplicadas sobre el monto bruto
     */
    private function calculateSACPayroll(Employee $employee, int $year, int $semester): array
    {
        // Determinar el mes de pago según el semestre
        $month = $semester === 1 ? 6 : 12;
        $startMonth = $semester === 1 ? 1 : 7;
        $endMonth = $semester === 1 ? 6 : 12;
        
        // Obtener categoría del empleado
        $category = $this->getEmployeeCategory($employee);
        
        // Calcular el SAC usando la función existente
        // Primero necesitamos el mejor sueldo del semestre
        $inicioSemestre = Carbon::createFromDate($year, $startMonth, 1);
        $finSemestre = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth();
        
        // Determinar meses trabajados basándose en la fecha de ingreso
        $mesesTrabajados = 6;
        $esProporcional = false;
        
        if ($employee->start_date) {
            $fechaIngreso = Carbon::parse($employee->start_date);
            
            if ($fechaIngreso->between($inicioSemestre, $finSemestre)) {
                $mesesTrabajados = $fechaIngreso->diffInMonths($finSemestre) + 1;
                $esProporcional = true;
            } elseif ($fechaIngreso->gt($finSemestre)) {
                // No corresponde SAC
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
                        'semester' => $semester,
                        'label' => 'SAC ' . ($semester === 1 ? '1er' : '2do') . ' Semestre ' . $year,
                    ],
                    'error' => 'El empleado ingresó después del fin del semestre',
                    'sac_bruto' => 0,
                    'neto_a_cobrar' => 0,
                ];
            }
        }
        
        // Buscar liquidaciones del semestre para obtener el mejor sueldo
        $payrolls = Payroll::where('employee_id', $employee->id)
            ->where('year', $year)
            ->whereBetween('month', [$startMonth, $endMonth])
            ->get();
        
        $sueldosRemunerativos = $payrolls->pluck('total_remunerativo')
            ->map(fn($v) => (float) $v)
            ->toArray();
        
        // Si no hay sueldos guardados, calcular el sueldo actual
        if (empty($sueldosRemunerativos)) {
            // Calcular liquidación del mes actual para tener un sueldo de referencia
            $payrollActual = $this->calculatePayroll($employee, $year, $month);
            $sueldosRemunerativos[] = $payrollActual['total_remunerativo'];
        }
        
        $mejorSueldo = max($sueldosRemunerativos);
        
        // Calcular SAC bruto
        if ($esProporcional) {
            $sacBruto = ($mejorSueldo * $mesesTrabajados) / 12;
        } else {
            $sacBruto = $mejorSueldo / 2;
        }
        
        // Construir el haber del SAC
        $sacLabel = 'SAC ' . ($semester === 1 ? '1er' : '2do') . ' Semestre ' . $year;
        if ($esProporcional) {
            $sacLabel .= ' (Proporcional ' . $mesesTrabajados . ' meses)';
        }
        
        $haberesCalculados = [
            [
                'nombre' => $sacLabel,
                'porcentaje' => null,
                'importe' => round($sacBruto, 2),
                'tipo' => 'sac',
                'remunerativo' => true,
            ]
        ];
        
        // Obtener deducciones activas
        $deducciones = SalaryItem::deducciones()->active()->forPeriod($month, $year)->orderBy('order')->get();
        
        // Calcular deducciones sobre el SAC bruto
        $deduccionesCalculadas = [];
        $totalDeducciones = 0;
        
        foreach ($deducciones as $deduccion) {
            // Si el concepto requiere asignación, verificar que el empleado lo tenga
            if ($deduccion->requires_assignment && !$employee->hasSalaryItem($deduccion->id)) {
                continue;
            }
            
            // Calcular la deducción sobre el SAC bruto
            $importe = 0;
            if ($deduccion->calculation_type === 'percentage') {
                $importe = $sacBruto * ($deduccion->value / 100);
            } elseif ($deduccion->calculation_type === 'fixed') {
                // Para montos fijos, aplicar proporcional al SAC
                // (el SAC es medio mes, así que aplicar proporción)
                $importe = $deduccion->value / 2;
            }
            
            if ($importe > 0) {
                $deduccionesCalculadas[] = [
                    'nombre' => $deduccion->name,
                    'porcentaje' => ($deduccion->calculation_type === 'percentage' && !$deduccion->hide_percentage_in_receipt) 
                        ? $deduccion->value . '%' : null,
                    'importe' => round($importe, 2),
                    'tipo' => $deduccion->calculation_type,
                ];
                $totalDeducciones += $importe;
            }
        }
        
        $netoACobrar = $sacBruto - $totalDeducciones;
        
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
                'semester' => $semester,
                'label' => 'SAC ' . ($semester === 1 ? '1er' : '2do') . ' Semestre ' . $year,
            ],
            'mejor_sueldo' => round($mejorSueldo, 2),
            'meses_trabajados' => $mesesTrabajados,
            'es_proporcional' => $esProporcional,
            'haberes' => $haberesCalculados,
            'sac_bruto' => round($sacBruto, 2),
            'deducciones' => $deduccionesCalculadas,
            'total_deducciones' => round($totalDeducciones, 2),
            'neto_a_cobrar' => round($netoACobrar, 2),
        ];
    }

    /**
     * Vista para liquidar SAC (Sueldo Anual Complementario)
     */
    public function sac(Request $request)
    {
        $employees = Employee::with(['jobs.category'])
            ->orderBy('lastName')
            ->get();

        $selectedEmployee = null;
        $sacPayroll = null;
        $year = $request->input('year', now()->year);
        $semester = $request->input('semester', now()->month <= 6 ? 1 : 2);

        if ($request->filled('employee_id')) {
            $selectedEmployee = Employee::with(['jobs.category'])->find($request->employee_id);
            
            if ($selectedEmployee) {
                $sacPayroll = $this->calculateSACPayroll($selectedEmployee, $year, $semester);
            }
        }

        return view('payroll.sac', [
            'employees' => $employees,
            'selectedEmployee' => $selectedEmployee,
            'sacPayroll' => $sacPayroll,
            'filters' => [
                'employee_id' => $request->employee_id,
                'year' => $year,
                'semester' => $semester,
            ],
        ]);
    }

    /**
     * Guardar liquidación de SAC
     */
    public function storeSAC(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'year' => 'required|integer',
            'semester' => 'required|integer|in:1,2',
        ]);

        $employee = Employee::with(['jobs.category'])->findOrFail($request->employee_id);
        $year = $request->year;
        $semester = $request->semester;
        $month = $semester === 1 ? 6 : 12;

        // Verificar si ya existe una liquidación de SAC para este período
        $existing = Payroll::where('employee_id', $employee->id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('period_label', 'like', 'SAC%')
            ->first();

        if ($existing && $existing->isLiquidado()) {
            return back()->with('error', 'Ya existe una liquidación de SAC cerrada para este período.');
        }

        // Calcular el SAC
        $sacData = $this->calculateSACPayroll($employee, $year, $semester);

        if (isset($sacData['error'])) {
            return back()->with('error', $sacData['error']);
        }

        if ($sacData['sac_bruto'] <= 0) {
            return back()->with('error', 'No se pudo calcular el SAC para este empleado.');
        }

        // Crear o actualizar la liquidación del SAC
        $payroll = Payroll::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'year' => $year,
                'month' => $month + 100, // Usar mes + 100 para diferenciar SAC (106 = SAC junio, 112 = SAC diciembre)
            ],
            [
                'period_label' => $sacData['periodo']['label'],
                'employee_name' => $sacData['empleado']['nombre'],
                'employee_cuil' => $sacData['empleado']['cuil'],
                'category_name' => $sacData['empleado']['categoria'],
                'position_name' => $employee->jobs->first()?->name,
                'antiguedad_years' => $sacData['empleado']['antiguedad_anos'],
                'start_date' => $employee->start_date,
                'salario_basico' => $sacData['mejor_sueldo'],
                'total_haberes' => $sacData['sac_bruto'],
                'total_remunerativo' => $sacData['sac_bruto'],
                'total_no_remunerativo' => 0,
                'total_deducciones' => $sacData['total_deducciones'],
                'neto_a_cobrar' => $sacData['neto_a_cobrar'],
                'status' => 'borrador',
                'created_by' => auth()->id(),
            ]
        );

        // Eliminar items anteriores y crear nuevos
        $payroll->items()->delete();

        // Agregar el haber del SAC
        $payroll->items()->create([
            'type' => 'haber',
            'name' => $sacData['haberes'][0]['nombre'],
            'percentage' => null,
            'amount' => $sacData['sac_bruto'],
            'is_remunerative' => true,
            'order' => 0,
        ]);

        // Agregar las deducciones
        $order = 0;
        foreach ($sacData['deducciones'] as $deduccion) {
            $payroll->items()->create([
                'type' => 'deduccion',
                'name' => $deduccion['nombre'],
                'percentage' => $deduccion['porcentaje'] ?? null,
                'amount' => $deduccion['importe'],
                'is_remunerative' => false,
                'order' => $order++,
            ]);
        }

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Liquidación de SAC guardada correctamente.');
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

    /**
     * Ver liquidaciones guardadas/cerradas
     */
    public function closed(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $payrolls = Payroll::with(['employee', 'items'])
            ->forPeriod($year, $month)
            ->orderBy('employee_name')
            ->get();

        return view('payroll.closed', [
            'payrolls' => $payrolls,
            'year' => $year,
            'month' => $month,
            'totals' => [
                'total_bruto' => $payrolls->sum('total_haberes'),
                'total_deducciones' => $payrolls->sum('total_deducciones'),
                'total_neto' => $payrolls->sum('neto_a_cobrar'),
            ],
        ]);
    }

    /**
     * Guardar/Cerrar una liquidación individual
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $employee = Employee::with(['jobs.category'])->findOrFail($request->employee_id);
        $year = $request->year;
        $month = $request->month;

        // Verificar si ya existe una liquidación para este período
        $existing = Payroll::where('employee_id', $employee->id)
            ->forPeriod($year, $month)
            ->first();

        if ($existing && $existing->isLiquidado()) {
            return back()->with('error', 'Ya existe una liquidación cerrada para este período.');
        }

        // Calcular la liquidación actual
        $payrollData = $this->calculatePayroll($employee, $year, $month);

        // Crear o actualizar la liquidación
        $payroll = Payroll::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'year' => $year,
                'month' => $month,
            ],
            [
                'period_label' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
                'employee_name' => $payrollData['empleado']['nombre'],
                'employee_cuil' => $payrollData['empleado']['cuil'],
                'category_name' => $payrollData['empleado']['categoria'],
                'position_name' => $employee->jobs->first()?->name,
                'antiguedad_years' => $payrollData['empleado']['antiguedad_anos'],
                'start_date' => $employee->start_date,
                'salario_basico' => $payrollData['salario_basico'],
                'total_haberes' => $payrollData['subtotal'],
                'total_remunerativo' => $payrollData['total_remunerativo'],
                'total_no_remunerativo' => $payrollData['total_no_remunerativo'],
                'total_deducciones' => $payrollData['total_deducciones'],
                'neto_a_cobrar' => $payrollData['neto_a_cobrar'],
                'status' => 'borrador',
                'created_by' => auth()->id(),
            ]
        );

        // Eliminar items anteriores y crear nuevos
        $payroll->items()->delete();

        $order = 0;
        foreach ($payrollData['haberes'] as $haber) {
            $payroll->items()->create([
                'type' => 'haber',
                'name' => $haber['nombre'],
                'percentage' => $haber['porcentaje'] ?? null,
                'amount' => $haber['importe'],
                'is_remunerative' => $haber['remunerativo'] ?? true,
                'order' => $order++,
            ]);
        }

        foreach ($payrollData['deducciones'] as $deduccion) {
            $payroll->items()->create([
                'type' => 'deduccion',
                'name' => $deduccion['nombre'],
                'percentage' => $deduccion['porcentaje'] ?? null,
                'amount' => $deduccion['importe'],
                'is_remunerative' => true,
                'order' => $order++,
            ]);
        }

        return back()->with('success', 'Liquidación guardada como borrador.');
    }

    /**
     * Cerrar/Liquidar definitivamente
     */
    public function liquidar(Payroll $payroll)
    {
        if ($payroll->isLiquidado()) {
            return back()->with('error', 'Esta liquidación ya está cerrada.');
        }

        $payroll->update([
            'status' => 'liquidado',
            'liquidated_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Liquidación cerrada correctamente. Los montos han quedado fijos.');
    }

    /**
     * Marcar como pagada
     */
    public function pagar(Payroll $payroll)
    {
        if ($payroll->isPagado()) {
            return back()->with('error', 'Esta liquidación ya está marcada como pagada.');
        }

        if ($payroll->status === 'borrador') {
            // Si es borrador, liquidar primero
            $payroll->update([
                'status' => 'pagado',
                'liquidated_at' => $payroll->liquidated_at ?? now(),
                'paid_at' => now(),
                'approved_by' => auth()->id(),
            ]);
        } else {
            $payroll->update([
                'status' => 'pagado',
                'paid_at' => now(),
            ]);
        }

        return back()->with('success', 'Liquidación marcada como pagada.');
    }

    /**
     * Reabrir una liquidación (solo si no está pagada)
     */
    public function reabrir(Payroll $payroll)
    {
        if ($payroll->isPagado()) {
            return back()->with('error', 'No se puede reabrir una liquidación pagada.');
        }

        $payroll->update([
            'status' => 'borrador',
            'liquidated_at' => null,
            'approved_by' => null,
        ]);

        return back()->with('success', 'Liquidación reabierta. Puede modificar los datos.');
    }

    /**
     * Eliminar una liquidación (solo borradores)
     */
    public function destroy(Payroll $payroll)
    {
        if ($payroll->isLiquidado()) {
            return back()->with('error', 'No se puede eliminar una liquidación cerrada o pagada.');
        }

        $payroll->delete();

        return back()->with('success', 'Liquidación eliminada.');
    }

    /**
     * Ver detalle de una liquidación cerrada
     */
    public function show(Payroll $payroll)
    {
        $payroll->load(['items', 'employee', 'createdBy', 'approvedBy']);

        return view('payroll.show', [
            'payroll' => $payroll,
        ]);
    }

    /**
     * Liquidación masiva - guardar todas
     */
    public function storeBulk(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $year = $request->year;
        $month = $request->month;
        $saved = 0;
        $skipped = 0;

        foreach ($request->employee_ids as $employeeId) {
            // Verificar si ya existe una liquidación cerrada
            $existing = Payroll::where('employee_id', $employeeId)
                ->forPeriod($year, $month)
                ->whereIn('status', ['liquidado', 'pagado'])
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            $employee = Employee::with(['jobs.category'])->find($employeeId);
            if (!$employee) continue;

            $payrollData = $this->calculatePayroll($employee, $year, $month);

            $payroll = Payroll::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'period_label' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
                    'employee_name' => $payrollData['empleado']['nombre'],
                    'employee_cuil' => $payrollData['empleado']['cuil'],
                    'category_name' => $payrollData['empleado']['categoria'],
                    'position_name' => $employee->jobs->first()?->name,
                    'antiguedad_years' => $payrollData['empleado']['antiguedad_anos'],
                    'start_date' => $employee->start_date,
                    'salario_basico' => $payrollData['salario_basico'],
                    'total_haberes' => $payrollData['subtotal'],
                    'total_remunerativo' => $payrollData['total_remunerativo'],
                    'total_no_remunerativo' => $payrollData['total_no_remunerativo'],
                    'total_deducciones' => $payrollData['total_deducciones'],
                    'neto_a_cobrar' => $payrollData['neto_a_cobrar'],
                    'status' => 'borrador',
                    'created_by' => auth()->id(),
                ]
            );

            $payroll->items()->delete();

            $order = 0;
            foreach ($payrollData['haberes'] as $haber) {
                $payroll->items()->create([
                    'type' => 'haber',
                    'name' => $haber['nombre'],
                    'percentage' => $haber['porcentaje'] ?? null,
                    'amount' => $haber['importe'],
                    'is_remunerative' => $haber['remunerativo'] ?? true,
                    'order' => $order++,
                ]);
            }

            foreach ($payrollData['deducciones'] as $deduccion) {
                $payroll->items()->create([
                    'type' => 'deduccion',
                    'name' => $deduccion['nombre'],
                    'percentage' => $deduccion['porcentaje'] ?? null,
                    'amount' => $deduccion['importe'],
                    'is_remunerative' => true,
                    'order' => $order++,
                ]);
            }

            $saved++;
        }

        return back()->with('success', "Se guardaron {$saved} liquidaciones. {$skipped} omitidas (ya cerradas).");
    }

    /**
     * Liquidar masivamente todas las liquidaciones en borrador
     */
    public function liquidarBulk(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $updated = Payroll::forPeriod($request->year, $request->month)
            ->where('status', 'borrador')
            ->update([
                'status' => 'liquidado',
                'liquidated_at' => now(),
                'approved_by' => auth()->id(),
            ]);

        return back()->with('success', "Se cerraron {$updated} liquidaciones.");
    }

    /**
     * Marcar como pagadas masivamente
     */
    public function pagarBulk(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $updated = Payroll::forPeriod($request->year, $request->month)
            ->where('status', 'liquidado')
            ->update([
                'status' => 'pagado',
                'paid_at' => now(),
            ]);

        return back()->with('success', "Se marcaron {$updated} liquidaciones como pagadas.");
    }

    /**
     * Descargar recibo en PDF
     */
    public function downloadPdf(Payroll $payroll)
    {
        $payroll->load(['haberes', 'deducciones', 'approvedBy', 'employee']);
        
        // Nombre del archivo: recibo-mesaño-nombreempleado.pdf
        $mesNombre = Carbon::createFromDate($payroll->year, $payroll->month, 1)
            ->locale('es')
            ->isoFormat('MMMM');
        
        // Limpiar el nombre del empleado para el archivo
        $nombreEmpleado = str_replace(' ', '_', $payroll->employee_name);
        $nombreEmpleado = preg_replace('/[^A-Za-z0-9_]/', '', $nombreEmpleado);
        
        $filename = "recibo-{$mesNombre}{$payroll->year}-{$nombreEmpleado}.pdf";
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.pdf', [
            'payroll' => $payroll,
        ]);
        
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download($filename);
    }
}

