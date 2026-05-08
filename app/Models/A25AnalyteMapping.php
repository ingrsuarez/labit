<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tabla de equivalencias entre el nombre de analito en el equipo Biosystems A25
 * y la determinación (Test) correspondiente en Labit.
 *
 * Columna 4 del worklist (import.txt) usa equipment_analyte_name.
 * Al importar el export del equipo, se resuelve test_id por este mapeo.
 */
class A25AnalyteMapping extends Model
{
    protected $fillable = [
        'equipment_analyte_name',
        'test_id',
        'lab_branch_id',
        'material_type',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function labBranch(): BelongsTo
    {
        return $this->belongsTo(LabBranch::class);
    }

    /**
     * Resuelve el test_id para un nombre de analito del equipo.
     * Primero intenta match por sede; si no hay, cae al global (lab_branch_id null).
     */
    public static function resolveTestId(string $analyteName, ?int $labBranchId = null): ?int
    {
        $query = self::where('equipment_analyte_name', $analyteName);

        if ($labBranchId) {
            $mapping = (clone $query)->where('lab_branch_id', $labBranchId)->first();
            if ($mapping) {
                return $mapping->test_id;
            }
        }

        // Fallback a global
        return $query->whereNull('lab_branch_id')->value('test_id');
    }

    /**
     * Resuelve el equipment_analyte_name para un test_id.
     * Primero intenta match por sede; si no hay, cae al global.
     */
    public static function resolveAnalyteName(int $testId, ?int $labBranchId = null): ?string
    {
        $query = self::where('test_id', $testId);

        if ($labBranchId) {
            $mapping = (clone $query)->where('lab_branch_id', $labBranchId)->first();
            if ($mapping) {
                return $mapping->equipment_analyte_name;
            }
        }

        return $query->whereNull('lab_branch_id')->value('equipment_analyte_name');
    }
}
