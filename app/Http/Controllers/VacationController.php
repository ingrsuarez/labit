<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\User;
use App\Services\WorkingDaysService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class VacationController extends Controller
{
    /**
     * Dashboard principal de vacaciones
     */
    public function index(Request $request)
    {
        $year = $request->input('year', now()->year);
        
        // Solicitudes pendientes de aprobación
        $pendingRequests = Leave::where('type', 'vacaciones')
            ->where('status', 'pendiente')
            ->where('start', '>=', now())
            ->with('employee')
            ->orderBy('start')
            ->get();
        
        // Vacaciones aprobadas futuras
        $approvedFuture = Leave::where('type', 'vacaciones')
            ->where('status', 'aprobado')
            ->where('start', '>=', now())
            ->with('employee')
            ->orderBy('start')
            ->get();
        
        // Vacaciones en curso
        $current = Leave::where('type', 'vacaciones')
            ->where('status', 'aprobado')
            ->where('start', '<=', now())
            ->where('end', '>=', now())
            ->with('employee')
            ->get();
        
        // Resumen del año
        $summary = [
            'total_requests' => Leave::where('type', 'vacaciones')->whereYear('start', $year)->count(),
            'pending' => Leave::where('type', 'vacaciones')->where('status', 'pendiente')->whereYear('start', $year)->count(),
            'approved' => Leave::where('type', 'vacaciones')->where('status', 'aprobado')->whereYear('start', $year)->count(),
            'rejected' => Leave::where('type', 'vacaciones')->where('status', 'rechazado')->whereYear('start', $year)->count(),
            'total_days' => Leave::where('type', 'vacaciones')->where('status', 'aprobado')->whereYear('start', $year)->sum('days'),
        ];
        
        // Empleados para el formulario de solicitud
        $employees = Employee::where('status', 'active')
            ->orderBy('lastName')
            ->orderBy('name')
            ->get();
        
        return view('vacation.index', compact(
            'pendingRequests',
            'approvedFuture',
            'current',
            'summary',
            'employees',
            'year'
        ));
    }

    /**
     * Panel de aprobación con visualización de superposiciones
     */
    public function approvalPanel(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        
        // Obtener todas las solicitudes del mes seleccionado
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
        
        // Solicitudes pendientes
        $pendingRequests = Leave::where('type', 'vacaciones')
            ->where('status', 'pendiente')
            ->where(function($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('start', [$startOfMonth, $endOfMonth])
                  ->orWhereBetween('end', [$startOfMonth, $endOfMonth])
                  ->orWhere(function($q2) use ($startOfMonth, $endOfMonth) {
                      $q2->where('start', '<=', $startOfMonth)
                         ->where('end', '>=', $endOfMonth);
                  });
            })
            ->with('employee.jobs')
            ->orderBy('start')
            ->get();
        
        // Vacaciones ya aprobadas en el período
        $approvedVacations = Leave::where('type', 'vacaciones')
            ->where('status', 'aprobado')
            ->where(function($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('start', [$startOfMonth, $endOfMonth])
                  ->orWhereBetween('end', [$startOfMonth, $endOfMonth])
                  ->orWhere(function($q2) use ($startOfMonth, $endOfMonth) {
                      $q2->where('start', '<=', $startOfMonth)
                         ->where('end', '>=', $endOfMonth);
                  });
            })
            ->with('employee.jobs')
            ->orderBy('start')
            ->get();
        
        // Detectar superposiciones
        $overlaps = $this->detectOverlaps($pendingRequests, $approvedVacations);
        
        // Generar datos para el calendario
        $calendarData = $this->generateCalendarData($year, $month, $pendingRequests->merge($approvedVacations));
        
        return view('vacation.approval', compact(
            'pendingRequests',
            'approvedVacations',
            'overlaps',
            'calendarData',
            'month',
            'year'
        ));
    }

    /**
     * Crear nueva solicitud de vacaciones
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
            'description' => 'nullable|string|max:500',
        ]);
        
        $start = Carbon::parse($validated['start']);
        $end = Carbon::parse($validated['end']);
        
        // Calcular días hábiles (excluye sábados, domingos y feriados)
        $daysDetail = WorkingDaysService::getDaysDetail($start, $end);
        $days = $daysDetail['working'];
        
        if ($days <= 0) {
            return back()->withErrors([
                'days' => 'El rango seleccionado no contiene días hábiles (solo fines de semana o feriados).'
            ])->withInput();
        }
        
        // Verificar días disponibles del empleado según ley argentina
        $employee = Employee::find($validated['employee_id']);
        $availableDays = $employee->getAvailableVacationDays($start->year);
        $totalByLaw = $employee->vacation_days_by_law;
        
        if ($days > $availableDays) {
            return back()->withErrors([
                'days' => "El empleado tiene {$availableDays} días disponibles de {$totalByLaw} (antigüedad: {$employee->antiquity_years} años)."
            ])->withInput();
        }
        
        // Si la fecha de inicio es pasada, aprobar automáticamente (registro histórico)
        $isPastDate = $start->lt(now()->startOfDay());
        $status = $isPastDate ? 'aprobado' : 'pendiente';
        
        // 'days' y 'year' son columnas generadas automáticamente, no se incluyen en create
        $leave = Leave::create([
            'employee_id' => $validated['employee_id'],
            'type' => 'vacaciones',
            'start' => $validated['start'],
            'end' => $validated['end'],
            'description' => $validated['description'] ?? '',
            'status' => $status,
            'user_id' => auth()->id(),
            'approved_by' => $isPastDate ? auth()->id() : null,
            'approved_at' => $isPastDate ? now() : null,
        ]);
        
        $message = $isPastDate 
            ? 'Vacaciones registradas y aprobadas automáticamente (fecha pasada).'
            : 'Solicitud de vacaciones creada correctamente.';
        
        return redirect()->route('vacation.index')
            ->with('success', $message);
    }

    /**
     * Aprobar solicitud de vacaciones
     */
    public function approve(Leave $leave)
    {
        if ($leave->type !== 'vacaciones' || $leave->status !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta solicitud no puede ser aprobada.']);
        }
        
        $leave->update([
            'status' => 'aprobado',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        
        return back()->with('success', 'Solicitud aprobada correctamente.');
    }

    /**
     * Rechazar solicitud de vacaciones
     */
    public function reject(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);
        
        if ($leave->type !== 'vacaciones' || $leave->status !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta solicitud no puede ser rechazada.']);
        }
        
        $leave->update([
            'status' => 'rechazado',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);
        
        return back()->with('success', 'Solicitud rechazada.');
    }

    /**
     * Generar PDF de solicitud de vacaciones
     */
    public function generatePdf(Leave $leave)
    {
        $leave->load(['employee.jobs.category', 'approver']);
        
        $employee = $leave->employee;
        $job = $employee->jobs->first();
        $category = $job?->category;
        
        // Calcular días disponibles según ley argentina
        $year = Carbon::parse($leave->start)->year;
        $totalDays = $employee->vacation_days_by_law;
        $usedDays = $employee->getUsedVacationDays($year);
        
        // Si esta solicitud ya está aprobada, no la contamos dos veces
        if ($leave->status === 'aprobado') {
            $usedDays -= $leave->days;
        }
        
        $availableDays = max(0, $totalDays - $usedDays);
        
        $requestDate = $leave->created_at;
        if ($requestDate && !$requestDate instanceof Carbon) {
            $requestDate = Carbon::parse($requestDate);
        }

        $data = [
            'leave' => $leave,
            'employee' => $employee,
            'job' => $job,
            'category' => $category,
            'totalDays' => $totalDays,
            'usedDays' => $usedDays,
            'availableDays' => $availableDays,
            'requestDate' => $requestDate,
        ];
        
        $pdf = Pdf::loadView('vacation.pdf', $data);
        $pdf->setPaper('A4', 'portrait');
        
        $filename = "solicitud_vacaciones_{$employee->lastName}_{$employee->name}_" . Carbon::parse($leave->start)->format('Y-m-d') . ".pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Calendario visual de vacaciones
     */
    public function calendar(Request $request)
    {
        $year = $request->input('year', now()->year);
        
        // Obtener todas las vacaciones del año con relaciones para detectar superposiciones
        $vacations = Leave::where('type', 'vacaciones')
            ->whereIn('status', ['aprobado', 'pendiente'])
            ->whereYear('start', $year)
            ->with(['employee.jobs.category'])
            ->get();
        
        $employees = Employee::where('status', 'active')
            ->with('jobs.category')
            ->orderBy('lastName')
            ->get();
        
        return view('vacation.calendar', compact('vacations', 'employees', 'year'));
    }

    /**
     * Detectar superposiciones entre solicitudes
     * Solo considera superposición si tienen misma categoría o puesto
     */
    private function detectOverlaps($pending, $approved)
    {
        $overlaps = [];
        
        foreach ($pending as $request) {
            $requestStart = Carbon::parse($request->start);
            $requestEnd = Carbon::parse($request->end);
            
            // Obtener categorías y puestos del solicitante
            $requestJobs = $request->employee->jobs ?? collect();
            $requestCategories = $requestJobs->pluck('category_id')->filter()->toArray();
            $requestJobIds = $requestJobs->pluck('id')->toArray();
            
            // Verificar superposición con vacaciones aprobadas
            foreach ($approved as $vacation) {
                $vacationStart = Carbon::parse($vacation->start);
                $vacationEnd = Carbon::parse($vacation->end);
                
                // Verificar si hay superposición de fechas
                if ($requestStart <= $vacationEnd && $requestEnd >= $vacationStart) {
                    // Obtener categorías y puestos de la vacación aprobada
                    $vacationJobs = $vacation->employee->jobs ?? collect();
                    $vacationCategories = $vacationJobs->pluck('category_id')->filter()->toArray();
                    $vacationJobIds = $vacationJobs->pluck('id')->toArray();
                    
                    // Verificar si comparten categoría o puesto
                    $sameCategory = !empty(array_intersect($requestCategories, $vacationCategories));
                    $sameJob = !empty(array_intersect($requestJobIds, $vacationJobIds));
                    
                    // Solo agregar si hay conflicto real (misma categoría o puesto)
                    if ($sameCategory || $sameJob) {
                        $overlaps[] = [
                            'request' => $request,
                            'conflict_with' => $vacation,
                            'same_category' => $sameCategory,
                            'same_job' => $sameJob,
                            'overlap_days' => min($requestEnd, $vacationEnd)->diffInDays(max($requestStart, $vacationStart)) + 1,
                        ];
                    }
                }
            }
            
            // Verificar superposición con otras solicitudes pendientes
            foreach ($pending as $otherRequest) {
                if ($request->id >= $otherRequest->id) continue; // Evitar duplicados
                
                $otherStart = Carbon::parse($otherRequest->start);
                $otherEnd = Carbon::parse($otherRequest->end);
                
                if ($requestStart <= $otherEnd && $requestEnd >= $otherStart) {
                    // Obtener categorías y puestos del otro solicitante
                    $otherJobs = $otherRequest->employee->jobs ?? collect();
                    $otherCategories = $otherJobs->pluck('category_id')->filter()->toArray();
                    $otherJobIds = $otherJobs->pluck('id')->toArray();
                    
                    // Verificar si comparten categoría o puesto
                    $sameCategory = !empty(array_intersect($requestCategories, $otherCategories));
                    $sameJob = !empty(array_intersect($requestJobIds, $otherJobIds));
                    
                    // Solo agregar si hay conflicto real
                    if ($sameCategory || $sameJob) {
                        $overlaps[] = [
                            'request' => $request,
                            'conflict_with' => $otherRequest,
                            'same_category' => $sameCategory,
                            'same_job' => $sameJob,
                            'overlap_days' => min($requestEnd, $otherEnd)->diffInDays(max($requestStart, $otherStart)) + 1,
                            'both_pending' => true,
                        ];
                    }
                }
            }
        }
        
        return $overlaps;
    }

    /**
     * Generar datos para el calendario mensual
     */
    private function generateCalendarData($year, $month, $vacations)
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
        
        $calendar = [];
        
        for ($day = $startOfMonth->copy(); $day <= $endOfMonth; $day->addDay()) {
            $dayData = [
                'date' => $day->copy(),
                'vacations' => [],
            ];
            
            foreach ($vacations as $vacation) {
                $start = Carbon::parse($vacation->start);
                $end = Carbon::parse($vacation->end);
                
                if ($day >= $start && $day <= $end) {
                    $dayData['vacations'][] = $vacation;
                }
            }
            
            $calendar[] = $dayData;
        }
        
        return $calendar;
    }

    /**
     * API: Calcular días hábiles entre dos fechas
     */
    public function calculateWorkingDays(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);
        
        $detail = WorkingDaysService::getDaysDetail($start, $end);
        
        return response()->json([
            'success' => true,
            'data' => $detail,
        ]);
    }

    /**
     * Vista de gestión de feriados
     */
    public function holidays(Request $request)
    {
        $year = $request->input('year', now()->year);
        
        $holidays = \App\Models\Holiday::whereYear('date', $year)
            ->orderBy('date')
            ->get();
        
        return view('vacation.holidays', compact('holidays', 'year'));
    }
}

