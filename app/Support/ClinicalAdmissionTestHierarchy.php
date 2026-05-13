<?php

namespace App\Support;

use App\Models\Admission;
use App\Models\AdmissionTest;

/**
 * Jerarquía padre-hijo de determinaciones en un protocolo clínico (misma lógica que {@see resources/views/lab/admissions/show.blade.php}).
 */
final class ClinicalAdmissionTestHierarchy
{
    /**
     * @return array{parentMap: array<int, list<int>>, childOf: array<int, int>}
     */
    public static function parentChildMaps(Admission $admission): array
    {
        $admission->loadMissing(['admissionTests.test.parentTests']);

        $allProtocolTestIds = $admission->admissionTests->pluck('test_id')->all();

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

        return ['parentMap' => $parentMap, 'childOf' => $childOf];
    }

    /**
     * Determinación hoja bajo un padre en el protocolo (no es sub-padre con hijos en el protocolo).
     */
    public static function isProtocolLeafChild(Admission $admission, AdmissionTest $at): bool
    {
        $maps = self::parentChildMaps($admission);
        $tid = $at->test_id;

        return isset($maps['childOf'][$tid]) && ! isset($maps['parentMap'][$tid]);
    }

    /**
     * Fila intermedia con hijos en el protocolo (no se elimina en cascada en esta versión).
     */
    public static function isProtocolSubParent(Admission $admission, AdmissionTest $at): bool
    {
        $maps = self::parentChildMaps($admission);
        $tid = $at->test_id;

        return isset($maps['childOf'][$tid]) && isset($maps['parentMap'][$tid]);
    }
}
