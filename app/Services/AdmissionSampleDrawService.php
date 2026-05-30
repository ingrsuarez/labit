<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdmissionSampleDrawService
{
    public function admissionRequiresSampleDraw(Admission $admission): bool
    {
        $admission->loadMissing([
            'admissionTests.test.materialRelation',
            'admissionTests.test.parentTests',
        ]);

        $parentTestIds = $admission->admissionTests->pluck('test_id')->all();

        foreach ($admission->admissionTests as $at) {
            $test = $at->test;
            if (! $test || ! $test->materialRelation) {
                continue;
            }

            $isChild = $test->parentTests->whereIn('id', $parentTestIds)->isNotEmpty();
            if ($isChild) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function pendingQuery(?int $labBranchId): Builder
    {
        $q = Admission::query()
            ->whereNull('sample_drawn_by')
            ->with([
                'patient',
                'labBranch',
                'admissionTests.test.materialRelation',
                'admissionTests.test.parentTests',
            ]);

        if ($labBranchId) {
            $q->where('lab_branch_id', $labBranchId);
        }

        return $q->orderBy('protocol_number');
    }

    public function pendingCount(?int $labBranchId): int
    {
        return $this->pendingQuery($labBranchId)
            ->get()
            ->filter(fn (Admission $a) => $this->admissionRequiresSampleDraw($a))
            ->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listPending(?int $labBranchId, int $limit = 50): Collection
    {
        return $this->pendingQuery($labBranchId)
            ->limit($limit * 3)
            ->get()
            ->filter(fn (Admission $a) => $this->admissionRequiresSampleDraw($a))
            ->sortBy('protocol_number')
            ->take($limit)
            ->map(fn (Admission $a) => [
                'id' => $a->id,
                'protocol_number' => $a->protocol_number,
                'patient_name' => $a->patient?->full_name ?? '—',
                'branch_name' => $a->labBranch?->name,
                'created_at' => $a->created_at?->toIso8601String(),
                'created_at_label' => $a->created_at?->timezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i'),
            ])
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    public function eligibleDrawers(): Collection
    {
        return User::query()
            ->whereHas('roles', fn (Builder $q) => $q->whereIn('name', ['tecnico-lab', 'bioquimico']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function defaultDrawerIdFor(?User $user): ?int
    {
        if (! $user?->hasAnyRole(['tecnico-lab', 'bioquimico'])) {
            return null;
        }

        $eligibleIds = $this->eligibleDrawers()->pluck('id');

        return $eligibleIds->contains($user->id) ? $user->id : null;
    }

    public function resolveDrawerUserId(?int $requestedId, User $actor): int
    {
        $actsAsDrawerOnly = $actor->hasAnyRole(['tecnico-lab', 'bioquimico'])
            && ! $actor->hasAnyRole(['recepcion-lab', 'admin']);

        if ($actsAsDrawerOnly) {
            return $actor->id;
        }

        if (! $requestedId) {
            throw ValidationException::withMessages([
                'sample_drawn_by' => 'Debe indicar quién realizó la extracción.',
            ]);
        }

        $drawer = User::query()->find($requestedId);
        if (! $drawer || ! $drawer->hasAnyRole(['tecnico-lab', 'bioquimico'])) {
            throw ValidationException::withMessages([
                'sample_drawn_by' => 'El tomador debe ser un técnico o bioquímico del laboratorio.',
            ]);
        }

        return $drawer->id;
    }

    public function registerDraw(Admission $admission, int $drawnByUserId, ?Carbon $at = null): void
    {
        $admission->loadMissing([
            'patient',
            'admissionTests.test.materialRelation',
            'admissionTests.test.parentTests',
        ]);

        if (! $this->admissionRequiresSampleDraw($admission)) {
            throw ValidationException::withMessages([
                'admission' => 'Esta admisión no requiere registro de extracción.',
            ]);
        }

        $drawer = User::query()->findOrFail($drawnByUserId);
        if (! $drawer->hasAnyRole(['tecnico-lab', 'bioquimico'])) {
            throw ValidationException::withMessages([
                'sample_drawn_by' => 'El tomador debe ser un técnico o bioquímico.',
            ]);
        }

        $at = $at ?? now();

        $admission->update([
            'sample_drawn_at' => $at,
            'sample_drawn_by' => $drawnByUserId,
        ]);

        $admission->logAudit(
            'sample_drawn',
            'Registró extracción de muestra en admisión Nº '.$admission->protocol_number
                .' — Tomador: '.$drawer->name
        );
    }

    public function syncDrawFromAdmissionForm(Admission $admission, ?int $sampleDrawnBy): void
    {
        $admission->loadMissing([
            'admissionTests.test.materialRelation',
            'admissionTests.test.parentTests',
        ]);

        if (! $this->admissionRequiresSampleDraw($admission)) {
            if ($admission->sample_drawn_by) {
                $admission->update([
                    'sample_drawn_at' => null,
                    'sample_drawn_by' => null,
                ]);
            }

            return;
        }

        if ($sampleDrawnBy) {
            $this->registerDraw($admission, $sampleDrawnBy);

            return;
        }

        $admission->update([
            'sample_drawn_at' => null,
            'sample_drawn_by' => null,
        ]);
    }

    public function reconcileAfterTestsChanged(Admission $admission): void
    {
        $admission->loadMissing([
            'admissionTests.test.materialRelation',
            'admissionTests.test.parentTests',
        ]);

        if (! $this->admissionRequiresSampleDraw($admission) && $admission->sample_drawn_by) {
            $admission->update([
                'sample_drawn_at' => null,
                'sample_drawn_by' => null,
            ]);
        }
    }

    public function validateDrawerId(?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        $user = User::query()->find($userId);
        if (! $user || ! $user->hasAnyRole(['tecnico-lab', 'bioquimico'])) {
            throw ValidationException::withMessages([
                'sample_drawn_by' => 'El tomador debe ser un técnico o bioquímico del laboratorio.',
            ]);
        }
    }
}
