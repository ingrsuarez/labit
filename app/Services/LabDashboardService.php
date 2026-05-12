<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\LabBranch;
use App\Models\Sample;
use App\Models\VetAdmission;
use Carbon\Carbon;

class LabDashboardService
{
    public function __construct(private ?int $labBranchId = null) {}

    /**
     * KPIs: creados (N días), pendientes validar (vivo), validados (N días), enviados (N días).
     */
    public function kpis(int $days = 30): array
    {
        $since = now()->subDays($days)->startOfDay();

        return [
            'created' => $this->countCreated($since),
            'pending_validation' => $this->countPendingValidation(),
            'validated' => $this->countValidated($since),
            'sent' => $this->countSent($since),
        ];
    }

    /**
     * Conteos agrupados por estado (acumulado vivo, sin filtro de fecha).
     */
    public function byStatus(): array
    {
        $admPending = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '')
                    ->orWhere('status', 'pending');
            })
            ->count();

        $admInProgress = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', 'in_progress')
            ->count();

        $admCompleted = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', 'completed')
            ->count();

        $admValidated = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', 'validated')
            ->count();

        $admSent = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->whereNotNull('sent_at')
            ->count();

        $vetPending = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '')
                    ->orWhere('status', 'pending');
            })
            ->count();

        $vetInProgress = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', 'in_progress')
            ->count();

        $vetCompleted = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', 'completed')
            ->count();

        $vetValidated = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', 'validated')
            ->count();

        $vetSent = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->whereNotNull('sent_at')
            ->count();

        $samplePending = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('validation_status')
                    ->orWhere('validation_status', 'pending');
            })
            ->count();

        $sampleInProgress = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', '!=', 'cancelled')
            ->where('validation_status', 'partial')
            ->count();

        $sampleCompleted = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', 'completed')
            ->where('validation_status', '!=', 'validated')
            ->count();

        $sampleValidated = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('validation_status', 'validated')
            ->count();

        $sampleSent = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->whereNotNull('sent_at')
            ->count();

        return [
            'pending' => $admPending + $vetPending + $samplePending,
            'in_progress' => $admInProgress + $vetInProgress + $sampleInProgress,
            'completed' => $admCompleted + $vetCompleted + $sampleCompleted,
            'validated' => $admValidated + $vetValidated + $sampleValidated,
            'sent' => $admSent + $vetSent + $sampleSent,
        ];
    }

    /**
     * Pendientes de validar desglosados por tipo de laboratorio.
     */
    public function byLabType(): array
    {
        $clinico = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['validated', 'cancelled']);
            })
            ->count();

        $veterinario = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['validated', 'cancelled']);
            })
            ->count();

        $aguas = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('validation_status')
                    ->orWhere('validation_status', '!=', 'validated');
            })
            ->count();

        return [
            'clinico' => $clinico,
            'veterinario' => $veterinario,
            'aguas' => $aguas,
        ];
    }

    /**
     * Pendientes de validar por sede (siempre muestra todas las sedes activas).
     */
    public function byBranch(): array
    {
        $branches = LabBranch::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $result = [];

        foreach ($branches as $branch) {
            $admCount = Admission::where('lab_branch_id', $branch->id)
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhereNotIn('status', ['validated', 'cancelled']);
                })
                ->count();

            $vetCount = VetAdmission::where('lab_branch_id', $branch->id)
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhereNotIn('status', ['validated', 'cancelled']);
                })
                ->count();

            $sampleCount = Sample::where('lab_branch_id', $branch->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) {
                    $q->whereNull('validation_status')
                        ->orWhere('validation_status', '!=', 'validated');
                })
                ->count();

            $result[] = [
                'id' => $branch->id,
                'name' => $branch->name,
                'pending' => $admCount + $vetCount + $sampleCount,
            ];
        }

        return $result;
    }

    /**
     * Protocolos pendientes con más de $days días de antigüedad.
     */
    public function overdue(int $days = 3): array
    {
        $cutoff = now()->subDays($days)->endOfDay();

        $admOldest = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['validated', 'cancelled']);
            })
            ->where('created_at', '<', $cutoff)
            ->min('created_at');

        $admCount = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['validated', 'cancelled']);
            })
            ->where('created_at', '<', $cutoff)
            ->count();

        $vetOldest = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['validated', 'cancelled']);
            })
            ->where('created_at', '<', $cutoff)
            ->min('created_at');

        $vetCount = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['validated', 'cancelled']);
            })
            ->where('created_at', '<', $cutoff)
            ->count();

        $sampleOldest = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('validation_status')
                    ->orWhere('validation_status', '!=', 'validated');
            })
            ->where('created_at', '<', $cutoff)
            ->min('created_at');

        $sampleCount = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('validation_status')
                    ->orWhere('validation_status', '!=', 'validated');
            })
            ->where('created_at', '<', $cutoff)
            ->count();

        $totalCount = $admCount + $vetCount + $sampleCount;

        $dates = array_filter([$admOldest, $vetOldest, $sampleOldest]);
        $oldestDate = ! empty($dates) ? Carbon::parse(min($dates))->toDateString() : null;

        return [
            'count' => $totalCount,
            'oldest_date' => $oldestDate,
        ];
    }

    private function countCreated(Carbon $since): int
    {
        $adm = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('created_at', '>=', $since)
            ->count();

        $vet = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('created_at', '>=', $since)
            ->count();

        $sample = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('created_at', '>=', $since)
            ->count();

        return $adm + $vet + $sample;
    }

    private function countPendingValidation(): int
    {
        $adm = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereIn('status', ['pending', 'in_progress', 'completed', '']);
            })
            ->count();

        $vet = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->whereIn('status', ['pending', 'in_progress', 'completed'])
            ->count();

        $sample = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('validation_status')
                    ->orWhere('validation_status', '!=', 'validated');
            })
            ->count();

        return $adm + $vet + $sample;
    }

    private function countValidated(Carbon $since): int
    {
        $adm = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', 'validated')
            ->where('updated_at', '>=', $since)
            ->count();

        $vet = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('status', 'validated')
            ->where('updated_at', '>=', $since)
            ->count();

        $sample = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('validation_status', 'validated')
            ->where(function ($q) use ($since) {
                $q->where('validated_at', '>=', $since)
                    ->orWhere('updated_at', '>=', $since);
            })
            ->count();

        return $adm + $vet + $sample;
    }

    private function countSent(Carbon $since): int
    {
        $adm = Admission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('sent_at', '>=', $since)
            ->count();

        $vet = VetAdmission::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('sent_at', '>=', $since)
            ->count();

        $sample = Sample::query()
            ->when($this->labBranchId, fn ($q) => $q->where('lab_branch_id', $this->labBranchId))
            ->where('sent_at', '>=', $since)
            ->count();

        return $adm + $vet + $sample;
    }
}
