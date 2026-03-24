<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Job;
use App\Models\User;

class ManageController extends Controller
{
    //
    public function index(Request $request)
    {
        $q = trim($request->input('q', ''));
        $status = $request->input('status', '');
        $jobId = $request->input('job_id', '');
        $companyFilter = $request->input('company_id', '');

        $employees = Employee::query()
            ->with(['jobs:id,name,department', 'company:id,name'])
            ->when($q !== '', function ($qBuilder) use ($q) {
                $qBuilder->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('lastName', 'like', "%{$q}%")
                        ->orWhere('employeeId', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($status !== '', fn ($qb) => $qb->where('status', $status))
            ->when($jobId !== '', function ($qb) use ($jobId) {
                $qb->whereHas('jobs', fn ($j) => $j->where('jobs.id', $jobId));
            })
            ->when($companyFilter !== '', function ($qb) use ($companyFilter) {
                if ($companyFilter === 'none') {
                    $qb->whereNull('company_id');
                } else {
                    $qb->where('company_id', $companyFilter);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $total = Employee::count();
        $activos = Employee::where('status', 'active')->count();
        $inactivos = Employee::where('status', 'inactive')->count();
        $sinEmpresa = Employee::whereNull('company_id')->count();
        $promHoras = round((float) Employee::avg('weekly_hours'), 1);

        $topJobs = DB::table('job_employee')
            ->join('jobs', 'jobs.id', '=', 'job_employee.job_id')
            ->select('jobs.id', 'jobs.name', DB::raw('COUNT(job_employee.employee_id) as total'))
            ->groupBy('jobs.id', 'jobs.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $jobs = Job::orderBy('name')->get(['id', 'name']);
        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('employee.index', [
            'employees' => $employees,
            'summary' => [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $inactivos,
                'sinEmpresa' => $sinEmpresa,
                'promHoras' => $promHoras,
                'topJobs' => $topJobs,
            ],
            'filters' => [
                'q' => $q,
                'status' => $status,
                'job_id' => $jobId,
                'company_id' => $companyFilter,
            ],
            'jobs' => $jobs,
            'companies' => $companies,
        ]);
    }

    public function chart(Request $request)
    {
        $q       = trim($request->input('q', ''));
        $status  = $request->input('status', '');
        $jobId   = $request->input('job_id', '');
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

        return view('manage.index',compact('employees'));
    }
}
