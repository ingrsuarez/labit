<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeePortalController extends Controller
{
    /**
     * Mostrar el dashboard del empleado (Mi Perfil)
     */
    public function dashboard()
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Esta verificación es redundante si has.employee middleware está activo,
        // pero la dejamos por seguridad, redirigiendo a access.pending
        if (!$employee) {
            return redirect()->route('access.pending')
                ->with('error', 'No tienes un empleado asociado a tu cuenta.');
        }

        $employee->load(['jobs.category', 'leaves', 'salaryItems']);
        
        // Calcular antigüedad
        $startDate = $employee->start_date ? Carbon::parse($employee->start_date) : null;
        $antiguedadExacta = $startDate ? $startDate->floatDiffInYears(now()) : 0;
        $antiguedad = number_format($antiguedadExacta, 1, ',', '.'); // Un solo decimal, formato español
        $antiguedadPorcentaje = number_format(min($antiguedadExacta * 2, 70), 1); // Porcentaje con un decimal
        $antiguedadMeses = $startDate ? $startDate->diff(now())->format('%y años, %m meses') : '—';
        
        // Obtener categoría del empleado
        $category = $employee->jobs->first()?->category;
        
        // Resumen de licencias del año actual
        $currentYear = now()->year;
        $leavesThisYear = $employee->leaves()
            ->whereYear('start', $currentYear)
            ->get();
        
        $leavesSummary = [
            'vacaciones' => $leavesThisYear->where('type', 'vacaciones')->sum('days'),
            'enfermedad' => $leavesThisYear->where('type', 'enfermedad')->sum('days'),
            'otros' => $leavesThisYear->whereNotIn('type', ['vacaciones', 'enfermedad'])->sum('days'),
            'total' => $leavesThisYear->sum('days'),
        ];
        
        // Últimas licencias
        $recentLeaves = $employee->leaves()
            ->orderByDesc('start')
            ->limit(5)
            ->get();

        // Verificar si es supervisor
        $isSupervisor = $employee->isSupervisor();
        $subordinatesCount = $isSupervisor ? $employee->getSubordinates()->count() : 0;
        
        return view('portal.dashboard', compact(
            'employee', 
            'antiguedad',
            'antiguedadPorcentaje',
            'antiguedadMeses',
            'category',
            'leavesSummary',
            'recentLeaves',
            'currentYear',
            'isSupervisor',
            'subordinatesCount'
        ));
    }

    /**
     * Mostrar el equipo a cargo (para supervisores)
     */
    public function team(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('access.pending')
                ->with('error', 'No tienes un empleado asociado a tu cuenta.');
        }

        if (!$employee->isSupervisor()) {
            return redirect()->route('portal.dashboard')
                ->with('error', 'No tienes empleados a tu cargo.');
        }

        $subordinates = $employee->getSubordinates();
        
        // Cargar relaciones necesarias
        $subordinateIds = $subordinates->pluck('id');
        $subordinates = Employee::whereIn('id', $subordinateIds)
            ->with(['jobs.category', 'leaves' => function($q) {
                $q->whereYear('start', now()->year)
                  ->where('status', 'aprobado');
            }])
            ->get();

        // Resumen del equipo
        $teamSummary = [
            'total' => $subordinates->count(),
            'activos' => $subordinates->where('status', 'active')->count(),
            'inactivos' => $subordinates->where('status', 'inactive')->count(),
        ];

        // Licencias pendientes de aprobación del equipo
        $pendingLeaves = Leave::whereIn('employee_id', $subordinateIds)
            ->where('status', 'pendiente')
            ->with('employee')
            ->orderByDesc('created_at')
            ->get();

        // Próximos cumpleaños del equipo (30 días)
        $upcomingBirthdays = $this->getUpcomingBirthdays($subordinates, 30);

        // Vacaciones actuales del equipo
        $currentVacations = Leave::whereIn('employee_id', $subordinateIds)
            ->where('type', 'vacaciones')
            ->where('status', 'aprobado')
            ->where('start', '<=', now())
            ->where('end', '>=', now())
            ->with('employee')
            ->get();

        return view('portal.team', compact(
            'employee',
            'subordinates',
            'teamSummary',
            'pendingLeaves',
            'upcomingBirthdays',
            'currentVacations'
        ));
    }

    /**
     * Directorio de empleados (vacaciones y cumpleaños)
     */
    public function directory(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('access.pending')
                ->with('error', 'No tienes un empleado asociado a tu cuenta.');
        }

        $tab = $request->get('tab', 'birthdays');

        // Todos los empleados activos (excepto el actual)
        $allEmployees = Employee::where('status', 'active')
            ->where('id', '!=', $employee->id)
            ->with('jobs')
            ->get();

        // Próximos cumpleaños (siguiente mes)
        $upcomingBirthdays = $this->getUpcomingBirthdays($allEmployees, 30);

        // Cumpleaños de hoy
        $todayBirthdays = $allEmployees->filter(function($emp) {
            if (!$emp->birth) return false;
            $birth = Carbon::parse($emp->birth);
            return $birth->month === now()->month && $birth->day === now()->day;
        });

        // Empleados de vacaciones actualmente
        $currentVacations = Leave::where('type', 'vacaciones')
            ->where('status', 'aprobado')
            ->where('start', '<=', now())
            ->where('end', '>=', now())
            ->with('employee.jobs')
            ->get();

        // Próximas vacaciones (siguiente mes)
        $upcomingVacations = Leave::where('type', 'vacaciones')
            ->where('status', 'aprobado')
            ->where('start', '>', now())
            ->where('start', '<=', now()->addDays(30))
            ->with('employee.jobs')
            ->orderBy('start')
            ->get();

        // Verificar si es supervisor
        $isSupervisor = $employee->isSupervisor();

        return view('portal.directory', compact(
            'employee',
            'tab',
            'upcomingBirthdays',
            'todayBirthdays',
            'currentVacations',
            'upcomingVacations',
            'isSupervisor'
        ));
    }

    /**
     * Vista de solicitudes (vacaciones y licencias del empleado)
     */
    public function requests(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('access.pending')
                ->with('error', 'No tienes un empleado asociado a tu cuenta.');
        }

        $tab = $request->get('tab', 'vacations');
        $year = $request->get('year', now()->year);

        // Resumen de vacaciones
        $vacationSummary = $employee->getVacationSummary($year);

        // Solicitudes de vacaciones del empleado
        $vacationRequests = Leave::where('employee_id', $employee->id)
            ->where('type', 'vacaciones')
            ->whereYear('start', $year)
            ->orderByDesc('start')
            ->get();

        // Otras licencias del empleado
        $otherLeaves = Leave::where('employee_id', $employee->id)
            ->where('type', '!=', 'vacaciones')
            ->whereYear('start', $year)
            ->orderByDesc('start')
            ->get();

        // Tipos de licencia disponibles
        $leaveTypes = [
            'enfermedad' => 'Enfermedad',
            'maternidad' => 'Maternidad',
            'paternidad' => 'Paternidad',
            'estudio' => 'Estudio',
            'mudanza' => 'Mudanza',
            'fallecimiento' => 'Fallecimiento familiar',
            'matrimonio' => 'Matrimonio',
            'otro' => 'Otro',
        ];

        return view('portal.requests', compact(
            'employee',
            'tab',
            'year',
            'vacationSummary',
            'vacationRequests',
            'otherLeaves',
            'leaveTypes'
        ));
    }

    /**
     * Crear solicitud de vacaciones desde el portal
     */
    public function storeVacationRequest(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('access.pending');
        }

        $validated = $request->validate([
            'start' => 'required|date|after_or_equal:today',
            'end' => 'required|date|after_or_equal:start',
            'description' => 'nullable|string|max:500',
        ]);

        $start = Carbon::parse($validated['start']);
        $end = Carbon::parse($validated['end']);

        // Calcular días hábiles
        $workingDays = 0;
        $current = $start->copy();
        while ($current <= $end) {
            if (!$current->isWeekend()) {
                $workingDays++;
            }
            $current->addDay();
        }

        if ($workingDays <= 0) {
            return back()->withErrors([
                'days' => 'El rango seleccionado no contiene días hábiles.'
            ])->withInput();
        }

        // Verificar días disponibles
        $availableDays = $employee->getAvailableVacationDays($start->year);
        
        if ($workingDays > $availableDays) {
            return back()->withErrors([
                'days' => "Solo tienes {$availableDays} días disponibles. Solicitaste {$workingDays} días."
            ])->withInput();
        }

        // Crear la solicitud
        Leave::create([
            'employee_id' => $employee->id,
            'type' => 'vacaciones',
            'start' => $validated['start'],
            'end' => $validated['end'],
            'description' => $validated['description'] ?? 'Solicitud desde portal del empleado',
            'status' => 'pendiente',
            'user_id' => $user->id,
        ]);

        return redirect()->route('portal.requests')
            ->with('success', 'Solicitud de vacaciones enviada correctamente. Será revisada por tu supervisor.');
    }

    /**
     * Crear solicitud de licencia desde el portal
     */
    public function storeLeaveRequest(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('access.pending');
        }

        $validated = $request->validate([
            'type' => 'required|string|in:enfermedad,maternidad,paternidad,estudio,mudanza,fallecimiento,matrimonio,otro',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
            'description' => 'required|string|max:500',
        ]);

        // Crear la solicitud
        Leave::create([
            'employee_id' => $employee->id,
            'type' => $validated['type'],
            'start' => $validated['start'],
            'end' => $validated['end'],
            'description' => $validated['description'],
            'status' => 'pendiente',
            'user_id' => $user->id,
        ]);

        return redirect()->route('portal.requests', ['tab' => 'leaves'])
            ->with('success', 'Solicitud de licencia enviada correctamente.');
    }

    /**
     * Cancelar una solicitud pendiente
     */
    public function cancelRequest(Leave $leave)
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Verificar que la solicitud pertenece al empleado
        if (!$employee || $leave->employee_id !== $employee->id) {
            return back()->withErrors(['error' => 'No puedes cancelar esta solicitud.']);
        }

        // Solo se pueden cancelar solicitudes pendientes
        if ($leave->status !== 'pendiente') {
            return back()->withErrors(['error' => 'Solo puedes cancelar solicitudes pendientes.']);
        }

        $leave->update(['status' => 'cancelado']);

        return back()->with('success', 'Solicitud cancelada correctamente.');
    }

    /**
     * Obtener próximos cumpleaños ordenados
     */
    protected function getUpcomingBirthdays($employees, int $days): \Illuminate\Support\Collection
    {
        $today = now();
        $endDate = now()->addDays($days);
        
        return $employees->filter(function($emp) {
            return $emp->birth !== null;
        })->map(function($emp) use ($today) {
            $birth = Carbon::parse($emp->birth);
            $nextBirthday = Carbon::create($today->year, $birth->month, $birth->day);
            
            // Si ya pasó este año, usar el del próximo
            if ($nextBirthday < $today) {
                $nextBirthday->addYear();
            }
            
            $emp->next_birthday = $nextBirthday;
            $emp->days_until_birthday = $today->diffInDays($nextBirthday, false);
            $emp->turning_age = $nextBirthday->year - $birth->year;
            
            return $emp;
        })->filter(function($emp) use ($days) {
            return $emp->days_until_birthday >= 0 && $emp->days_until_birthday <= $days;
        })->sortBy('days_until_birthday')
        ->values();
    }
}

