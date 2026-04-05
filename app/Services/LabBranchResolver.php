<?php

namespace App\Services;

use App\Models\LabBranch;
use Illuminate\Validation\ValidationException;

class LabBranchResolver
{
    /**
     * Sedes activas para formularios (compras, stock).
     */
    public static function activeBranchesForForms()
    {
        return LabBranch::query()->active()->orderBy('name')->get();
    }

    /**
     * Sede del documento obligatoria (remito, etc.): no usa fallback a usuario.
     *
     * @throws ValidationException
     */
    public static function requireDocumentBranch(?int $labBranchId): LabBranch
    {
        if (! $labBranchId) {
            throw ValidationException::withMessages([
                'lab_branch_id' => 'El documento debe tener una sede / depósito asignado. Editá el registro y elegí la sede antes de continuar.',
            ]);
        }

        $branch = LabBranch::query()->active()->whereKey($labBranchId)->first();
        if (! $branch) {
            throw ValidationException::withMessages([
                'lab_branch_id' => 'La sede asignada no existe o está inactiva.',
            ]);
        }

        return $branch;
    }

    /**
     * Primera sede activa central, o primera activa por id (misma regla que migración v1.37.0).
     */
    public static function defaultDatabaseBranchId(): ?int
    {
        $id = LabBranch::query()->active()->where('is_central', true)->orderBy('id')->value('id');

        return $id ?? LabBranch::query()->active()->orderBy('id')->value('id');
    }

    /**
     * Sede activa para operaciones de stock: documento explícito → sesión/perfil → default BD.
     */
    public static function resolveBranchIdForStock(?int $documentLabBranchId = null): ?int
    {
        if ($documentLabBranchId) {
            $exists = LabBranch::query()->active()->whereKey($documentLabBranchId)->exists();

            return $exists ? $documentLabBranchId : null;
        }

        $sessionOrUser = active_lab_branch_id() ?? auth()->user()?->default_lab_branch_id;
        if ($sessionOrUser && LabBranch::query()->active()->whereKey($sessionOrUser)->exists()) {
            return (int) $sessionOrUser;
        }

        return self::defaultDatabaseBranchId();
    }

    /**
     * @throws ValidationException
     */
    public static function requireActiveBranchForStock(?int $documentLabBranchId = null): LabBranch
    {
        $id = self::resolveBranchIdForStock($documentLabBranchId);
        if (! $id) {
            throw ValidationException::withMessages([
                'lab_branch' => 'No hay sede de laboratorio disponible para registrar stock. Configurá una sede activa o seleccioná una en el encabezado.',
            ]);
        }

        $branch = LabBranch::query()->active()->whereKey($id)->first();
        if (! $branch) {
            throw ValidationException::withMessages([
                'lab_branch' => 'La sede seleccionada no existe o está inactiva.',
            ]);
        }

        return $branch;
    }
}
