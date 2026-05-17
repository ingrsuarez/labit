<?php

namespace App\Support;

/**
 * Normaliza NBU de determinaciones: admite coma, ceros finales (1,50 → 1,5) y como máximo un decimal.
 */
final class TestNbuInput
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $normalized = str_replace(',', '.', trim($raw));
        if (! is_numeric($normalized)) {
            return $normalized;
        }

        return self::formatOneDecimal(round((float) $normalized, 1));
    }

    public static function formatOneDecimal(float $value): string
    {
        $formatted = number_format($value, 1, '.', '');

        return preg_replace('/\.0$/', '', $formatted) ?: '0';
    }
}
