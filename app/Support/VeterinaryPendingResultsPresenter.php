<?php

namespace App\Support;

use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;

/**
 * Texto «Determinaciones pendientes» para protocolos veterinarios, alineado a {@see resources/views/vet/admissions/show.blade.php}.
 */
final class VeterinaryPendingResultsPresenter
{
    /**
     * @return array{parentMap: array<int, list<int>>, childOf: array<int, int>}
     */
    public static function parentChildMaps(VetAdmission $admission): array
    {
        $admission->loadMissing(['vetTests.test.parentTests']);

        $allProtocolTestIds = $admission->vetTests->pluck('test_id')->all();

        $parentMap = [];
        $childOf = [];

        foreach ($admission->vetTests as $vt) {
            if (! $vt->test) {
                continue;
            }
            $parentIds = $vt->test->parentTests->pluck('id')->toArray();
            if ($vt->test->parent) {
                $parentIds[] = $vt->test->parent;
                $parentIds = array_unique($parentIds);
            }
            $parentsInProtocol = array_intersect($parentIds, $allProtocolTestIds);

            if (count($parentsInProtocol) > 0) {
                $parentId = (int) reset($parentsInProtocol);
                $childOf[$vt->test_id] = $parentId;
                if (! isset($parentMap[$parentId])) {
                    $parentMap[$parentId] = [];
                }
                $parentMap[$parentId][] = (int) $vt->test_id;
            }
        }

        return ['parentMap' => $parentMap, 'childOf' => $childOf];
    }

    /**
     * Cadena tipo "Grupo - Glucosa" o vacío si no hay determinaciones hoja sin resultado visibles para el rol.
     */
    public static function pendingDeterminationsLabel(VetAdmission $admission, bool $isRecepcionLab): string
    {
        $admission->loadMissing(['vetTests.test.parentTests']);

        $ordered = VetAdmissionTestDisplayOrder::orderedEntries($admission, false);
        $childOf = self::parentChildMaps($admission)['childOf'];

        $itemsByTestId = [];
        foreach ($admission->vetTests as $vt) {
            if ($vt->test) {
                $itemsByTestId[$vt->test_id] = $vt;
            }
        }

        $labels = [];
        $seen = [];

        foreach ($ordered as $entry) {
            /** @var VetAdmissionTest $vt */
            $vt = $entry['vt'];
            $isParent = $entry['isParent'];
            $isChild = $entry['isChild'];

            if ($isRecepcionLab && $isChild && ! $isParent) {
                continue;
            }

            if ($isParent) {
                continue;
            }

            if ($vt->hasResult()) {
                continue;
            }

            $tid = $vt->test_id;
            $parentId = $childOf[$tid] ?? null;

            if ($parentId !== null && isset($itemsByTestId[$parentId])) {
                $parentVt = $itemsByTestId[$parentId];
                $name = $parentVt->test?->name ?? '';
                $key = 'p:'.$parentId;
            } else {
                $name = $vt->test?->name ?? '';
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
}
