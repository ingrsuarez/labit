<?php

namespace App\Enums;

use App\Models\Admission;
use App\Models\Sample;
use App\Models\VetAdmission;

/**
 * Tipo de protocolo expuesto por la API pública v1.
 * Identifica unívocamente el modelo (clínico/muestras/vet) detrás de un
 * `protocol_number` y permite emitir respuestas polimórficas unificadas.
 */
enum ProtocolType: string
{
    case Clinical = 'clinical';
    case Sample = 'sample';
    case Vet = 'vet';

    /**
     * Clase Eloquent asociada al tipo. Las queries del lookup service
     * y los show endpoints despachan en base a esto.
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Clinical => Admission::class,
            self::Sample => Sample::class,
            self::Vet => VetAdmission::class,
        };
    }

    /**
     * Letra inicial del `protocol_number` que el trait `GeneratesProtocolNumber`
     * concatena con `ymd` + 4 dígitos (ej: `C260418000123`).
     * NOTA: el formato real NO usa guión separador, así que el match es por
     * primer carácter, no por prefijo `X-`.
     */
    public function protocolPrefix(): string
    {
        return match ($this) {
            self::Clinical => 'C',
            self::Sample => 'A',
            self::Vet => 'V',
        };
    }

    /**
     * Detecta el tipo a partir del primer carácter del `protocol_number`.
     * Devuelve null si la cadena está vacía o el prefijo no es reconocido,
     * en cuyo caso el caller debe hacer fallback de búsqueda en los 3 modelos.
     */
    public static function fromBarcodePrefix(string $code): ?self
    {
        $first = substr($code, 0, 1);

        foreach (self::cases() as $case) {
            if ($case->protocolPrefix() === $first) {
                return $case;
            }
        }

        return null;
    }
}
