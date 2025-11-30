<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\Leave;
use App\Models\Job;
use App\Models\Employee;
use App\Exports\LeavesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class LeaveController extends Controller
{
    public function new()
    {
        $leaves = Leave::orderBy('start','desc')->get();
        $employees = Employee::all();

        return view('leave.new',compact('leaves','employees'));
    }

    public function index(Request $request)
    {
        $leaves = Leave::with('employee')
            ->orderByRaw("YEAR(start) DESC, MONTH(start) DESC")
            ->orderBy('employee_id')
            ->orderBy('start')
            ->get();

        return view('leave.index', compact('leaves'));
    }

    public function resume(Request $request)
    {
        $year       = $request->input('year');
        $month      = $request->input('month');
        $employeeId = $request->input('employee_id');

        $query = \App\Models\Leave::query()
            ->join('employees', 'leaves.employee_id', '=', 'employees.id')
            ->selectRaw("
                MIN(leaves.id) AS id,
                YEAR(leaves.start)  AS year,
                MONTH(leaves.start) AS month,
                employees.id        AS employee_id,

                CONCAT(MIN(employees.lastName), ' ', MIN(employees.name)) AS employee,
                MIN(employees.employeeId) AS cuil,              -- si tenés employees.cuil, cambialo aquí

                MIN(employees.weekly_hours) AS weekly_hours,    -- NUEVO
                MIN(employees.position)     AS category,        -- NUEVO

                leaves.type         AS type,
                COUNT(*)            AS cantidad,
                SUM(COALESCE(leaves.days, DATEDIFF(leaves.end, leaves.start) + 1)) AS total_dias,
                SUM(COALESCE(leaves.hour_50, 0))  AS horas_50,
                SUM(COALESCE(leaves.hour_100, 0)) AS horas_100,
                GROUP_CONCAT(COALESCE(leaves.file,'' ) SEPARATOR '|') AS files
            ")
            ->when($year,  fn($q) => $q->whereYear('leaves.start', $year))
            ->when($month, fn($q) => $q->whereMonth('leaves.start', $month))
            ->when($employeeId, fn($q) => $q->where('employees.id', $employeeId))
            ->groupByRaw('YEAR(leaves.start), MONTH(leaves.start), employees.id, leaves.type')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('employee');

        $resumes = $query->get();

        // Recalcular días hábiles para vacaciones
        foreach ($resumes as $resume) {
            if ($resume->type === 'vacaciones') {
                // Obtener las licencias individuales para calcular días hábiles
                $vacaciones = Leave::where('employee_id', $resume->employee_id)
                    ->where('type', 'vacaciones')
                    ->whereYear('start', $resume->year)
                    ->whereMonth('start', $resume->month)
                    ->get();
                
                $resume->total_dias = $vacaciones->sum(fn($l) => $l->working_days);
            }
        }

        $employees = \App\Models\Employee::orderBy('lastName')
            ->get(['id','name','lastName','employeeId']); // agrega 'cuil' si existe

        return view('leave.resume', [
            'resumes'   => $resumes,
            'employees' => $employees,
            'filters'   => ['year' => $year, 'month' => $month, 'employee_id' => $employeeId],
        ]);
    }

    public function resumeCompact(Request $request)
    {
        // Mes ancla = comienzo del mes actual
        // 1) Tomar anchor del query ?anchor=YYYY-MM (o fallback a ahora)
        $anchorYm = $request->query('anchor'); // ej. 2025-08
        if ($anchorYm && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $anchorYm)) {
            $anchor = Carbon::createFromFormat('Y-m', $anchorYm)->startOfMonth();
        } elseif ($request->filled('year') && $request->filled('month')) {
            $anchor = Carbon::createFromDate((int)$request->year, (int)$request->month, 1)->startOfMonth();
        } else {
            $anchor = Carbon::now()->startOfMonth();
        }
        // Array de los 4 meses (0 = actual, 1 = -1, etc.)
        $months = collect(range(0, 3))->map(fn($i) => $anchor->copy()->subMonths($i));
        $rangeStart = $months->last()->copy()->startOfMonth(); // inicio del más antiguo
        $rangeEnd   = $anchor->copy()->endOfMonth();           // fin del actual

        // Agregados por empleado y mes (solo en la ventana de 4 meses)
        $leavesAgg = Leave::query()
            ->selectRaw("
                leaves.employee_id,
                YEAR(leaves.start) AS y,
                MONTH(leaves.start) AS m,
                SUM(CASE WHEN leaves.type='vacaciones' THEN COALESCE(leaves.days, DATEDIFF(leaves.end, leaves.start)+1) ELSE 0 END) AS vac,
                SUM(CASE WHEN leaves.type='enfermedad' THEN COALESCE(leaves.days, DATEDIFF(leaves.end, leaves.start)+1) ELSE 0 END) AS enf,
                SUM(CASE WHEN leaves.type='embarazo' THEN COALESCE(leaves.days, DATEDIFF(leaves.end, leaves.start)+1) ELSE 0 END) AS emb,
                SUM(COALESCE(leaves.hour_50, 0))  AS h50,
                SUM(COALESCE(leaves.hour_100, 0)) AS h100,
                GROUP_CONCAT(NULLIF(leaves.file,'' ) SEPARATOR '|') AS files
            ")
            ->whereBetween('leaves.start', [$rangeStart, $rangeEnd])
            ->groupBy('leaves.employee_id', 'y', 'm')
            ->get();

        // Mapear por empleado y por mes (YYYY-MM) para acceso fácil en la vista
        $byEmpMonth = [];
        foreach ($leavesAgg as $row) {
            $ym = sprintf('%04d-%02d', $row->y, $row->m);
            
            // Recalcular días hábiles para vacaciones
            $vacDays = 0;
            if ($row->vac > 0) {
                $vacaciones = Leave::where('employee_id', $row->employee_id)
                    ->where('type', 'vacaciones')
                    ->whereYear('start', $row->y)
                    ->whereMonth('start', $row->m)
                    ->get();
                $vacDays = $vacaciones->sum(fn($l) => $l->working_days);
            }
            
            $byEmpMonth[$row->employee_id][$ym] = [
                'vac'  => $vacDays,
                'enf'  => (int) $row->enf,
                'emb'  => (int) $row->emb,
                'h50'  => (int) $row->h50,
                'h100' => (int) $row->h100,
                'files'=> collect(explode('|', (string)$row->files))->filter()->values(),
            ];
        }

        // Traer empleados con sus puestos (para mostrar "puesto" y "categoría").
        // Usamos la relación "jobs" y tomamos el último por fecha del pivot en la vista.
        $employees = Employee::query()
        ->with([
            'jobs' => function ($q) {
                // Trae lo necesario y ordena por fecha del pivot (último puesto primero)
                $q->select('jobs.id','jobs.name','jobs.category_id')
                ->with('category')
                ->orderByDesc('job_employee.created_at');
                }
            ])
            ->orderBy('lastName')
            ->get([
                'id','name','lastName','employeeId',
                'start_date','weekly_hours','position'
        ]);
        
        // Armar metadatos de meses para cabecera de tabla
        $monthsMeta = $months->map(function($c){
            return [
                'key'   => $c->format('Y-m'),
                'label' => $c->translatedFormat('M Y'), // ej. "ago 2025"
            ];
        })->reverse()->values(); // del más antiguo al más nuevo

        return view('leave.resume_compact', [
            'employees'   => $employees,
            'monthsMeta'  => $monthsMeta,
            'byEmpMonth'  => $byEmpMonth,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'description' => ['required','string','max:255'],
            'start'       => ['required','date'],
            'end'         => ['required','date','after_or_equal:start'],
            'employee'    => ['required','integer','exists:employees,id'],
            'type'        => ['required','in:enfermedad,vacaciones,embarazo,capacitacion,horas extra'],
            'hour_50'     => ['nullable','integer','min:0'],
            'hour_100'    => ['nullable','integer','min:0'],
            'doctor'      => ['nullable','string','max:255'],
            'file'        => ['nullable','image','max:5120'], // 5MB
        ]);

        $leave = new Leave;
        $leave->description = strtolower($validated['description']);
        $leave->start       = $validated['start'];
        $leave->end         = $validated['end'];
        $leave->employee_id = $validated['employee'];
        $leave->type        = $validated['type'];
        $leave->user_id     = $user->id;
        $leave->doctor      = $validated['doctor'] ?? null;
        $leave->hour_50     = $validated['hour_50'] ?? null;
        $leave->hour_100    = $validated['hour_100'] ?? null;
        $leave->status      = 'revision';

        // Guardar certificado (imagen)
        if ($request->hasFile('file')) {
            // requiere: php artisan storage:link
            $path = $request->file('file')->store('leaves', 'public');
            $leave->file = $path; // ej: leaves/abc123.jpg
        }

        $leave->save();

        return back()->with('success','Licencia cargada correctamente');
    
    }

    public function edit(Leave $leave)
    {
        $employees = Employee::orderBy('lastName')->get(['id', 'name', 'lastName', 'employeeId']);
        return view('leave.edit',compact('leave', 'employees'));
    }

    public function update(Request $request, Leave $leave)
    {

        $validated = $request->validate([
            'employee'    => ['required', 'exists:employees,id'],
            'type'        => ['required', 'string', 'max:255'],
            'doctor'      => ['nullable', 'string', 'max:255'],
            'start'       => ['required', 'date'],
            'end'         => ['required', 'date', 'after_or_equal:start'],
            'hour_50'     => ['nullable', 'integer', 'min:0'],
            'hour_100'    => ['nullable', 'integer', 'min:0'],
            'description' => ['required', 'string', 'max:255'],
            'file'        => ['nullable', 'image', 'max:5120'], // 5MB
        ]);

        // Actualiza los campos básicos
        $leave->employee_id = $validated['employee'];
        $leave->type        = $validated['type'];
        $leave->doctor      = $validated['doctor'] ?? null;
        $leave->status      = 'pendiente';
        $leave->start       = $validated['start'];
        $leave->end         = $validated['end'];
        $leave->hour_50     = $validated['hour_50'] ?? 0;
        $leave->hour_100    = $validated['hour_100'] ?? 0;
        $leave->description = $validated['description'];
        $leave->user_id = auth()->id();

        // Si se sube un nuevo archivo, lo reemplaza
        if ($request->hasFile('file')) {
            // Opcional: eliminar el archivo anterior
            if ($leave->file && \Storage::disk('public')->exists($leave->file)) {
                \Storage::disk('public')->delete($leave->file);
            }

            $path = $request->file('file')->store('leaves', 'public');
            $leave->file = $path;
        }

        $leave->save();

        return redirect()->route('leave.index')
            ->with('success', 'Licencia actualizada correctamente.');
    }

    // public function update(Request $request)
    // {
    //     $user = Auth::user();

    //     $leave = Leave::where('id',$request->leave_id)->first();

    //     $leave->description = strtolower($request->description);
    //     $leave->start = $request->start;
    //     $leave->end = $request->end;
    //     $leave->employee_id =$request->employee_id;
    //     $leave->type = $request->type;
    //     $leave->user_id = $user->id;
    //     $leave->doctor = $request->doctor;
    //     $leave->hour_50 = $request->hour_50;
    //     $leave->hour_100 = $request->hour_100;
        
        

    //     try {
    //         $leave->save();
    //         return redirect()->back();
    //     }
    //     catch(Exception $e) {
    //         echo 'Error: ',  $e->getMessage(), "\n";

    //     }
    // }

    public function delete(Leave $leave)
    {
        $leave->delete();
        return redirect()->back();
    }

    /**
     * Exportar novedades a Excel
     */
    public function exportExcel(Request $request)
    {
        $year       = $request->input('year');
        $month      = $request->input('month');
        $employeeId = $request->input('employee_id');

        // Obtener novedades del período y recalcular días hábiles para vacaciones
        $resumes = $this->getResumesQuery($year, $month, $employeeId)->get();
        $resumes = $this->recalculateVacationDays($resumes);

        // Obtener todos los empleados (o uno específico si se filtró)
        $employeesQuery = Employee::query()->orderBy('lastName')->orderBy('name');
        if ($employeeId) {
            $employeesQuery->where('id', $employeeId);
        }
        $allEmployees = $employeesQuery->get();

        // Combinar empleados con sus novedades (o valores en 0 si no tienen)
        $exportData = $this->combineEmployeesWithLeaves($allEmployees, $resumes, $year, $month);

        $filename = 'novedades_liquidacion';
        if ($year && $month) {
            $monthName = str_pad($month, 2, '0', STR_PAD_LEFT);
            $filename .= "_{$year}-{$monthName}";
        } elseif ($year) {
            $filename .= "_{$year}";
        }
        $filename .= '.xlsx';

        return Excel::download(new LeavesExport($exportData, $year, $month), $filename);
    }

    /**
     * Combinar todos los empleados con sus novedades
     */
    private function combineEmployeesWithLeaves($employees, $resumes, $year, $month)
    {
        $result = collect();

        // Agrupar novedades por empleado
        $resumesByEmployee = $resumes->groupBy('employee_id');

        foreach ($employees as $employee) {
            $employeeResumes = $resumesByEmployee->get($employee->id, collect());

            if ($employeeResumes->isEmpty()) {
                // Empleado sin novedades - agregar con valores en 0
                $result->push((object)[
                    'year' => $year ?? now()->year,
                    'month' => $month ?? now()->month,
                    'employee_id' => $employee->id,
                    'employee' => trim($employee->lastName . ' ' . $employee->name),
                    'cuil' => $employee->employeeId,
                    'weekly_hours' => $employee->weekly_hours ?? 0,
                    'category' => $employee->position ?? '-',
                    'type' => '-',
                    'cantidad' => 0,
                    'total_dias' => 0,
                    'horas_50' => 0,
                    'horas_100' => 0,
                    'dias_vacaciones' => 0,
                    'dias_enfermedad' => 0,
                    'dias_embarazo' => 0,
                ]);
            } else {
                // Empleado con novedades - agregar cada tipo
                foreach ($employeeResumes as $resume) {
                    $result->push($resume);
                }
            }
        }

        return $result;
    }

    /**
     * Exportar novedades a PDF
     */
    public function exportPdf(Request $request)
    {
        $year       = $request->input('year');
        $month      = $request->input('month');
        $employeeId = $request->input('employee_id');

        // Recalcular días hábiles para vacaciones
        $resumes = $this->getResumesQuery($year, $month, $employeeId)->get();
        $resumes = $this->recalculateVacationDays($resumes);

        $filename = 'novedades_liquidacion';
        if ($year && $month) {
            $monthName = str_pad($month, 2, '0', STR_PAD_LEFT);
            $filename .= "_{$year}-{$monthName}";
        } elseif ($year) {
            $filename .= "_{$year}";
        }
        $filename .= '.pdf';

        $pdf = Pdf::loadView('leave.resume_pdf', [
            'resumes' => $resumes,
            'filters' => ['year' => $year, 'month' => $month, 'employee_id' => $employeeId],
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download($filename);
    }

    /**
     * Query reutilizable para obtener resúmenes de novedades
     */
    private function getResumesQuery($year = null, $month = null, $employeeId = null)
    {
        return \App\Models\Leave::query()
            ->join('employees', 'leaves.employee_id', '=', 'employees.id')
            ->selectRaw("
                MIN(leaves.id) AS id,
                YEAR(leaves.start)  AS year,
                MONTH(leaves.start) AS month,
                employees.id        AS employee_id,
                CONCAT(MIN(employees.lastName), ' ', MIN(employees.name)) AS employee,
                MIN(employees.employeeId) AS cuil,
                MIN(employees.weekly_hours) AS weekly_hours,
                MIN(employees.position)     AS category,
                leaves.type         AS type,
                COUNT(*)            AS cantidad,
                SUM(COALESCE(leaves.days, DATEDIFF(leaves.end, leaves.start) + 1)) AS total_dias,
                SUM(COALESCE(leaves.hour_50, 0))  AS horas_50,
                SUM(COALESCE(leaves.hour_100, 0)) AS horas_100,
                GROUP_CONCAT(COALESCE(leaves.file,'' ) SEPARATOR '|') AS files
            ")
            ->when($year,  fn($q) => $q->whereYear('leaves.start', $year))
            ->when($month, fn($q) => $q->whereMonth('leaves.start', $month))
            ->when($employeeId, fn($q) => $q->where('employees.id', $employeeId))
            ->groupByRaw('YEAR(leaves.start), MONTH(leaves.start), employees.id, leaves.type')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('employee');
    }

    /**
     * Recalcular días hábiles para vacaciones en una colección de resúmenes
     */
    private function recalculateVacationDays($resumes)
    {
        foreach ($resumes as $resume) {
            if ($resume->type === 'vacaciones') {
                $vacaciones = Leave::where('employee_id', $resume->employee_id)
                    ->where('type', 'vacaciones')
                    ->whereYear('start', $resume->year)
                    ->whereMonth('start', $resume->month)
                    ->get();
                
                $resume->total_dias = $vacaciones->sum(fn($l) => $l->working_days);
            }
        }
        return $resumes;
    }
}
