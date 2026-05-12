<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Admission;
use App\Models\Sample;
use App\Models\VetAdmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filtros del listado de protocolos reutilizados por index y navegación "Siguiente pendiente".
 */
trait AppliesProtocolIndexFilters
{
    protected function applyLabAdmissionIndexFilters(Request $request, Builder $query): void
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('protocol_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('lastName', 'like', "%{$search}%")
                            ->orWhere('patientId', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('insurance')) {
            $query->where('insurance', $request->insurance);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('lab_branch_id')) {
            if ($request->lab_branch_id === 'all') {
                // sin filtro adicional
            } elseif ($request->lab_branch_id === 'none') {
                $query->whereNull('lab_branch_id');
            } else {
                $query->where('lab_branch_id', $request->lab_branch_id);
            }
        } elseif ($activeBranch = active_lab_branch_id()) {
            $query->where(function ($q) use ($activeBranch) {
                $q->where('lab_branch_id', $activeBranch)
                    ->orWhereNull('lab_branch_id');
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'enviado') {
                $query->where('status', Admission::STATUS_VALIDATED)->whereNotNull('sent_at');
            } else {
                $query->where('status', $request->status);
            }
        }
    }

    protected function applyVetAdmissionIndexFilters(Request $request, Builder $query): void
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('protocol_number', 'like', "%{$search}%")
                    ->orWhere('animal_name', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%")
                    ->orWhere('breed', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('veterinarian', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('species_id')) {
            $query->where('species_id', $request->species_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('owner')) {
            $query->where('owner_name', 'like', '%'.$request->owner.'%');
        }

        if ($request->filled('animal')) {
            $query->where('animal_name', 'like', '%'.$request->animal.'%');
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'pending') {
                $query->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhereNotIn('status', ['validated', 'cancelled']);
                });
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('lab_branch_id')) {
            if ($request->lab_branch_id === 'all') {
                // sin filtro
            } elseif ($request->lab_branch_id === 'none') {
                $query->whereNull('lab_branch_id');
            } else {
                $query->where('lab_branch_id', $request->lab_branch_id);
            }
        } elseif ($activeBranch = active_lab_branch_id()) {
            $query->where(function ($q) use ($activeBranch) {
                $q->where('lab_branch_id', $activeBranch)
                    ->orWhereNull('lab_branch_id');
            });
        }
    }

    /**
     * Misma base que SampleController@index (sede activa) + filtros opcionales en query.
     * list_status se aplica en PHP sobre calculated_status (ver filterSamplesCollectionForList).
     */
    protected function applySampleIndexFilters(Request $request, Builder $query): void
    {
        if ($activeBranch = active_lab_branch_id()) {
            $query->where(function ($q) use ($activeBranch) {
                $q->where('lab_branch_id', $activeBranch)
                    ->orWhereNull('lab_branch_id');
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('protocol_number', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($request->filled('sample_type')) {
            $query->where('sample_type', $request->sample_type);
        }

        if ($request->filled('lab_branch_id')) {
            if ($request->lab_branch_id === 'all') {
                // sin filtro adicional sobre la ya aplicada por sede activa
            } elseif ($request->lab_branch_id === 'none') {
                $query->whereNull('lab_branch_id');
            } else {
                $query->where('lab_branch_id', $request->lab_branch_id);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    protected function labAdmissionNavigationQuery(Request $request): array
    {
        return array_filter(
            $request->only(['search', 'insurance', 'date_from', 'date_to', 'lab_branch_id', 'status']),
            fn ($v) => $v !== null && $v !== ''
        );
    }

    /**
     * @return array<string, string>
     */
    protected function vetAdmissionNavigationQuery(Request $request): array
    {
        return array_filter(
            $request->only(['search', 'species_id', 'customer_id', 'date_from', 'date_to', 'owner', 'animal', 'status', 'lab_branch_id']),
            fn ($v) => $v !== null && $v !== ''
        );
    }

    /**
     * @return array<string, string>
     */
    protected function sampleNavigationQuery(Request $request): array
    {
        return array_filter(
            $request->only(['search', 'sample_type', 'list_status', 'lab_branch_id']),
            fn ($v) => $v !== null && $v !== ''
        );
    }

    /**
     * Colección filtrada como el listado Alpine cuando list_status no basta con SQL (consistente con calculated_status).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Sample>  $samples
     * @return \Illuminate\Database\Eloquent\Collection<int, Sample>
     */
    protected function filterSamplesCollectionForList(Request $request, $samples)
    {
        if (! $request->filled('list_status')) {
            return $samples;
        }

        $want = strtolower((string) $request->list_status);

        return $samples->filter(function (Sample $sample) use ($want) {
            return strtolower($sample->calculated_status) === $want;
        })->values();
    }
}
