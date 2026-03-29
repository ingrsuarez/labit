<?php

namespace App\Traits;

trait GeneratesProtocolNumber
{
    // Prefijos registrados:
    // 'A' → Sample (aguas/alimentos)
    // 'C' → Admission (clínico)
    // 'V' → VetAdmission (veterinario) — se implementa en v1.24.0

    /**
     * Genera número de protocolo con formato [PREFIJO][AAMMDD][NNNN]
     * El contador NNNN se reinicia a 0001 cada día, por prefijo independiente.
     */
    public static function generatePrefixedProtocolNumber(string $prefix, string $column = 'protocol_number'): string
    {
        $today = now();
        $dateStr = $today->format('ymd');
        $todayPrefix = $prefix.$dateStr;

        $lastRecord = static::where($column, 'like', $todayPrefix.'%')
            ->orderBy($column, 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->$column, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $todayPrefix.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
