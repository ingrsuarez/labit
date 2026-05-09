<?php

namespace App\Http\Controllers\Concerns;

trait FiltersLabelsByMaterialsQuery
{
    /**
     * Filtra filas de etiqueta por query ?materials=EDTA,SUE (siglas).
     * Si el parámetro es inválido o no coincide ninguna fila, devuelve el arreglo original.
     *
     * @param  array<int, array<string, mixed>>  $labels
     * @return array<int, array<string, mixed>>
     */
    protected function filterLabelsByMaterialsQuery(array $labels): array
    {
        $raw = request()->query('materials');
        if (! is_string($raw) || $raw === '') {
            return $labels;
        }

        $codes = array_values(array_filter(array_map('trim', explode(',', $raw))));
        if ($codes === []) {
            return $labels;
        }

        $filtered = array_values(array_filter(
            $labels,
            fn (array $row) => in_array((string) ($row['material'] ?? ''), $codes, true)
        ));

        return count($filtered) > 0 ? $filtered : $labels;
    }
}
