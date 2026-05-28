<?php

namespace App\Traits;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait GeneratesProtocolNumber
{
    // Prefijos registrados:
    // 'A' → Sample (aguas/alimentos)
    // 'C' → Admission (clínico)
    // 'V' → VetAdmission (veterinario) — se implementa en v1.24.0

    /**
     * Genera número de protocolo con formato [PREFIJO][AAMMDD][NNNN]
     * El contador NNNN se reinicia a 0001 cada día, por prefijo independiente.
     *
     * Debe invocarse dentro de la misma transacción DB del create del protocolo
     * para que lockForUpdate() mantenga el bloqueo hasta el insert.
     * Si no hay transacción activa, el número se calcula sin lock (tests/unitarios).
     */
    public static function generatePrefixedProtocolNumber(string $prefix, string $column = 'protocol_number'): string
    {
        $today = now();
        $dateStr = $today->format('ymd');
        $todayPrefix = $prefix.$dateStr;

        $query = static::where($column, 'like', $todayPrefix.'%')
            ->orderBy($column, 'desc');

        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $lastRecord = $query->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->$column, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $todayPrefix.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Ejecuta un callback dentro de una transacción, reintentando ante colisión de protocol_number.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function retryOnProtocolNumberCollision(callable $callback, int $maxAttempts = 5): mixed
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction($callback);
            } catch (QueryException $e) {
                if (! static::isProtocolNumberDuplicateException($e)) {
                    throw $e;
                }

                $lastException = $e;

                Log::warning('Colisión de protocol_number al crear protocolo, reintentando', [
                    'model' => static::class,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                ]);
            }
        }

        throw $lastException;
    }

    protected static function isProtocolNumberDuplicateException(QueryException $e): bool
    {
        $sqlState = $e->getCode();
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        if ($sqlState !== '23000' && $driverCode !== 1062) {
            return false;
        }

        return str_contains(strtolower($e->getMessage()), 'protocol_number');
    }
}
