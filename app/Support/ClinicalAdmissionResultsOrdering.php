<?php

namespace App\Support;

use App\Models\Admission;
use Illuminate\Support\Collection;

/**
 * Orden jerárquico de filas de resultados en protocolo clínico (misma lógica que show de admisiones).
 */
final class ClinicalAdmissionResultsOrdering
{
    /**
     * @return array{
     *     items: Collection<int, array{at: \App\Models\AdmissionTest, level: int, isParent: bool, isSubParent: bool, isChild: bool, childCount: int}>,
     *     parentMap: array<int, list<int>>,
     *     childOf: array<int, int>,
     *     isSubParentMap: array<int, bool>
     * }
     */
    public static function build(Admission $admission): array
    {
        $admission->loadMissing([
            'admissionTests.test.parentTests',
            'admissionTests.test.referenceValues',
        ]);

        $maps = ClinicalAdmissionTestHierarchy::parentChildMaps($admission);
        $parentMap = $maps['parentMap'];
        $childOf = $maps['childOf'];

        $itemsByTestId = [];
        foreach ($admission->admissionTests as $at) {
            $itemsByTestId[$at->test_id] = $at;
        }

        $isSubParentMap = [];
        foreach ($parentMap as $testId => $children) {
            if (isset($childOf[$testId])) {
                $isSubParentMap[$testId] = true;
            }
        }

        foreach ($parentMap as $parentId => &$childIds) {
            usort($childIds, function ($a, $b) use ($itemsByTestId) {
                $sortA = $itemsByTestId[$a]->test->sort_order ?? 0;
                $sortB = $itemsByTestId[$b]->test->sort_order ?? 0;

                return $sortA <=> $sortB;
            });
        }
        unset($childIds);

        $roots = [];
        foreach ($admission->admissionTests as $at) {
            if (! isset($childOf[$at->test_id]) && ! in_array($at->test_id, $roots, true)) {
                $roots[] = $at->test_id;
            }
        }

        usort($roots, function ($a, $b) use ($itemsByTestId) {
            $sortA = $itemsByTestId[$a]->test->sort_order ?? 0;
            $sortB = $itemsByTestId[$b]->test->sort_order ?? 0;

            return $sortA <=> $sortB;
        });

        $orderedItems = collect();
        $addWithChildren = function ($testId, $level) use (
            &$addWithChildren, $parentMap, $isSubParentMap, $itemsByTestId, &$orderedItems
        ) {
            if (! isset($itemsByTestId[$testId])) {
                return;
            }
            $isParent = isset($parentMap[$testId]);

            $orderedItems->push([
                'at' => $itemsByTestId[$testId],
                'level' => $level,
                'isParent' => $isParent,
                'isSubParent' => isset($isSubParentMap[$testId]),
                'isChild' => $level > 0,
                'childCount' => $isParent ? count($parentMap[$testId]) : 0,
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

        return [
            'items' => $orderedItems,
            'parentMap' => $parentMap,
            'childOf' => $childOf,
            'isSubParentMap' => $isSubParentMap,
        ];
    }
}
