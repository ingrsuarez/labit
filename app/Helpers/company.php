<?php

use App\Models\Company;

if (! function_exists('active_company_id')) {
    function active_company_id(): ?int
    {
        return session('active_company_id');
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
