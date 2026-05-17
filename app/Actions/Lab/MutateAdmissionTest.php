<?php

namespace App\Actions\Lab;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Support\ClinicalAdmissionTestHierarchy;

class MutateAdmissionTest
{
    /**
     * @return array{ok: true, message: string}|array{ok: false, message: string}
     */
    public function validate(Admission $admission, AdmissionTest $test): array
    {
        if ($test->admission_id !== $admission->id) {
            abort(404);
        }

        if (! $test->hasResult()) {
            return ['ok' => false, 'message' => 'No se puede validar una práctica sin resultado.'];
        }

        $test->update([
            'is_validated' => true,
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        $test->loadMissing('test');
        $admission->logAudit('validated', 'Validó práctica '.$test->test->name.' en admisión Nº '.$admission->protocol_number);

        $this->syncAdmission($admission);

        return ['ok' => true, 'message' => 'Práctica validada correctamente.'];
    }

    /**
     * @return array{ok: true, message: string}
     */
    public function unvalidate(Admission $admission, AdmissionTest $test): array
    {
        if ($test->admission_id !== $admission->id) {
            abort(404);
        }

        $test->update([
            'is_validated' => false,
            'validated_by' => null,
            'validated_at' => null,
            'is_ratified' => false,
            'ratified_at' => null,
            'ratified_by' => null,
        ]);

        $test->loadMissing('test');
        $admission->logAudit('unvalidated', 'Desvalidó práctica '.$test->test->name.' en admisión Nº '.$admission->protocol_number);

        $this->syncAdmission($admission);

        return ['ok' => true, 'message' => 'Validación removida.'];
    }

    /**
     * @return array{ok: true, message: string}|array{ok: false, message: string}
     */
    public function remove(Admission $admission, AdmissionTest $test): array
    {
        if ($test->admission_id !== $admission->id) {
            abort(404);
        }

        if (auth()->user()->hasRole('recepcion-lab')) {
            if ($test->is_validated || $test->hasResult()) {
                return ['ok' => false, 'message' => 'No se puede eliminar una práctica en proceso o validada.'];
            }
        }

        if (ClinicalAdmissionTestHierarchy::isProtocolSubParent($admission, $test)) {
            return ['ok' => false, 'message' => 'No se puede eliminar un grupo intermedio. Quite solo determinaciones hoja sin resultado.'];
        }

        if (ClinicalAdmissionTestHierarchy::isProtocolLeafChild($admission, $test)) {
            if ($test->hasResult() || $test->is_validated || $test->is_ratified) {
                return ['ok' => false, 'message' => 'No se puede eliminar esta determinación: tiene resultado, está validada o ratificada.'];
            }

            $test->loadMissing('test');
            $detLabel = $test->test
                ? trim(($test->test->code ? $test->test->code.' — ' : '').$test->test->name)
                : 'Desconocida';
            $test->delete();
            $this->syncAdmission($admission);
            $admission->logAudit('test_removed', 'Eliminó determinación hoja '.$detLabel.' de la admisión Nº '.$admission->protocol_number);

            return ['ok' => true, 'message' => 'Determinación eliminada del protocolo (el grupo permanece).'];
        }

        if ($test->price > 0) {
            $parentTest = $test->test;
            $children = $parentTest->getAllChildren(false);

            foreach ($children as $childTest) {
                AdmissionTest::where('admission_id', $admission->id)
                    ->where('test_id', $childTest->id)
                    ->delete();
            }
        }

        $test->loadMissing('test');
        $testName = $test->test->name ?? 'Desconocida';
        $test->delete();
        $this->syncAdmission($admission);
        $admission->logAudit('test_removed', 'Eliminó práctica '.$testName.' de la admisión Nº '.$admission->protocol_number);

        return ['ok' => true, 'message' => 'Práctica eliminada correctamente.'];
    }

    private function syncAdmission(Admission $admission): void
    {
        $admission->load(['admissionTests.test.childTests', 'admissionTests.test.children']);
        $admission->calculateTotals();
        $admission->syncWorkStatusFromTests();
    }
}
