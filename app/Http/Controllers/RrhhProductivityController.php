<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Job;
use App\Models\LabBranch;
use App\Services\EmployeeProductivityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RrhhProductivityController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('rrhh.productivity.view');

        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : now();

        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $jobId = $request->filled('job_id') ? (int) $request->job_id : null;
        $employeeId = $request->filled('employee_id') ? (int) $request->employee_id : null;

        $report = app(EmployeeProductivityService::class)->report($date, $branchId, $jobId, $employeeId);

        $branches = LabBranch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $jobs = Job::query()->whereHas('employees')->orderBy('name')->get(['id', 'name']);
        $employees = Employee::query()
            ->whereNotNull('user_id')
            ->orderBy('name')
            ->orderBy('lastName')
            ->get(['id', 'name', 'lastName']);

        return view('rrhh.productividad', [
            'date' => $date->toDateString(),
            'branchId' => $branchId,
            'jobId' => $jobId,
            'employeeId' => $employeeId,
            'report' => $report,
            'branches' => $branches,
            'jobs' => $jobs,
            'employees' => $employees,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('rrhh.productivity.view');

        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : now();

        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $jobId = $request->filled('job_id') ? (int) $request->job_id : null;
        $employeeId = $request->filled('employee_id') ? (int) $request->employee_id : null;

        $report = app(EmployeeProductivityService::class)->report($date, $branchId, $jobId, $employeeId);
        $filename = 'productividad-'.$date->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Empleado', 'Puesto', 'Sede', 'Roles',
                'Prot. creados', 'Pac. nuevos', 'Editados', 'Cobros', 'Entregados', '% entregados',
                'Det. cargadas', 'Prot. con carga', '% carga',
                'Val. prácticas', 'Val. protocolos', '% validados', 'Desvalid.', 'Emails',
            ], ';');

            foreach ($report['rows'] as $row) {
                $reception = $row['metrics']['reception'] ?? [];
                $technician = $row['metrics']['technician'] ?? [];
                $biochemist = $row['metrics']['biochemist'] ?? [];

                fputcsv($out, [
                    $row['employee_name'],
                    $row['job_name'],
                    $row['inferred_branch_name'],
                    implode(', ', $row['roles']),
                    $reception['protocols_created'] ?? '',
                    $reception['patients_created'] ?? '',
                    $reception['protocols_updated'] ?? '',
                    $reception['payments_recorded'] ?? '',
                    $reception['results_delivered'] ?? '',
                    isset($reception['delivery_rate']) ? $reception['delivery_rate'].'%' : '',
                    $technician['results_entered'] ?? '',
                    $technician['protocols_with_results'] ?? '',
                    isset($technician['load_rate']) ? $technician['load_rate'].'%' : '',
                    $biochemist['tests_validated'] ?? '',
                    $biochemist['protocols_validated'] ?? '',
                    isset($biochemist['validation_rate']) ? $biochemist['validation_rate'].'%' : '',
                    $biochemist['unvalidations'] ?? '',
                    $biochemist['emails_sent'] ?? '',
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
