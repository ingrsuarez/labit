<?php

namespace App\Support;

/**
 * Texto de valores de referencia para pantallas de carga e informes PDF.
 * Une el valor persistido/calculado con "Otros valores de referencia" del test cuando corresponde.
 */
final class ProtocolReferenceDisplay
{
    /**
     * @param  string|null  $primaryReference  Valor en línea de admisión/muestra o rango calculado
     * @param  string|null  $testOtherReference  Campo tests.other_reference (texto libre / no numérico)
     */
    public static function line(?string $primaryReference, ?string $testOtherReference): string
    {
        $primary = self::normalizePrimary($primaryReference);
        $other = trim((string) ($testOtherReference ?? ''));

        if ($primary !== '' && $other !== '') {
            // Evitar duplicar si ya fue concatenado (p. ej. VetAdmissionController::buildReferenceValue)
            if (stripos($primary, $other) !== false) {
                return $primary;
            }

            return $primary.' | '.$other;
        }

        return $primary !== '' ? $primary : $other;
    }

    private static function normalizePrimary(?string $value): string
    {
        $s = trim((string) ($value ?? ''));
        if ($s === '') {
            return '';
        }
        if ($s === '-' || $s === '–' || $s === '—') {
            return '';
        }
        // Concatenaciones legacy tipo null - null → " - "
        if (preg_match('/^\s*-\s*$/', $s)) {
            return '';
        }

        return $s;
    }
}
