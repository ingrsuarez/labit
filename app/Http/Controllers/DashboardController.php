<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Employee;
use App\Models\Job;
use App\Models\Leave;
use App\Models\Payroll;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Si el usuario solo tiene empleado asociado (sin roles administrativos),
        // redirigir al portal de empleados
        if ($user->employee && $user->roles->count() === 0 && $user->permissions->count() === 0) {
            return redirect()->route('portal.dashboard');
        }

        // ============================================
        // KPIs PRINCIPALES
        // ============================================
        $totalEmpleados = Employee::count();
        $empleadosActivos = Employee::where('status', 'active')->count();
        $empleadosInactivos = Employee::where('status', 'inactive')->count();
        $totalPuestos = Job::count();
        
        // Ausencias del mes actual
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        
        $ausenciasDelMes = Leave::where(function($q) use ($inicioMes, $finMes) {
            $q->whereBetween('start', [$inicioMes, $finMes])
              ->orWhereBetween('end', [$inicioMes, $finMes])
              ->orWhere(function($q2) use ($inicioMes, $finMes) {
                  $q2->where('start', '<=', $inicioMes)
                     ->where('end', '>=', $finMes);
              });
        })->count();

        // Solicitudes pendientes
        $solicitudesPendientes = Leave::where('status', 'pendiente')->count();

        // Promedio de antigüedad
        $promedioAntiguedad = Employee::where('status', 'active')
            ->whereNotNull('start_date')
            ->get()
            ->avg(function($emp) {
                return Carbon::parse($emp->start_date)->diffInYears(now());
            });
        $promedioAntiguedad = round($promedioAntiguedad ?? 0, 1);

        // Costo de nómina del último mes (si hay payrolls liquidados o pagados)
        $ultimoMesPagado = Payroll::whereIn('status', ['liquidado', 'pagado'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();
        
        $costoNomina = 0;
        if ($ultimoMesPagado) {
            $costoNomina = Payroll::whereIn('status', ['liquidado', 'pagado'])
                ->where('year', $ultimoMesPagado->year)
                ->where('month', $ultimoMesPagado->month)
                ->sum('neto_a_cobrar');
        }

        // ============================================
        // GRÁFICO: Empleados por Departamento
        // ============================================
        $empleadosPorDepartamento = DB::table('job_employee')
            ->join('jobs', 'jobs.id', '=', 'job_employee.job_id')
            ->join('employees', 'employees.id', '=', 'job_employee.employee_id')
            ->where('employees.status', 'active')
            ->select('jobs.department', DB::raw('COUNT(DISTINCT job_employee.employee_id) as total'))
            ->groupBy('jobs.department')
            ->orderByDesc('total')
            ->get()
            ->map(function($item) {
                return [
                    'label' => $item->department ?: 'Sin departamento',
                    'value' => $item->total
                ];
            });

        // ============================================
        // GRÁFICO: Distribución por Género
        // ============================================
        $empleadosPorGenero = Employee::where('status', 'active')
            ->select('sex', DB::raw('COUNT(*) as total'))
            ->groupBy('sex')
            ->get()
            ->map(function($item) {
                $labels = [
                    'M' => 'Masculino',
                    'F' => 'Femenino',
                    'O' => 'Otro'
                ];
                return [
                    'label' => $labels[$item->sex] ?? $item->sex ?? 'No especificado',
                    'value' => $item->total
                ];
            });

        // ============================================
        // GRÁFICO: Ausencias por Tipo (últimos 3 meses)
        // ============================================
        $inicioTrimestre = Carbon::now()->subMonths(3)->startOfMonth();
        $ausenciasPorTipo = Leave::where('start', '>=', $inicioTrimestre)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->get()
            ->map(function($item) {
                $labels = [
                    'vacaciones' => 'Vacaciones',
                    'enfermedad' => 'Enfermedad',
                    'maternidad' => 'Maternidad',
                    'paternidad' => 'Paternidad',
                    'estudio' => 'Estudio',
                    'mudanza' => 'Mudanza',
                    'fallecimiento' => 'Fallecimiento',
                    'matrimonio' => 'Matrimonio',
                    'otro' => 'Otro'
                ];
                return [
                    'label' => $labels[$item->type] ?? $item->type,
                    'value' => $item->total
                ];
            });

        // ============================================
        // GRÁFICO: Contrataciones por Mes (últimos 12 meses)
        // ============================================
        $hace12Meses = Carbon::now()->subMonths(12)->startOfMonth();
        $contratacionesPorMes = Employee::whereNotNull('start_date')
            ->where('start_date', '>=', $hace12Meses)
            ->get()
            ->groupBy(function($emp) {
                return Carbon::parse($emp->start_date)->format('Y-m');
            })
            ->map(function($grupo, $fecha) {
                return [
                    'label' => Carbon::createFromFormat('Y-m', $fecha)->locale('es')->isoFormat('MMM YY'),
                    'value' => $grupo->count()
                ];
            })
            ->values();

        // Rellenar meses vacíos
        $mesesCompletos = collect();
        for ($i = 11; $i >= 0; $i--) {
            $mes = Carbon::now()->subMonths($i);
            $key = $mes->format('Y-m');
            $existe = $contratacionesPorMes->firstWhere('label', $mes->locale('es')->isoFormat('MMM YY'));
            $mesesCompletos->push([
                'label' => $mes->locale('es')->isoFormat('MMM YY'),
                'value' => $existe ? $existe['value'] : 0
            ]);
        }
        $contratacionesPorMes = $mesesCompletos;

        // ============================================
        // PRÓXIMOS CUMPLEAÑOS (próximos 30 días)
        // ============================================
        $hoy = Carbon::now();
        $proximosCumpleanos = Employee::where('status', 'active')
            ->whereNotNull('birth')
            ->get()
            ->map(function($emp) use ($hoy) {
                $cumple = Carbon::parse($emp->birth);
                $cumpleEsteAno = $cumple->copy()->year($hoy->year);
                
                if ($cumpleEsteAno->lt($hoy)) {
                    $cumpleEsteAno->addYear();
                }
                
                $diasParaCumple = $hoy->diffInDays($cumpleEsteAno, false);
                
                return [
                    'employee' => $emp,
                    'fecha' => $cumpleEsteAno,
                    'dias' => $diasParaCumple
                ];
            })
            ->filter(fn($item) => $item['dias'] >= 0 && $item['dias'] <= 30)
            ->sortBy('dias')
            ->take(5);

        // ============================================
        // EMPLEADOS DE VACACIONES HOY
        // ============================================
        $enVacacionesHoy = Leave::where('type', 'vacaciones')
            ->where('status', 'aprobado')
            ->where('start', '<=', $hoy)
            ->where('end', '>=', $hoy)
            ->with('employee')
            ->get();

        // ============================================
        // SOLICITUDES PENDIENTES DE APROBACIÓN
        // ============================================
        $solicitudesRecientes = Leave::where('status', 'pendiente')
            ->with('employee')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // ============================================
        // TOP PUESTOS
        // ============================================
        $topPuestos = DB::table('job_employee')
            ->join('jobs', 'jobs.id', '=', 'job_employee.job_id')
            ->join('employees', 'employees.id', '=', 'job_employee.employee_id')
            ->where('employees.status', 'active')
            ->select('jobs.id', 'jobs.name', 'jobs.department', DB::raw('COUNT(job_employee.employee_id) as total'))
            ->groupBy('jobs.id', 'jobs.name', 'jobs.department')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalEmpleados',
            'empleadosActivos',
            'empleadosInactivos',
            'totalPuestos',
            'ausenciasDelMes',
            'solicitudesPendientes',
            'promedioAntiguedad',
            'costoNomina',
            'ultimoMesPagado',
            'empleadosPorDepartamento',
            'empleadosPorGenero',
            'ausenciasPorTipo',
            'contratacionesPorMes',
            'proximosCumpleanos',
            'enVacacionesHoy',
            'solicitudesRecientes',
            'topPuestos'
        ));
    }
}
