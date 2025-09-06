<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\Leave;
use App\Models\Job;
use App\Models\Employee;

class LeaveController extends Controller
{
    public function new()
    {
        $leaves = Leave::orderBy('start','desc')->get();
        $employees = Employee::all();

        return view('leave.new',compact('leaves','employees'));
    }

    public function resume(Request $request)
    {
        $year       = $request->input('year');
        $month      = $request->input('month');
        $employeeId = $request->input('employee_id');

        $query = \App\Models\Leave::query()
            ->join('employees', 'leaves.employee_id', '=', 'employees.id')
            ->selectRaw("
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
            $byEmpMonth[$row->employee_id][$ym] = [
                'vac'  => (int) $row->vac,
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

        return view('leave.edit',compact('leave'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $leave = Leave::where('id',$request->leave_id)->first();

        $leave->description = strtolower($request->description);
        $leave->start = $request->start;
        $leave->end = $request->end;
        $leave->employee_id =$request->employee_id;
        $leave->type = $request->type;
        $leave->user_id = $user->id;
        $leave->doctor = $request->doctor;
        $leave->hour_50 = $request->hour_50;
        $leave->hour_100 = $request->hour_100;
        
        

        try {
            $leave->save();
            return redirect()->back();
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }
    }

    public function delete(Leave $leave)
    {
        $leave->delete();
        return redirect()->back();
    }
}
