<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Employee;
use App\Models\Job;

class DashboardController extends Controller
{
    public function index(Request $request)
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
