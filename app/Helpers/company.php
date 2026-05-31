<?php

use App\Models\Company;

if (! function_exists('active_company_id')) {
    function active_company_id(): ?int
    {
        return session('active_company_id');
    }
}

if (! function_exists('active_company_id_or_abort')) {
    /**
     * ID de empresa activa validado. Repara sesión stale y usa default del usuario.
     */
    function active_company_id_or_abort(): int
    {
        $id = active_company_id();
        if ($id && Company::whereKey($id)->exists()) {
            return (int) $id;
        }

        if (auth()->check()) {
            $default = auth()->user()->defaultCompany();
            if ($default) {
                session(['active_company_id' => $default->id]);

                return (int) $default->id;
            }
        }

        abort(403, 'Seleccioná una empresa activa para continuar.');
    }
}

if (! function_exists('active_company')) {
    function active_company(): ?Company
    {
        $id = active_company_id();

        return $id ? Company::find($id) : null;
    }
}

if (! function_exists('ipac_sas_company_id')) {
    function ipac_sas_company_id(): ?int
    {
        static $id = null;
        if ($id === null) {
            $company = Company::where('cuit', '30-71922759-3')->first();
            $id = $company?->id ?? active_company_id();
        }

        return $id;
    }
}

if (! function_exists('billing_summary_lab')) {
    /**
     * Datos del laboratorio para encabezados de resúmenes de facturación (pantalla, PDF, Excel).
     *
     * @return array{name: string, cuit: string, address_line: string, logo_url: string, logo_path: string, has_logo: bool}
     */
    function billing_summary_lab(): array
    {
        static $lab = null;
        if ($lab !== null) {
            return $lab;
        }

        $company = null;
        $companyId = ipac_sas_company_id();
        if ($companyId) {
            $company = Company::find($companyId);
        }

        $addressParts = array_filter([
            $company?->address,
            $company?->city,
            $company?->state,
        ]);

        $logoPath = public_path('images/logo_ipac.png');

        $lab = [
            'name' => $company?->name ?? 'IPAC LABORATORIOS S.A.S.',
            'cuit' => $company?->cuit ?? '30-71922759-3',
            'address_line' => $addressParts !== []
                ? implode(', ', $addressParts)
                : 'Leguizamon 356, Neuquén',
            'logo_url' => asset('images/logo_ipac.png'),
            'logo_path' => $logoPath,
            'has_logo' => is_file($logoPath),
        ];

        return $lab;
    }
}

if (! function_exists('billing_entity_display_name')) {
    /**
     * Nombre legible para resúmenes de facturación (obra social, cliente): título por palabra.
     */
    function billing_entity_display_name(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $normalized = mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        return preg_replace_callback(
            '/\b([A-Za-z])\.([A-Za-z])(\.?)/u',
            static fn (array $m): string => mb_strtoupper($m[1]).'.'.mb_strtoupper($m[2]).$m[3],
            $normalized
        ) ?? $normalized;
    }
}

if (! function_exists('billing_patient_display_name')) {
    /**
     * Apellido y nombre del paciente con capitalización para reportes de facturación.
     *
     * @return string Formato "Apellido, Nombre" o "N/A" si no hay datos.
     */
    function billing_patient_display_name(?string $lastName, ?string $firstName): string
    {
        $last = billing_entity_display_name(trim((string) $lastName));
        $first = billing_entity_display_name(trim((string) $firstName));

        if ($last === '' && $first === '') {
            return 'N/A';
        }

        if ($last === '') {
            return $first;
        }

        if ($first === '') {
            return $last;
        }

        return $last.', '.$first;
    }
}

if (! function_exists('billing_patient_summary_name')) {
    /**
     * Nombre del paciente para columnas resumidas (nombre + apellido).
     */
    function billing_patient_summary_name(?string $lastName, ?string $firstName): string
    {
        $formatted = billing_patient_display_name($lastName, $firstName);

        if ($formatted === 'N/A') {
            return $formatted;
        }

        if (! str_contains($formatted, ', ')) {
            return $formatted;
        }

        [$last, $first] = explode(', ', $formatted, 2);

        return trim($first.' '.$last);
    }
}
