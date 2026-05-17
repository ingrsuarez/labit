<?php

namespace App\Support;

use App\Models\AdmissionTest;
use App\Models\Test;
use App\Models\VetAdmissionTest;

/**
 * Filtro compartido para excluir prácticas padre-título del cálculo de estado (v1.69.0 / v1.102.0).
 */
trait ProtocolCountableTestFilter
{
    protected function isTitleParentTest(?Test $test): bool
    {
        if (! $test) {
            return false;
        }

        if ($test->relationLoaded('childTests')) {
            return $test->childTests->isNotEmpty();
        }

        if ($test->relationLoaded('children')) {
            return $test->children->isNotEmpty();
        }

        // Sin eager load (p. ej. tras saveResults): consultar hijos para no contar
        // al padre-título como determinación vacía (hemograma, perfiles, etc.).
        return $test->childTests()->exists() || $test->children()->exists();
    }

    protected function filterCountableAdmissionTests($admissionTests)
    {
        return app(\App\Services\ProtocolStatusCalculator::class)->filterCountableDeterminations(
            $admissionTests,
            fn (AdmissionTest $at) => $at->hasResult(),
            fn (AdmissionTest $at) => (bool) $at->is_validated,
            fn (AdmissionTest $at) => $this->isTitleParentTest(
                $at->relationLoaded('test') ? $at->test : null
            ),
        );
    }

    protected function filterCountableVetTests($vetTests)
    {
        return app(\App\Services\ProtocolStatusCalculator::class)->filterCountableDeterminations(
            $vetTests,
            fn (VetAdmissionTest $vt) => $vt->hasResult(),
            fn (VetAdmissionTest $vt) => (bool) $vt->is_validated,
            fn (VetAdmissionTest $vt) => $this->isTitleParentTest(
                $vt->relationLoaded('test') ? $vt->test : null
            ),
        );
    }
}
