<?php

namespace App\Support;

use App\Models\Admission;
use App\Models\AdmissionTest;
use Illuminate\Support\Collection;

/**
 * Texto «Determinaciones pendientes» y detección de filas alineadas a {@see resources/views/lab/admissions/show.blade.php}.
 */
final class ClinicalPendingResultsPresenter
{
    public static function admissionTestCountsForPendingResults(AdmissionTest $at): bool
    {
        if ($at->isRejected()) {
            return false;
        }

        if ($at->paid_by_patient) {
            return true;
        }

        return $at->isAuthorized();
    }

    /**
     * Cadena tipo "Grupo A - Glucosa" o vacío si no hay pendientes visibles para el rol.
     */
    public static function pendingDeterminationsLabel(Admission $admission, bool $isRecepcionLab): string
    {
        $admission->loadMissing([
            'admissionTests.test.parentTests',
            'admissionTests.test.referenceValues',
        ]);

        $ordered = self::orderedHierarchyRows($admission);
        $childOf = ClinicalAdmissionTestHierarchy::parentChildMaps($admission)['childOf'];

        $itemsByTestId = [];
        foreach ($admission->admissionTests as $at) {
            if ($at->test) {
                $itemsByTestId[$at->test_id] = $at;
            }
        }

        $labels = [];
        $seen = [];

        foreach ($ordered as $item) {
            /** @var AdmissionTest $rowAt */
            $rowAt = $item['at'];
            $hasChildren = $item['hasChildren'];
            $isChild = $item['isChild'];

            if ($isRecepcionLab && $isChild && ! $hasChildren) {
                continue;
            }

            if ($hasChildren) {
                continue;
            }

            if (! self::admissionTestCountsForPendingResults($rowAt)) {
                continue;
            }

            if (ProtocolEmptyResultExempt::isExemptAndEmpty($rowAt)) {
                continue;
            }

            if ($rowAt->hasResult()) {
                continue;
            }

            $tid = $rowAt->test_id;
            $parentId = $childOf[$tid] ?? null;

            if ($parentId !== null && isset($itemsByTestId[$parentId])) {
                $parentAt = $itemsByTestId[$parentId];
                $name = $parentAt->test?->name ?? '';
                $key = 'p:'.$parentId;
            } else {
                $name = $rowAt->test?->name ?? '';
                $key = 'o:'.$tid;
            }

            if ($name === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $labels[] = $name;
        }

        return implode(' - ', $labels);
    }

    /**
     * @return Collection<int, array{at: AdmissionTest, level: int, hasChildren: bool, isChild: bool, isSubParent: bool, childCount: int}>
     */
    public static function orderedHierarchyRows(Admission $admission): Collection
    {
        $allProtocolTestIds = $admission->admissionTests->pluck('test_id')->toArray();

        $itemsByTestId = [];
        foreach ($admission->admissionTests as $at) {
            if ($at->test) {
                $itemsByTestId[$at->test_id] = $at;
            }
        }

        $parentMap = [];
        $childOf = [];
        foreach ($admission->admissionTests as $at) {
            if (! $at->test) {
                continue;
            }
            $parentIds = $at->test->parentTests->pluck('id')->toArray();
            if ($at->test->parent) {
                $parentIds[] = $at->test->parent;
                $parentIds = array_unique($parentIds);
            }
            $parentsInProtocol = array_intersect($parentIds, $allProtocolTestIds);

            if (count($parentsInProtocol) > 0) {
                $parentId = (int) reset($parentsInProtocol);
                $childOf[$at->test_id] = $parentId;
                if (! isset($parentMap[$parentId])) {
                    $parentMap[$parentId] = [];
                }
                $parentMap[$parentId][] = (int) $at->test_id;
            }
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
            &$addWithChildren,
            $parentMap,
            $isSubParentMap,
            $itemsByTestId,
            &$orderedItems
        ) {
            if (! isset($itemsByTestId[$testId])) {
                return;
            }
            $isParent = isset($parentMap[$testId]);

            $orderedItems->push([
                'at' => $itemsByTestId[$testId],
                'level' => $level,
                'hasChildren' => $isParent,
                'isChild' => $level > 0,
                'isSubParent' => isset($isSubParentMap[$testId]),
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

        return $orderedItems;
    }
}
