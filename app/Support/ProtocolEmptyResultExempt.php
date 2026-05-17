<?php

namespace App\Support;

use App\Models\Test;

/**
 * Prácticas que pueden quedar vacías sin observación (p. ej. fórmula leucocitaria):
 * no cuentan para estado del protocolo ni en la planilla de pendientes.
 */
final class ProtocolEmptyResultExempt
{
    /**
     * @param  object  $row  AdmissionTest, VetAdmissionTest o SampleDetermination (con test opcional)
     */
    public static function isExemptAndEmpty(object $row): bool
    {
        if (method_exists($row, 'hasResult') && $row->hasResult()) {
            return false;
        }

        if ((bool) ($row->is_validated ?? false)) {
            return false;
        }

        $test = self::resolveTest($row);

        return $test !== null && $test->empty_result_exempt;
    }

    /**
     * @param  object  $row
     */
    public static function resolveTest(object $row): ?Test
    {
        if (! method_exists($row, 'loadMissing')) {
            return null;
        }

        $row->loadMissing('test');

        return $row->test;
    }
}
