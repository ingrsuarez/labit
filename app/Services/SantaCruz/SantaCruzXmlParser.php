<?php

namespace App\Services\SantaCruz;

use Carbon\Carbon;

class SantaCruzXmlParseException extends \RuntimeException {}

class SantaCruzXmlParser
{
    /**
     * @return array{
     *   document_type: string,
     *   document_number: string,
     *   last_name: string,
     *   first_name: string,
     *   sex_raw: string,
     *   birth_raw: string,
     *   phone: string,
     *   email: string,
     *   address_line: string,
     *   city: string,
     *   state: string,
     *   country: string,
     *   accession_number: string,
     *   order_date_raw: string,
     *   order_time_raw: string,
     *   requesting_doctor: string,
     *   branch_name: string,
     *   practicas: list<array{prestacion_code: string, prestacion_name: string}>
     * }
     */
    public function parse(string $xml): array
    {
        libxml_use_internal_errors(true);
        $sx = @simplexml_load_string($xml);
        if ($sx === false) {
            throw new SantaCruzXmlParseException('XML inválido o vacío.');
        }
        if ($sx->getName() !== 'Solicitud') {
            throw new SantaCruzXmlParseException('Se esperaba el elemento raíz Solicitud.');
        }

        $dir = $sx->Direccion ?? null;
        $calle = trim((string) ($dir->Calle ?? ''));
        $altura = trim((string) ($dir->Altura ?? ''));
        $cp = trim((string) ($dir->CodigoPostal ?? ''));
        $addressLine = trim($calle.(($calle !== '' && $altura !== '' && $altura !== '0') ? ' '.$altura : '').($cp !== '' && $cp !== '0' ? ' (CP '.$cp.')' : ''));

        if (! isset($sx->OrdenPrestacion)) {
            throw new SantaCruzXmlParseException('Falta OrdenPrestacion en el XML.');
        }

        $practicas = [];
        if (isset($sx->OrdenPrestacion->Practicas->Practica)) {
            foreach ($sx->OrdenPrestacion->Practicas->Practica as $p) {
                $code = trim((string) ($p->Prestacion ?? ''));
                $name = trim((string) ($p->Nombre ?? ''));
                if ($code === '' && $name === '') {
                    continue;
                }
                $practicas[] = [
                    'prestacion_code' => $code,
                    'prestacion_name' => $name,
                ];
            }
        }

        return [
            'document_type' => trim((string) $sx->TipoDocumento),
            'document_number' => trim((string) $sx->NumeroDocumento),
            'last_name' => trim((string) $sx->Apellidos),
            'first_name' => trim((string) $sx->Nombres),
            'sex_raw' => trim((string) $sx->Sexo),
            'birth_raw' => trim((string) $sx->FechaNacimiento),
            'phone' => trim((string) $sx->Telefono),
            'email' => trim((string) $sx->Mail),
            'address_line' => $addressLine,
            'city' => trim((string) ($dir->Localidad ?? '')),
            'state' => trim((string) ($dir->Provincia ?? '')),
            'country' => trim((string) ($dir->Pais ?? '')),
            'accession_number' => trim((string) ($sx->OrdenPrestacion->AccessionNumber ?? '')),
            'order_date_raw' => trim((string) ($sx->OrdenPrestacion->Fecha ?? '')),
            'order_time_raw' => trim((string) ($sx->OrdenPrestacion->Hora ?? '')),
            'requesting_doctor' => trim((string) ($sx->ResponsableExamen->NombreCompleto ?? '')),
            'branch_name' => trim((string) ($sx->Sucursal->Nombre ?? '')),
            'practicas' => $practicas,
        ];
    }

    public function orderDate(array $parsed): Carbon
    {
        $raw = $parsed['order_date_raw'] ?? '';
        if (strlen($raw) === 8 && ctype_digit($raw)) {
            return Carbon::createFromFormat('Ymd', $raw)->startOfDay();
        }

        return Carbon::parse($raw);
    }

    public function birthDate(array $parsed): ?Carbon
    {
        $raw = $parsed['birth_raw'] ?? '';
        if (strlen($raw) === 8 && ctype_digit($raw)) {
            return Carbon::createFromFormat('Ymd', $raw)->startOfDay();
        }
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public function mapSex(string $raw): string
    {
        $r = mb_strtolower(trim($raw));

        return match (true) {
            str_contains($r, 'mujer'), str_contains($r, 'femen') => 'f',
            str_contains($r, 'varón'), str_contains($r, 'varon'), str_contains($r, 'masc') => 'm',
            default => 'm',
        };
    }
}
