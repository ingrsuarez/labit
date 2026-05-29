<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LabBranch;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\User;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class EmployeeProductivityService
{
    private const PROTOCOL_TYPES = [
        Admission::class,
        VetAdmission::class,
        Sample::class,
    ];

    public function report(Carbon $date, ?int $labBranchId = null, ?int $jobId = null, ?int $employeeId = null): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $protocolsCreated = $this->countProtocolsCreated($start, $end, $labBranchId);
        $patientsCreated = $this->countPatientsCreated($start, $end);

        $userIds = $this->collectActiveUserIds($start, $end, $labBranchId);

        $employeesQuery = Employee::query()
            ->with(['user.roles', 'jobs'])
            ->whereNotNull('user_id')
            ->whereIn('user_id', $userIds);

        if ($employeeId) {
            $employeesQuery->where('id', $employeeId);
        }

        if ($jobId) {
            $employeesQuery->whereHas('jobs', fn (Builder $q) => $q->where('jobs.id', $jobId));
        }

        $rows = [];
        $totalDelivered = 0;
        $totalValidatedProtocols = 0;

        foreach ($employeesQuery->get() as $employee) {
            $user = $employee->user;
            if (! $user) {
                continue;
            }

            $roles = $user->getRoleNames()->intersect(['recepcion-lab', 'tecnico-lab', 'bioquimico'])->values()->all();
            if ($roles === []) {
                continue;
            }

            $metrics = [];
            if (in_array('recepcion-lab', $roles, true)) {
                $metrics['reception'] = $this->receptionMetrics($user->id, $start, $end, $labBranchId, $protocolsCreated);
                $totalDelivered += $metrics['reception']['results_delivered'];
            }
            if (in_array('tecnico-lab', $roles, true)) {
                $metrics['technician'] = $this->technicianMetrics($user->id, $start, $end, $labBranchId, $protocolsCreated);
            }
            if (in_array('bioquimico', $roles, true)) {
                $metrics['biochemist'] = $this->biochemistMetrics($user->id, $start, $end, $labBranchId, $protocolsCreated);
                $totalValidatedProtocols += $metrics['biochemist']['protocols_validated'];
            }

            $rows[] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'job_name' => $this->primaryJobName($employee, $jobId),
                'inferred_branch_name' => $this->inferBranchName($user->id, $start, $end, $labBranchId),
                'roles' => $roles,
                'metrics' => $metrics,
            ];
        }

        usort($rows, fn ($a, $b) => strcmp($a['employee_name'], $b['employee_name']));

        return [
            'branch_summary' => [
                'protocols_created' => $protocolsCreated,
                'patients_created' => $patientsCreated,
                'results_delivered' => $totalDelivered,
                'protocols_validated' => $totalValidatedProtocols,
            ],
            'rows' => $rows,
        ];
    }

    private function countProtocolsCreated(Carbon $start, Carbon $end, ?int $labBranchId): int
    {
        $total = 0;
        foreach (self::PROTOCOL_TYPES as $model) {
            $q = $model::query()->whereBetween('created_at', [$start, $end]);
            if ($labBranchId) {
                $q->where('lab_branch_id', $labBranchId);
            }
            $total += $q->count();
        }

        return $total;
    }

    private function countPatientsCreated(Carbon $start, Carbon $end): int
    {
        return (int) AuditLog::query()
            ->where('action', 'created')
            ->where('auditable_type', Patient::class)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('count(distinct auditable_id) as aggregate')
            ->value('aggregate');
    }

    private function collectActiveUserIds(Carbon $start, Carbon $end, ?int $labBranchId): array
    {
        $ids = collect();

        $auditUserIds = $this->auditBaseQuery($start, $end, $labBranchId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $ids = $ids->merge($auditUserIds);

        $ids = $ids->merge($this->admissionTestUserIds($start, $end, $labBranchId));
        $ids = $ids->merge($this->vetTestUserIds($start, $end, $labBranchId));
        $ids = $ids->merge($this->sampleDeterminationUserIds($start, $end, $labBranchId));

        return $ids->unique()->filter()->values()->all();
    }

    private function receptionMetrics(int $userId, Carbon $start, Carbon $end, ?int $labBranchId, int $denominator): array
    {
        $protocolsCreated = $this->countDistinctProtocolAudits($userId, 'created', $start, $end, $labBranchId);
        $resultsDelivered = $this->countAudits($userId, 'result_delivered', $start, $end, $labBranchId);

        return [
            'protocols_created' => $protocolsCreated,
            'patients_created' => $this->countAudits($userId, 'created', $start, $end, null, Patient::class),
            'protocols_updated' => $this->countAudits($userId, 'updated', $start, $end, $labBranchId),
            'payments_recorded' => $this->countAudits($userId, 'payment_recorded', $start, $end, $labBranchId),
            'results_delivered' => $resultsDelivered,
            'delivery_rate' => $this->rate($resultsDelivered, $denominator),
        ];
    }

    private function technicianMetrics(int $userId, Carbon $start, Carbon $end, ?int $labBranchId, int $denominator): array
    {
        $clinicalEntered = AdmissionTest::query()
            ->where('result_entered_by', $userId)
            ->whereBetween('result_entered_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('admission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->count();

        $vetEntered = VetAdmissionTest::query()
            ->where('analyzed_by', $userId)
            ->whereBetween('analyzed_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('vetAdmission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->count();

        $sampleEntered = SampleDetermination::query()
            ->where('analyzed_by', $userId)
            ->whereBetween('analyzed_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('sample', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->count();

        $resultsEntered = $clinicalEntered + $vetEntered + $sampleEntered;

        $protocolsWithResults = $this->countProtocolsWithResultsEntered($userId, $start, $end, $labBranchId);
        $resultsLoaded = $this->countAudits($userId, 'results_loaded', $start, $end, $labBranchId);

        return [
            'results_entered' => $resultsEntered,
            'protocols_with_results' => $protocolsWithResults,
            'results_loaded_events' => $resultsLoaded,
            'tests_removed' => $this->countAudits($userId, 'test_removed', $start, $end, $labBranchId),
            'load_rate' => $this->rate($protocolsWithResults, $denominator),
        ];
    }

    private function biochemistMetrics(int $userId, Carbon $start, Carbon $end, ?int $labBranchId, int $denominator): array
    {
        $testsValidated = $this->countValidatedTests($userId, $start, $end, $labBranchId);
        $protocolsValidated = $this->countProtocolsValidated($userId, $start, $end, $labBranchId);

        return [
            'tests_validated' => $testsValidated,
            'protocols_validated' => $protocolsValidated,
            'unvalidations' => $this->countAudits($userId, 'unvalidated', $start, $end, $labBranchId),
            'emails_sent' => $this->countAudits($userId, 'email_sent', $start, $end, $labBranchId),
            'results_loaded_events' => $this->countAudits($userId, 'results_loaded', $start, $end, $labBranchId),
            'validation_rate' => $this->rate($protocolsValidated, $denominator),
        ];
    }

    private function countValidatedTests(int $userId, Carbon $start, Carbon $end, ?int $labBranchId): int
    {
        $clinical = AdmissionTest::query()
            ->where('validated_by', $userId)
            ->whereBetween('validated_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('admission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->count();

        $vet = VetAdmissionTest::query()
            ->where('validated_by', $userId)
            ->whereBetween('validated_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('vetAdmission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->count();

        $sample = SampleDetermination::query()
            ->where('validated_by', $userId)
            ->whereBetween('validated_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('sample', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->count();

        return $clinical + $vet + $sample;
    }

    private function countProtocolsValidated(int $userId, Carbon $start, Carbon $end, ?int $labBranchId): int
    {
        $count = 0;

        $admissionIds = AdmissionTest::query()
            ->where('validated_by', $userId)
            ->whereBetween('validated_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('admission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->distinct()
            ->pluck('admission_id');
        $count += $admissionIds->count();

        $vetIds = VetAdmissionTest::query()
            ->where('validated_by', $userId)
            ->whereBetween('validated_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('vetAdmission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->distinct()
            ->pluck('vet_admission_id');
        $count += $vetIds->count();

        $sampleIds = SampleDetermination::query()
            ->where('validated_by', $userId)
            ->whereBetween('validated_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('sample', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->distinct()
            ->pluck('sample_id');
        $count += $sampleIds->count();

        return $count;
    }

    private function countProtocolsWithResultsEntered(int $userId, Carbon $start, Carbon $end, ?int $labBranchId): int
    {
        $count = 0;

        $count += AdmissionTest::query()
            ->where('result_entered_by', $userId)
            ->whereBetween('result_entered_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('admission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->distinct('admission_id')
            ->count('admission_id');

        $count += VetAdmissionTest::query()
            ->where('analyzed_by', $userId)
            ->whereBetween('analyzed_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('vetAdmission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->distinct('vet_admission_id')
            ->count('vet_admission_id');

        $count += SampleDetermination::query()
            ->where('analyzed_by', $userId)
            ->whereBetween('analyzed_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('sample', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->distinct('sample_id')
            ->count('sample_id');

        return $count;
    }

    private function countDistinctProtocolAudits(int $userId, string $action, Carbon $start, Carbon $end, ?int $labBranchId): int
    {
        $q = AuditLog::query()
            ->where('user_id', $userId)
            ->where('action', $action)
            ->whereIn('auditable_type', self::PROTOCOL_TYPES)
            ->whereBetween('created_at', [$start, $end]);

        $this->applyBranchFilterToAudits($q, $labBranchId);

        $driver = $q->getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return (int) $q->selectRaw('count(distinct auditable_type || \'-\' || auditable_id) as aggregate')->value('aggregate');
        }

        return (int) $q->selectRaw('count(distinct concat(auditable_type, "-", auditable_id)) as aggregate')->value('aggregate');
    }

    private function countAudits(int $userId, string $action, Carbon $start, Carbon $end, ?int $labBranchId, ?string $auditableType = null): int
    {
        $q = AuditLog::query()
            ->where('user_id', $userId)
            ->where('action', $action)
            ->whereBetween('created_at', [$start, $end]);

        if ($auditableType) {
            $q->where('auditable_type', $auditableType);
        } else {
            $this->applyBranchFilterToAudits($q, $labBranchId);
        }

        return $q->count();
    }

    private function auditBaseQuery(Carbon $start, Carbon $end, ?int $labBranchId): Builder
    {
        $q = AuditLog::query()->whereBetween('created_at', [$start, $end]);
        $this->applyBranchFilterToAudits($q, $labBranchId);

        return $q;
    }

    private function applyBranchFilterToAudits(Builder $q, ?int $labBranchId): void
    {
        if (! $labBranchId) {
            return;
        }

        $q->where(function (Builder $outer) use ($labBranchId) {
            $outer->whereHasMorph('auditable', [Admission::class], fn (Builder $m) => $m->where('lab_branch_id', $labBranchId))
                ->orWhereHasMorph('auditable', [VetAdmission::class], fn (Builder $m) => $m->where('lab_branch_id', $labBranchId))
                ->orWhereHasMorph('auditable', [Sample::class], fn (Builder $m) => $m->where('lab_branch_id', $labBranchId))
                ->orWhere('auditable_type', Patient::class);
        });
    }

    private function admissionTestUserIds(Carbon $start, Carbon $end, ?int $labBranchId): \Illuminate\Support\Collection
    {
        return AdmissionTest::query()
            ->where(function (Builder $q) use ($start, $end) {
                $q->whereBetween('result_entered_at', [$start, $end])
                    ->orWhereBetween('validated_at', [$start, $end]);
            })
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('admission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->get(['result_entered_by', 'validated_by'])
            ->flatMap(fn ($t) => [$t->result_entered_by, $t->validated_by])
            ->filter();
    }

    private function vetTestUserIds(Carbon $start, Carbon $end, ?int $labBranchId): \Illuminate\Support\Collection
    {
        return VetAdmissionTest::query()
            ->where(function (Builder $q) use ($start, $end) {
                $q->whereBetween('analyzed_at', [$start, $end])
                    ->orWhereBetween('validated_at', [$start, $end]);
            })
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('vetAdmission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->get(['analyzed_by', 'validated_by'])
            ->flatMap(fn ($t) => [$t->analyzed_by, $t->validated_by])
            ->filter();
    }

    private function sampleDeterminationUserIds(Carbon $start, Carbon $end, ?int $labBranchId): \Illuminate\Support\Collection
    {
        return SampleDetermination::query()
            ->where(function (Builder $q) use ($start, $end) {
                $q->whereBetween('analyzed_at', [$start, $end])
                    ->orWhereBetween('validated_at', [$start, $end]);
            })
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('sample', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->get(['analyzed_by', 'validated_by'])
            ->flatMap(fn ($t) => [$t->analyzed_by, $t->validated_by])
            ->filter();
    }

    private function inferBranchName(int $userId, Carbon $start, Carbon $end, ?int $filterBranchId): string
    {
        if ($filterBranchId) {
            return LabBranch::find($filterBranchId)?->name ?? '—';
        }

        $counts = [];
        foreach (self::PROTOCOL_TYPES as $type) {
            $rows = AuditLog::query()
                ->where('user_id', $userId)
                ->where('auditable_type', $type)
                ->whereBetween('created_at', [$start, $end])
                ->pluck('auditable_id');

            foreach ($rows as $id) {
                $branchId = $type::query()->whereKey($id)->value('lab_branch_id');
                if ($branchId) {
                    $counts[$branchId] = ($counts[$branchId] ?? 0) + 1;
                }
            }
        }

        if ($counts === []) {
            $defaultId = User::find($userId)?->default_lab_branch_id;
            if ($defaultId) {
                return LabBranch::find($defaultId)?->name ?? '—';
            }

            return '—';
        }

        arsort($counts);
        $topBranchId = array_key_first($counts);

        return LabBranch::find($topBranchId)?->name ?? '—';
    }

    private function primaryJobName(Employee $employee, ?int $filterJobId): string
    {
        if ($filterJobId) {
            $job = $employee->jobs->firstWhere('id', $filterJobId);

            return $job?->name ?? $employee->jobs->first()?->name ?? '—';
        }

        return $employee->jobs->first()?->name ?? '—';
    }

    private function rate(int $numerator, int $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 1);
    }
}
