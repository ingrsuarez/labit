<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula el campo `status` de todos los VetAdmission existentes
 * basándose en el estado real de sus determinaciones (vet_admission_tests).
 *
 * Necesario porque antes de v1.67.4 el campo status del protocolo padre
 * nunca se actualizaba al validar/cargar resultados.
 *
 * Lógica (espejo de VetAdmission::getCalculatedStatusAttribute):
 *   - Sin determinaciones           → pending
 *   - Todas validadas               → validated
 *   - Todas completadas o alguna validada → completed
 *   - Resto                         → pending
 */
return new class extends Migration
{
    public function up(): void
    {
        $admissions = DB::table('vet_admissions')->select('id')->get();

        foreach ($admissions as $admission) {
            $tests = DB::table('vet_admission_tests')
                ->where('vet_admission_id', $admission->id)
                ->select('status', 'is_validated')
                ->get();

            $total     = $tests->count();
            $validated = $tests->where('is_validated', 1)->count();
            $completed = $tests->whereIn('status', ['completed', 'validated'])->count();

            if ($total === 0) {
                $status = 'pending';
            } elseif ($validated === $total) {
                $status = 'validated';
            } elseif ($completed === $total || $validated > 0) {
                $status = 'completed';
            } else {
                $status = 'pending';
            }

            DB::table('vet_admissions')
                ->where('id', $admission->id)
                ->update(['status' => $status]);
        }
    }

    public function down(): void
    {
        // No hay forma de revertir un recálculo de datos sin snapshot previo.
    }
};
