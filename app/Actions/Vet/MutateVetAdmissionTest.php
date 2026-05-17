<?php

namespace App\Actions\Vet;

use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use App\Support\VetAdmissionTestDisplayOrder;
use Illuminate\Support\Facades\DB;

class MutateVetAdmissionTest
{
    /**
     * @return array{ok: true, message: string}|array{ok: false, message: string}
     */
    public function validate(VetAdmission $vetAdmission, VetAdmissionTest $vetAdmissionTest): array
    {
        if ($vetAdmissionTest->vet_admission_id !== $vetAdmission->id) {
            abort(404);
        }

        if (! $vetAdmissionTest->hasResult()) {
            return ['ok' => false, 'message' => 'No se puede validar sin resultado.'];
        }

        $vetAdmissionTest->update([
            'is_validated' => true,
            'validated_by' => auth()->id(),
            'validated_at' => now(),
            'status' => $this->validatedStatusValue(),
        ]);

        $vetAdmissionTest->loadMissing('test');
        $vetAdmission->logAudit('validated', 'Validó práctica '.$vetAdmissionTest->test->name.' en protocolo veterinario Nº '.$vetAdmission->protocol_number);

        $this->syncVetAdmission($vetAdmission);

        return ['ok' => true, 'message' => 'Práctica validada.'];
    }

    /**
     * @return array{ok: true, message: string}
     */
    public function unvalidate(VetAdmission $vetAdmission, VetAdmissionTest $vetAdmissionTest): array
    {
        if ($vetAdmissionTest->vet_admission_id !== $vetAdmission->id) {
            abort(404);
        }

        $testName = $vetAdmissionTest->test->name ?? 'Desconocida';

        $vetAdmissionTest->update([
            'is_validated' => false,
            'validated_by' => null,
            'validated_at' => null,
            'status' => $vetAdmissionTest->hasResult() ? 'completed' : 'pending',
            'is_ratified' => false,
            'ratified_at' => null,
            'ratified_by' => null,
        ]);

        $vetAdmission->logAudit('unvalidated', 'Desvalidó práctica '.$testName.' en protocolo veterinario Nº '.$vetAdmission->protocol_number);

        $this->syncVetAdmission($vetAdmission);

        return ['ok' => true, 'message' => 'Validación removida.'];
    }

    /**
     * @return array{ok: true, message: string}|array{ok: false, message: string}
     */
    public function remove(VetAdmission $vetAdmission, VetAdmissionTest $vetAdmissionTest): array
    {
        if ($vetAdmissionTest->vet_admission_id !== $vetAdmission->id) {
            abort(404);
        }

        if (auth()->user()->hasRole('recepcion-lab')) {
            if ($vetAdmissionTest->status !== 'pending') {
                return ['ok' => false, 'message' => 'No se puede eliminar una práctica en proceso o validada.'];
            }
        }

        if ($vetAdmissionTest->is_validated) {
            return ['ok' => false, 'message' => 'No se puede quitar una práctica ya validada.'];
        }

        if (VetAdmissionTestDisplayOrder::isProtocolSubParent($vetAdmission, $vetAdmissionTest)) {
            return ['ok' => false, 'message' => 'No se puede eliminar un grupo intermedio. Quite solo determinaciones hoja sin resultado.'];
        }

        if (VetAdmissionTestDisplayOrder::isProtocolLeafChild($vetAdmission, $vetAdmissionTest)) {
            if ($vetAdmissionTest->hasResult() || $vetAdmissionTest->is_ratified) {
                return ['ok' => false, 'message' => 'No se puede eliminar esta determinación: tiene resultado o está ratificada.'];
            }

            $testName = $vetAdmissionTest->test->name ?? 'Desconocida';
            $removedPrice = (float) $vetAdmissionTest->price;
            $vetAdmissionTest->delete();
            $vetAdmission->update(['total_price' => max(0, (float) $vetAdmission->total_price - $removedPrice)]);
            $this->syncVetAdmission($vetAdmission);
            $vetAdmission->logAudit('test_removed', 'Eliminó determinación hoja '.$testName.' del protocolo veterinario Nº '.$vetAdmission->protocol_number);

            return ['ok' => true, 'message' => 'Determinación eliminada del protocolo (el grupo permanece).'];
        }

        $test = $vetAdmissionTest->test;
        $testName = $test->name ?? 'Desconocida';
        $removedPrice = (float) $vetAdmissionTest->price;
        $removedCount = 1;

        $vetAdmissionTest->delete();

        if ($test) {
            $children = $test->getAllChildren(false);
            $childTestIds = $children->pluck('id')->toArray();

            if (! empty($childTestIds)) {
                $childVats = VetAdmissionTest::where('vet_admission_id', $vetAdmission->id)
                    ->whereIn('test_id', $childTestIds)
                    ->where('is_validated', false)
                    ->get();

                foreach ($childVats as $childVat) {
                    $removedPrice += (float) $childVat->price;
                    $removedCount++;
                    $childVat->delete();
                }
            }
        }

        $vetAdmission->update(['total_price' => max(0, (float) $vetAdmission->total_price - $removedPrice)]);
        $this->syncVetAdmission($vetAdmission);

        $vetAdmission->logAudit('test_removed', 'Eliminó práctica '.$testName.' del protocolo veterinario Nº '.$vetAdmission->protocol_number);

        $msg = 'Práctica eliminada del protocolo.';
        if ($removedCount > 1) {
            $msg = "Se eliminaron {$removedCount} prácticas (padre + hijos) del protocolo.";
        }

        return ['ok' => true, 'message' => $msg];
    }

    private function syncVetAdmission(VetAdmission $vetAdmission): void
    {
        $vetAdmission->unsetRelation('vetTests');
        $vetAdmission->load(['vetTests.test.childTests', 'vetTests.test.children']);
        $vetAdmission->update(['status' => $vetAdmission->calculated_status]);
    }

    private function validatedStatusValue(): string
    {
        return DB::getDriverName() === 'mysql' ? 'validated' : 'completed';
    }
}
