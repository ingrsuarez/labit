<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AdmissionTest;
use App\Models\SampleDetermination;
use App\Models\VetAdmissionTest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Estructura unificada de una determinación dentro de un protocolo.
 *
 * Trabaja sobre los 3 modelos distintos (`AdmissionTest`, `SampleDetermination`,
 * `VetAdmissionTest`) que comparten la mayoría de columnas pero NO el campo
 * `status` (las muestras y vet usan un enum string; las clínicas usan
 * `authorization_status` + `is_validated` + `result`). Acá lo derivamos a un
 * `status` único equivalente para que LISCOM no tenga que tener 3 caminos.
 */
class DeterminationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $test = $this->test;

        return [
            'id' => $this->id,
            'test_id' => $test?->id,
            'test_code' => $test?->code,
            'test_name' => $test?->name,
            'material' => $test?->materialRelation ? [
                'id' => $test->materialRelation->id,
                'name' => $test->materialRelation->name,
                'abbreviation' => $test->materialRelation->code,
            ] : null,
            'unit' => $this->unit,
            'reference_value' => $this->reference_value,
            'status' => $this->resolveStatus(),
            'has_result' => $this->resource->hasResult(),
        ];
    }

    /**
     * Mapea el estado interno (heterogéneo entre los 3 modelos) a un valor
     * común esperado por LISCOM: pending / in_progress / completed / validated.
     */
    private function resolveStatus(): string
    {
        if ($this->resource instanceof AdmissionTest) {
            $hasResult = $this->resource->hasResult();
            $isValidated = (bool) $this->resource->is_validated;
            $authorized = in_array($this->resource->authorization_status, [
                AdmissionTest::STATUS_AUTHORIZED,
                AdmissionTest::STATUS_NOT_REQUIRED,
            ], true);

            if ($isValidated && $hasResult) {
                return 'validated';
            }
            if ($hasResult) {
                return 'completed';
            }
            if ($authorized) {
                return 'in_progress';
            }

            return 'pending';
        }

        if ($this->resource instanceof SampleDetermination || $this->resource instanceof VetAdmissionTest) {
            if ((bool) $this->resource->is_validated && $this->resource->hasResult()) {
                return 'validated';
            }

            return $this->resource->status ?? 'pending';
        }

        return 'pending';
    }
}
