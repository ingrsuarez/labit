<?php

use App\Models\LabBranch;

if (! function_exists('active_lab_branch_id')) {
    /**
     * Resolve the active lab branch: session → user default → null.
     */
    function active_lab_branch_id(): ?int
    {
        if (session()->has('active_lab_branch_id')) {
            return session('active_lab_branch_id') ?: null;
        }

        return auth()->user()?->default_lab_branch_id;
    }
}

if (! function_exists('active_lab_branch_name')) {
    function active_lab_branch_name(): string
    {
        $id = active_lab_branch_id();
        if (! $id) {
            return 'Todas las sedes';
        }

        return LabBranch::find($id)?->name ?? 'Todas las sedes';
    }
}
