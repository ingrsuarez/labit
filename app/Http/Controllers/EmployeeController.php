<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Category;
use App\Models\Job;
use App\Models\User;

class EmployeeController extends Controller
{

    public function new()
    {
        $employees = Employee::all();
        $jobs = Job::all();
        $categories = Category::all();
        $users = User::all();
        return view('employee.new',compact('employees','jobs','categories','users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => ['required','string','max:255'],
            'lastName'            => ['required','string','max:255'],
            'employeeId'          => ['required','string','max:255'],
            'user_id'             => ['nullable','integer','exists:users,id'],
            'email'               => ['nullable','email','max:255'],
            'start_date'          => ['nullable','date'],
            'vacation_days'       => ['nullable','integer'],
            'bank_account'        => ['nullable','string','max:255'],
            'position'            => ['nullable','string','max:255'],
            'health_registration' => ['nullable','string','max:255'],
            'sex'                 => ['required','string','max:255'],
            'weekly_hours'        => ['nullable','integer'],
            'birth'               => ['nullable','date'],
            'phone'               => ['nullable','string','max:255'],
            'address'             => ['nullable','string','max:255'],
            'city'                => ['nullable','string','max:255'],
            'state'               => ['nullable','string','max:255'],
            'country'             => ['nullable','string','max:255'],
            'status'              => ['nullable','string','max:255'],
            'job_id'              => ['nullable','integer','exists:jobs,id'],
        ]);

        // crear empleado directamente
        $employee = Employee::create($data);

        // asociar job sólo si hay valor
        if (!empty($data['job_id'])) {
            $employee->jobs()->syncWithoutDetaching([
                (int)$data['job_id'] => ['user_id' => auth()->id() ?? 1],
            ]);
        }

        return redirect()
            ->route('employee.show', $employee)
            ->with('success', 'Empleado creado correctamente');
    }

    public function edit(Employee $employee)
    {
        
        
        if (!$employee) {
            // Manejo del error: empleado no encontrado
            return redirect()->back()->withErrors(['Empleado no encontrado']);
        }
        $jobs = Job::all();
        $categories = Category::all();
        
        $employee_jobs = $employee->jobs;
        $users = User::all();
        return view('employee.edit',compact('employee','jobs','employee_jobs','categories','users'));
    }

    public function save(Request $request)
    {
        
         $employee = Employee::find($request->id);

        if (!$employee) {
            return redirect()->back()->withErrors(['Empleado no encontrado']);
        }



        $data = $request->validate([
            'name'                => ['required','string','max:255'],
            'lastName'            => ['required','string','max:255'],
            'employeeId'          => ['required','string','max:255'],
            'user_id'             => ['nullable','integer','exists:users,id'],
            'email'               => ['nullable','email','max:255'],
            'start_date'          => ['nullable','date'],
            'vacation_days'       => ['nullable','integer'],
            'bank_account'        => ['nullable','string','max:255'],
            'position'            => ['nullable','string','max:255'],
            'health_registration' => ['nullable','string','max:255'],
            'sex'                 => ['required','string','max:255'],
            'weekly_hours'        => ['nullable','integer'],
            'birth'               => ['nullable','date'],
            'phone'               => ['nullable','string','max:255'],
            'address'             => ['nullable','string','max:255'],
            'city'                => ['nullable','string','max:255'],
            'state'               => ['nullable','string','max:255'],
            'country'             => ['nullable','string','max:255'],
            'status'              => ['nullable','string','max:255'],
            'job_id'              => ['nullable','integer','exists:jobs,id'],
        ]);

        $employee->update($data);

        $jobIds = $request->input('job_ids', []); // array directamente
        $syncData = [];

        foreach ($jobIds as $jobId) {
            $syncData[$jobId] = ['user_id' => auth()->id() ?? 1];
        }

        $employee->jobs()->sync($syncData);
        if (!empty($data['job_id'])) {
            $employee->jobs()->syncWithoutDetaching([
                (int)$data['job_id'] => ['user_id' => auth()->id() ?? 1],
            ]);
        }

        return redirect()
            ->route('employee.show', $employee)
            ->with('success', 'Empleado actualizado correctamente');

    }

    /**
     * Mostrar perfil/resumen completo de un empleado
     */
    public function profile(Employee $employee)
    {
        $employee->load(['jobs.category', 'leaves', 'salaryItems']);
        
        // Calcular antigüedad
        $startDate = $employee->start_date ? \Carbon\Carbon::parse($employee->start_date) : null;
        $antiguedad = $startDate ? $startDate->diffInYears(now()) : 0;
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
        
        // Conceptos de sueldo asignados
        $assignedConcepts = $employee->salaryItems()->wherePivot('is_active', true)->get();
        
        // Últimas licencias
        $recentLeaves = $employee->leaves()
            ->orderByDesc('start')
            ->limit(5)
            ->get();
        
        return view('employee.profile', compact(
            'employee', 
            'antiguedad',
            'antiguedadMeses',
            'category',
            'leavesSummary',
            'assignedConcepts',
            'recentLeaves',
            'currentYear'
        ));
    }

    public function show(Request $request)
    {
        // Filtros
        $q       = trim($request->input('q', ''));
        $status  = $request->input('status', '');
        $jobId   = $request->input('job_id', '');

        // Query base con relaciones
        $employees = Employee::query()
            ->with(['jobs:id,name,department']) // para mostrar puestos y departamento
            ->when($q !== '', function($qBuilder) use ($q) {
                $qBuilder->where(function($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                    ->orWhere('lastName', 'like', "%{$q}%")
                    ->orWhere('employeeId', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($status !== '', fn($qb) => $qb->where('status', $status))
            ->when($jobId !== '', function($qb) use ($jobId) {
                $qb->whereHas('jobs', fn($j) => $j->where('jobs.id', $jobId));
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // Resúmenes
        $total        = Employee::count();
        $activos      = Employee::where('status', 'active')->count();
        $inactivos    = Employee::where('status', 'inactive')->count();
        $promHoras    = round((float) Employee::avg('weekly_hours'), 1);

        // Top 5 puestos por cantidad de empleados
        $topJobs = DB::table('job_employee')
            ->join('jobs', 'jobs.id', '=', 'job_employee.job_id')
            ->select('jobs.id', 'jobs.name', DB::raw('COUNT(job_employee.employee_id) as total'))
            ->groupBy('jobs.id', 'jobs.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Para el select de puestos en filtros
        $jobs = Job::orderBy('name')->get(['id','name']);

        return view('employee.index', [
            'employees' => $employees,
            'summary'   => [
                'total'     => $total,
                'activos'   => $activos,
                'inactivos' => $inactivos,
                'promHoras' => $promHoras,
                'topJobs'   => $topJobs,
            ],
            'filters'   => [
                'q'      => $q,
                'status' => $status,
                'job_id' => $jobId,
            ],
            'jobs'      => $jobs,
        ]);

    }
}
