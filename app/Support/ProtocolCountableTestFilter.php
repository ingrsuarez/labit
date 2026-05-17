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

        if ($test->relationLoaded('childTests') && $test->childTests->isNotEmpty()) {
            return true;
        }

        if ($test->relationLoaded('children') && $test->children->isNotEmpty()) {
            return true;
        }

        return false;
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
