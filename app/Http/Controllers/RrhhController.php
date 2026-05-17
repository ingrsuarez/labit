<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Job;
use App\Models\Leave;
use App\Models\Payroll;
use App\Support\RrhhNavigation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RrhhController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! RrhhNavigation::userCanAccessHub($user)) {
            abort(403);
        }

        $sections = RrhhNavigation::sectionsForUser($user);

        return view('rrhh.index', compact('sections'));
    }

    public function resumen(Request $request)
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['admin', 'contador'])) {
            abort(403);
        }

        $totalEmpleados = Employee::count();
        $empleadosActivos = Employee::where('status', 'active')->count();
        $empleadosInactivos = Employee::where('status', 'inactive')->count();
        $totalPuestos = Job::count();

        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        $ausenciasDelMes = Leave::where(function ($q) use ($inicioMes, $finMes) {
            $q->whereBetween('start', [$inicioMes, $finMes])
                ->orWhereBetween('end', [$inicioMes, $finMes])
                ->orWhere(function ($q2) use ($inicioMes, $finMes) {
                    $q2->where('start', '<=', $inicioMes)
                        ->where('end', '>=', $finMes);
                });
        })->count();

        $solicitudesPendientes = Leave::where('status', 'pendiente')->count();

        $promedioAntiguedad = Employee::where('status', 'active')
            ->whereNotNull('start_date')
            ->get()
            ->avg(function ($emp) {
                return Carbon::parse($emp->start_date)->diffInYears(now());
            });
        $promedioAntiguedad = round($promedioAntiguedad ?? 0, 1);

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

        $empleadosPorDepartamento = DB::table('job_employee')
            ->join('jobs', 'jobs.id', '=', 'job_employee.job_id')
            ->join('employees', 'employees.id', '=', 'job_employee.employee_id')
            ->where('employees.status', 'active')
            ->select('jobs.department', DB::raw('COUNT(DISTINCT job_employee.employee_id) as total'))
            ->groupBy('jobs.department')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->department ?: 'Sin departamento',
                    'value' => $item->total,
                ];
            });

        $empleadosPorGenero = Employee::where('status', 'active')
            ->select('sex', DB::raw('COUNT(*) as total'))
            ->groupBy('sex')
            ->get()
            ->map(function ($item) {
                $labels = [
                    'M' => 'Masculino',
                    'F' => 'Femenino',
                    'O' => 'Otro',
                ];

                return [
                    'label' => $labels[$item->sex] ?? $item->sex ?? 'No especificado',
                    'value' => $item->total,
                ];
            });

        $inicioTrimestre = Carbon::now()->subMonths(3)->startOfMonth();
        $ausenciasPorTipo = Leave::where('start', '>=', $inicioTrimestre)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->get()
            ->map(function ($item) {
                $labels = [
                    'vacaciones' => 'Vacaciones',
                    'enfermedad' => 'Enfermedad',
                    'maternidad' => 'Maternidad',
                    'paternidad' => 'Paternidad',
                    'estudio' => 'Estudio',
                    'mudanza' => 'Mudanza',
                    'fallecimiento' => 'Fallecimiento',
                    'matrimonio' => 'Matrimonio',
                    'otro' => 'Otro',
                ];

                return [
                    'label' => $labels[$item->type] ?? $item->type,
                    'value' => $item->total,
                ];
            });

        $hace12Meses = Carbon::now()->subMonths(12)->startOfMonth();
        $contratacionesPorMes = Employee::whereNotNull('start_date')
            ->where('start_date', '>=', $hace12Meses)
            ->get()
            ->groupBy(function ($emp) {
                return Carbon::parse($emp->start_date)->format('Y-m');
            })
            ->map(function ($grupo, $fecha) {
                return [
                    'label' => Carbon::createFromFormat('Y-m', $fecha)->locale('es')->isoFormat('MMM YY'),
                    'value' => $grupo->count(),
                ];
            })
            ->values();

        $mesesCompletos = collect();
        for ($i = 11; $i >= 0; $i--) {
            $mes = Carbon::now()->subMonths($i);
            $existe = $contratacionesPorMes->firstWhere('label', $mes->locale('es')->isoFormat('MMM YY'));
            $mesesCompletos->push([
                'label' => $mes->locale('es')->isoFormat('MMM YY'),
                'value' => $existe ? $existe['value'] : 0,
            ]);
        }
        $contratacionesPorMes = $mesesCompletos;

        $hoy = Carbon::now();
        $proximosCumpleanos = Employee::where('status', 'active')
            ->whereNotNull('birth')
            ->get()
            ->map(function ($emp) use ($hoy) {
                $cumple = Carbon::parse($emp->birth);
                $cumpleEsteAno = $cumple->copy()->year($hoy->year);

                if ($cumpleEsteAno->lt($hoy)) {
                    $cumpleEsteAno->addYear();
                }

                $diasParaCumple = (int) $hoy->diffInDays($cumpleEsteAno, false);

                return [
                    'employee' => $emp,
                    'fecha' => $cumpleEsteAno,
                    'dias' => $diasParaCumple,
                ];
            })
            ->filter(fn ($item) => $item['dias'] >= 0 && $item['dias'] <= 30)
            ->sortBy('dias')
            ->take(5);

        $enVacacionesHoy = Leave::where('type', 'vacaciones')
            ->where('status', 'aprobado')
            ->where('start', '<=', $hoy)
            ->where('end', '>=', $hoy)
            ->with('employee')
            ->get();

        $solicitudesRecientes = Leave::where('status', 'pendiente')
            ->with('employee')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $topPuestos = DB::table('job_employee')
            ->join('jobs', 'jobs.id', '=', 'job_employee.job_id')
            ->join('employees', 'employees.id', '=', 'job_employee.employee_id')
            ->where('employees.status', 'active')
            ->select('jobs.id', 'jobs.name', 'jobs.department', DB::raw('COUNT(job_employee.employee_id) as total'))
            ->groupBy('jobs.id', 'jobs.name', 'jobs.department')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('rrhh.resumen', compact(
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
