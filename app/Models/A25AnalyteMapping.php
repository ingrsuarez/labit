<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Tabla de equivalencias entre el nombre de analito en el equipo Biosystems A25
 * y las determinaciones (Test) correspondientes en Labit.
 *
 * Un analito del equipo puede mapearse a UNA O MÁS determinaciones de Labit.
 * Al importar resultados, el valor se aplica a TODAS las determinaciones
 * mapeadas que estén presentes en el protocolo.
 */
class A25AnalyteMapping extends Model
{
    protected $fillable = [
        'equipment_analyte_name',
        'lab_branch_id',
        'material_type',
    ];

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(
            Test::class,
            'a25_analyte_mapping_tests',
            'a25_analyte_mapping_id',
            'test_id'
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function labBranch(): BelongsTo
    {
        return $this->belongsTo(LabBranch::class);
    }

    // ─── Resolvers estáticos ──────────────────────────────────────────────────

    /**
     * Devuelve todos los test_ids mapeados para un analito del equipo.
     * Primero intenta match por sede; si no hay, cae al global (lab_branch_id null).
     *
     * @return list<int>
     */
    public static function resolveTestIds(string $analyteName, ?int $labBranchId = null): array
    {
        $mapping = self::resolveMapping($analyteName, $labBranchId);

        if (! $mapping) {
            return [];
        }

        return $mapping->tests()->orderByPivot('sort_order')->pluck('tests.id')->all();
    }

    /**
     * Devuelve el primer test_id mapeado (compatibilidad con código que espera un único ID).
     */
    public static function resolveTestId(string $analyteName, ?int $labBranchId = null): ?int
    {
        $ids = self::resolveTestIds($analyteName, $labBranchId);

        return $ids[0] ?? null;
    }

    /**
     * Resuelve el equipment_analyte_name para un test_id dado.
     * Busca en el pivot por test_id.
     * Primero intenta match por sede; si no hay, cae al global.
     */
    public static function resolveAnalyteName(int $testId, ?int $labBranchId = null): ?string
    {
        $query = self::whereHas('tests', fn ($q) => $q->where('tests.id', $testId));

        if ($labBranchId) {
            $mapping = (clone $query)->where('lab_branch_id', $labBranchId)->first();
            if ($mapping) {
                return $mapping->equipment_analyte_name;
            }
        }

        return $query->whereNull('lab_branch_id')->value('equipment_analyte_name');
    }

    /**
     * Devuelve el material_type para un test_id dado.
     */
    public static function resolveMaterialType(int $testId, ?int $labBranchId = null): ?string
    {
        $query = self::whereHas('tests', fn ($q) => $q->where('tests.id', $testId));

        if ($labBranchId) {
            $mapping = (clone $query)->where('lab_branch_id', $labBranchId)->first();
            if ($mapping) {
                return $mapping->material_type ?: 'SER';
            }
        }

        return $query->whereNull('lab_branch_id')->value('material_type') ?: 'SER';
    }

    // ─── Helpers privados ─────────────────────────────────────────────────────

    private static function resolveMapping(string $analyteName, ?int $labBranchId): ?self
    {
        $query = self::where('equipment_analyte_name', $analyteName);

        if ($labBranchId) {
            $mapping = (clone $query)->where('lab_branch_id', $labBranchId)->first();
            if ($mapping) {
                return $mapping;
            }
        }

        return $query->whereNull('lab_branch_id')->first();
    }
}
