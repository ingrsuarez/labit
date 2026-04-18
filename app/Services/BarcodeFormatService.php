<?php

namespace App\Services;

class BarcodeFormatService
{
    /**
     * Genera el contenido del barcode para una etiqueta de protocolo.
     *
     * Formato: {protocol_number}^{material_abbreviation}
     * Si material_abbreviation es null/vacío, devuelve solo protocol_number.
     *
     * Separator '^' elegido por compatibilidad CODE_128 + estándar HL7 de componentes.
     *
     * @param  string  $protocolNumber  Ej: "C-2026-001234"
     * @param  string|null  $materialAbbreviation  Ej: "EDTA", "SUE", "ORI"
     * @return string Ej: "C-2026-001234^EDTA"
     */
    public static function forLabel(string $protocolNumber, ?string $materialAbbreviation): string
    {
        $material = trim((string) $materialAbbreviation);

        if ($material === '') {
            return $protocolNumber;
        }

        return "{$protocolNumber}^{$material}";
    }
}
