<?php

namespace App\Services\Payroll;

use App\Models\SalaryItem;

/**
 * Resuelve el monto base numérico para calcular deducciones en liquidación mensual.
 * Solo aplica a calculation_type percentage (calculateItem ignora la base en fixed / fixed_proportional / hours).
 */
class DeductionBaseResolver
{
    /**
     * @param  array<string, float>  $bases  Mismas claves que en PayrollController (basic, basic_antiguedad, …)
     */
    public static function resolve(?string $calculationBase, float $subtotalRemunerativo, float $totalHaberes, array $bases): float
    {
        $key = $calculationBase ?: 'subtotal_remunerativo';

        if (! in_array($key, SalaryItem::DEDUCTION_CALCULATION_BASES, true)) {
            return $subtotalRemunerativo;
        }

        return match ($key) {
            'subtotal_remunerativo' => $subtotalRemunerativo,
            'total_haberes' => $totalHaberes,
            'basic', 'basic_vacaciones', 'basic_antiguedad', 'basic_antiguedad_titulo', 'basic_hours', 'basic_hours_antiguedad' => $bases[$key] ?? $subtotalRemunerativo,
            default => $subtotalRemunerativo,
        };
    }
}
