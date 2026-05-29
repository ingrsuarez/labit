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

    /** Orden de filas en el reporte: recepción → técnico → bioquímico → director técnico */
    private const ROLE_SORT_ORDER = [
        'recepcion-lab' => 1,
        'tecnico-lab' => 2,
        'bioquimico' => 3,
        'director-tecnico' => 4,
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

            $roles = $this->resolveLabRoles($user, $employee);
            if ($roles === []) {
                continue;
            }

            $hasBiochemistMetrics = in_array('bioquimico', $roles, true)
                || in_array('director-tecnico', $roles, true);

            $metrics = [];
            $metrics['delivery'] = $this->deliveryMetrics($user->id, $start, $end, $labBranchId, $protocolsCreated);
            $totalDelivered += $metrics['delivery']['results_delivered'];
            $metrics['loading'] = $this->loadingMetrics($user->id, $start, $end, $labBranchId, $protocolsCreated);

            if (in_array('recepcion-lab', $roles, true)) {
                $metrics['reception'] = $this->receptionMetrics($user->id, $start, $end, $labBranchId);
            }
            if (in_array('tecnico-lab', $roles, true)) {
                $metrics['technician'] = $this->technicianMetrics($user->id, $start, $end, $labBranchId);
            }
            if ($hasBiochemistMetrics) {
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

        usort($rows, function (array $a, array $b): int {
            $byRole = $this->roleSortKey($a['roles']) <=> $this->roleSortKey($b['roles']);
            if ($byRole !== 0) {
                return $byRole;
            }

            return strcmp($a['employee_name'], $b['employee_name']);
        });

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

    private function deliveryMetrics(int $userId, Carbon $start, Carbon $end, ?int $labBranchId, int $denominator): array
    {
        $resultsDelivered = $this->countDistinctProtocolDeliveries($userId, $start, $end, $labBranchId);

        return [
            'results_delivered' => $resultsDelivered,
            'delivery_rate' => $this->rate($resultsDelivered, $denominator),
        ];
    }

    private function receptionMetrics(int $userId, Carbon $start, Carbon $end, ?int $labBranchId): array
    {
        return [
            'protocols_created' => $this->countDistinctProtocolAudits($userId, 'created', $start, $end, $labBranchId),
            'patients_created' => $this->countAudits($userId, 'created', $start, $end, null, Patient::class),
            'protocols_updated' => $this->countAudits($userId, 'updated', $start, $end, $labBranchId),
            'payments_recorded' => $this->countAudits($userId, 'payment_recorded', $start, $end, $labBranchId),
        ];
    }

    /**
     * Protocolos distintos entregados en el día: result_delivered o email_sent (sin duplicar por protocolo).
     */
    private function countDistinctProtocolDeliveries(int $userId, Carbon $start, Carbon $end, ?int $labBranchId): int
    {
        $q = AuditLog::query()
            ->where('user_id', $userId)
            ->whereIn('action', ['result_delivered', 'email_sent'])
            ->whereIn('auditable_type', self::PROTOCOL_TYPES)
            ->whereBetween('created_at', [$start, $end]);

        $this->applyBranchFilterToAudits($q, $labBranchId);

        $driver = $q->getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return (int) $q->selectRaw('count(distinct auditable_type || \'-\' || auditable_id) as aggregate')->value('aggregate');
        }

        return (int) $q->selectRaw('count(distinct concat(auditable_type, "-", auditable_id)) as aggregate')->value('aggregate');
    }

    private function loadingMetrics(int $userId, Carbon $start, Carbon $end, ?int $labBranchId, int $denominator): array
    {
        $resultsEntered = $this->countResultsEntered($userId, $start, $end, $labBranchId);
        $protocolsWithResults = $this->countProtocolsWithResultsLoaded($userId, $start, $end, $labBranchId);

        return [
            'results_entered' => $resultsEntered,
            'protocols_with_results' => $protocolsWithResults,
            'load_rate' => $this->rate($protocolsWithResults, $denominator),
        ];
    }

    private function technicianMetrics(int $userId, Carbon $start, Carbon $end, ?int $labBranchId): array
    {
        return [
            'results_loaded_events' => $this->countAudits($userId, 'results_loaded', $start, $end, $labBranchId),
            'tests_removed' => $this->countAudits($userId, 'test_removed', $start, $end, $labBranchId),
        ];
    }

    private function countResultsEntered(int $userId, Carbon $start, Carbon $end, ?int $labBranchId): int
    {
        $fromDb = $this->countResultsEnteredFromDb($userId, $start, $end, $labBranchId);

        if ($fromDb > 0) {
            return $fromDb;
        }

        return $this->countResultsEnteredFromResultsLoadedAudit($userId, $start, $end, $labBranchId);
    }

    private function countResultsEnteredFromDb(int $userId, Carbon $start, Carbon $end, ?int $labBranchId): int
    {
        $clinical = AdmissionTest::query()
            ->where('result_entered_by', $userId)
            ->whereBetween('result_entered_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('admission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->count();

        $vet = VetAdmissionTest::query()
            ->where('analyzed_by', $userId)
            ->whereBetween('analyzed_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('vetAdmission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->count();

        $sample = SampleDetermination::query()
            ->where('analyzed_by', $userId)
            ->whereBetween('analyzed_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('sample', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->count();

        return $clinical + $vet + $sample;
    }

    /**
     * Fallback histórico: prácticas con resultado en protocolos donde el usuario registró results_loaded.
     */
    private function countResultsEnteredFromResultsLoadedAudit(int $userId, Carbon $start, Carbon $end, ?int $labBranchId): int
    {
        $count = 0;

        $admissionIds = $this->protocolIdsFromAudit($userId, 'results_loaded', Admission::class, $start, $end, $labBranchId);
        if ($admissionIds->isNotEmpty()) {
            $count += AdmissionTest::query()
                ->whereIn('admission_id', $admissionIds)
                ->whereNotNull('result')
                ->where('result', '!=', '')
                ->count();
        }

        $vetIds = $this->protocolIdsFromAudit($userId, 'results_loaded', VetAdmission::class, $start, $end, $labBranchId);
        if ($vetIds->isNotEmpty()) {
            $count += VetAdmissionTest::query()
                ->whereIn('vet_admission_id', $vetIds)
                ->whereNotNull('result')
                ->where('result', '!=', '')
                ->count();
        }

        $sampleIds = $this->protocolIdsFromAudit($userId, 'results_loaded', Sample::class, $start, $end, $labBranchId);
        if ($sampleIds->isNotEmpty()) {
            $count += SampleDetermination::query()
                ->whereIn('sample_id', $sampleIds)
                ->whereNotNull('result')
                ->where('result', '!=', '')
                ->count();
        }

        return $count;
    }

    /**
     * Protocolos distintos con carga: result_entered_* o audit results_loaded (sin duplicar).
     */
    private function countProtocolsWithResultsLoaded(int $userId, Carbon $start, Carbon $end, ?int $labBranchId): int
    {
        $keys = collect();

        $admissionIds = AdmissionTest::query()
            ->where('result_entered_by', $userId)
            ->whereBetween('result_entered_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('admission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->distinct()
            ->pluck('admission_id');
        foreach ($admissionIds as $id) {
            $keys->push(Admission::class.'-'.$id);
        }

        $vetIds = VetAdmissionTest::query()
            ->where('analyzed_by', $userId)
            ->whereBetween('analyzed_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('vetAdmission', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->distinct()
            ->pluck('vet_admission_id');
        foreach ($vetIds as $id) {
            $keys->push(VetAdmission::class.'-'.$id);
        }

        $sampleIds = SampleDetermination::query()
            ->where('analyzed_by', $userId)
            ->whereBetween('analyzed_at', [$start, $end])
            ->when($labBranchId, fn (Builder $q) => $q->whereHas('sample', fn (Builder $a) => $a->where('lab_branch_id', $labBranchId)))
            ->distinct()
            ->pluck('sample_id');
        foreach ($sampleIds as $id) {
            $keys->push(Sample::class.'-'.$id);
        }

        $auditRows = AuditLog::query()
            ->where('user_id', $userId)
            ->where('action', 'results_loaded')
            ->whereIn('auditable_type', self::PROTOCOL_TYPES)
            ->whereBetween('created_at', [$start, $end])
            ->when($labBranchId, function (Builder $q) use ($labBranchId) {
                $this->applyBranchFilterToAudits($q, $labBranchId);
            })
            ->get(['auditable_type', 'auditable_id']);

        foreach ($auditRows as $row) {
            $keys->push($row->auditable_type.'-'.$row->auditable_id);
        }

        return $keys->unique()->count();
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function protocolIdsFromAudit(int $userId, string $action, string $auditableType, Carbon $start, Carbon $end, ?int $labBranchId): \Illuminate\Support\Collection
    {
        $q = AuditLog::query()
            ->where('user_id', $userId)
            ->where('action', $action)
            ->where('auditable_type', $auditableType)
            ->whereBetween('created_at', [$start, $end]);

        $this->applyBranchFilterToAudits($q, $labBranchId);

        return $q->distinct()->pluck('auditable_id');
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

    /**
     * Roles de laboratorio para el reporte: Spatie + director técnico por puesto.
     * El director técnico recibe las mismas métricas que bioquímico aunque no tenga el rol Spatie.
     */
    private function resolveLabRoles(User $user, Employee $employee): array
    {
        $roles = $user->getRoleNames()
            ->intersect(['recepcion-lab', 'tecnico-lab', 'bioquimico'])
            ->values()
            ->all();

        if ($this->employeeHasDirectorTecnicoJob($employee) && ! in_array('bioquimico', $roles, true)) {
            $roles[] = 'director-tecnico';
        }

        return $roles;
    }

    private function roleSortKey(array $roles): int
    {
        $keys = array_map(fn (string $role) => self::ROLE_SORT_ORDER[$role] ?? 99, $roles);

        return $keys === [] ? 99 : min($keys);
    }

    private function employeeHasDirectorTecnicoJob(Employee $employee): bool
    {
        return $employee->jobs->contains(function ($job) {
            $normalized = mb_strtolower(str_replace(['é', 'É'], 'e', $job->name));

            return str_contains($normalized, 'director') && str_contains($normalized, 'tecnico');
        });
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
