<?php

namespace App\Support;

use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Illuminate\Support\Collection;

/**
 * Orden de filas de determinaciones en protocolos veterinarios (jerarquía + sort_order),
 * alineado al informe PDF. Modo "validated" replica el árbol del PDF; modo "all" incluye
 * todas las líneas para carga/validación en pantalla.
 */
class VetAdmissionTestDisplayOrder
{
    /**
     * @param  bool  $hierarchyFromValidatedOnly  true = mismo criterio que el PDF histórico (solo validadas para aristas); false = todas las líneas del protocolo
     * @return Collection<int, array{vt: VetAdmissionTest, level: int, isParent: bool, isSubParent: bool, isChild: bool}>
     */
    public static function orderedEntries(VetAdmission $admission, bool $hierarchyFromValidatedOnly = false): Collection
    {
        $admission->loadMissing(['vetTests.test.parentTests']);

        $allVetTests = $admission->vetTests;
        $allProtocolTestIds = $allVetTests->pluck('test_id')->all();

        $itemsByTestId = [];
        foreach ($allVetTests as $vt) {
            $itemsByTestId[$vt->test_id] = $vt;
        }

        $sourceForEdges = $hierarchyFromValidatedOnly
            ? $allVetTests->where('is_validated', true)
            : $allVetTests;

        $parentMap = [];
        $childOf = [];

        foreach ($sourceForEdges as $vt) {
            if (! $vt->test) {
                continue;
            }
            $parentIds = $vt->test->parentTests->pluck('id')->all();
            if ($vt->test->parent) {
                $parentIds[] = (int) $vt->test->parent;
                $parentIds = array_unique($parentIds);
            }
            $parentsInProtocol = array_values(array_intersect($parentIds, $allProtocolTestIds));

            if (count($parentsInProtocol) > 0) {
                $parentId = (int) reset($parentsInProtocol);
                $childOf[$vt->test_id] = $parentId;
                if (! isset($parentMap[$parentId])) {
                    $parentMap[$parentId] = [];
                }
                $parentMap[$parentId][] = (int) $vt->test_id;
            }
        }

        $changed = true;
        while ($changed) {
            $changed = false;
            foreach (array_keys($parentMap) as $pId) {
                if (isset($childOf[$pId])) {
                    continue;
                }
                if (! isset($itemsByTestId[$pId])) {
                    continue;
                }
                $pTest = $itemsByTestId[$pId]->test;
                if (! $pTest) {
                    continue;
                }
                $ancestorIds = $pTest->parentTests->pluck('id')->all();
                if ($pTest->parent) {
                    $ancestorIds[] = (int) $pTest->parent;
                    $ancestorIds = array_unique($ancestorIds);
                }
                $ancestorsInProtocol = array_values(array_intersect($ancestorIds, $allProtocolTestIds));
                if (count($ancestorsInProtocol) > 0) {
                    $ancestorId = (int) reset($ancestorsInProtocol);
                    $childOf[$pId] = $ancestorId;
                    if (! isset($parentMap[$ancestorId])) {
                        $parentMap[$ancestorId] = [];
                    }
                    if (! in_array($pId, $parentMap[$ancestorId], true)) {
                        $parentMap[$ancestorId][] = $pId;
                    }
                    $changed = true;
                }
            }
        }

        $isSubParentMap = [];
        foreach (array_keys($parentMap) as $testId) {
            if (isset($childOf[$testId])) {
                $isSubParentMap[$testId] = true;
            }
        }

        foreach ($parentMap as $parentId => &$pChildren) {
            usort($pChildren, fn ($a, $b) => self::compareTestIds($a, $b, $itemsByTestId));
        }
        unset($pChildren);

        $roots = [];
        foreach ($sourceForEdges as $vt) {
            if (! isset($childOf[$vt->test_id])) {
                $roots[] = (int) $vt->test_id;
            }
        }
        $roots = array_values(array_unique($roots));

        foreach ($parentMap as $parentId => $children) {
            if (! isset($childOf[$parentId]) && ! in_array($parentId, $roots, true)) {
                $roots[] = $parentId;
            }
        }

        usort($roots, fn ($a, $b) => self::compareTestIds($a, $b, $itemsByTestId));

        $orderedTests = collect();

        $addWithChildren = function ($testId, $level) use (
            &$addWithChildren,
            $parentMap,
            $isSubParentMap,
            $itemsByTestId,
            &$orderedTests
        ) {
            if (! isset($itemsByTestId[$testId])) {
                return;
            }

            $isParent = isset($parentMap[$testId]);
            $isSub = isset($isSubParentMap[$testId]);

            $orderedTests->push([
                'vt' => $itemsByTestId[$testId],
                'level' => $level,
                'isParent' => $isParent,
                'isSubParent' => $isSub,
                'isChild' => $level > 0,
            ]);

            if ($isParent) {
                foreach ($parentMap[$testId] as $childId) {
                    $addWithChildren($childId, $level + 1);
                }
            }
        };

        foreach ($roots as $rootId) {
            $addWithChildren($rootId, 0);
        }

        $visited = $orderedTests->pluck('vt')->map(fn (VetAdmissionTest $vt) => $vt->test_id)->all();
        $orphans = $allVetTests->filter(fn (VetAdmissionTest $vt) => ! in_array($vt->test_id, $visited, true))
            ->sort(fn (VetAdmissionTest $a, VetAdmissionTest $b) => self::compareTestIds($a->test_id, $b->test_id, $itemsByTestId))
            ->values();

        foreach ($orphans as $vt) {
            $orderedTests->push([
                'vt' => $vt,
                'level' => 0,
                'isParent' => false,
                'isSubParent' => false,
                'isChild' => false,
            ]);
        }

        return $orderedTests;
    }

    /**
     * @param  array<int, VetAdmissionTest>  $itemsByTestId
     */
    private static function compareTestIds(int $a, int $b, array $itemsByTestId): int
    {
        $ta = $itemsByTestId[$a]->test ?? null;
        $tb = $itemsByTestId[$b]->test ?? null;
        $sortA = $ta !== null ? (int) ($ta->sort_order ?? 0) : 0;
        $sortB = $tb !== null ? (int) ($tb->sort_order ?? 0) : 0;
        $sort = $sortA <=> $sortB;
        if ($sort !== 0) {
            return $sort;
        }

        $ca = $ta !== null ? (string) ($ta->code ?? '') : '';
        $cb = $tb !== null ? (string) ($tb->code ?? '') : '';
        $cmp = strcmp($ca, $cb);

        return $cmp !== 0 ? $cmp : ($a <=> $b);
    }
}
