<?php

use App\Models\VetAdmission;
use Illuminate\Database\Migrations\Migration;

/**
 * Re-recalcula el status de todos los VetAdmission usando la lógica corregida
 * de v1.67.5: excluye padres-título (tests sin resultado que tienen hijos)
 * del conteo de validación, para que no bloqueen el estado 'validated'.
 *
 * Reemplaza la migración anterior (2026_05_04_223716_recalculate_vet_admissions_status)
 * que no tenía en cuenta los padres-título.
 */
return new class extends Migration
{
    public function up(): void
    {
        VetAdmission::with(['vetTests.test.childTests'])->chunk(100, function ($admissions) {
            foreach ($admissions as $admission) {
                $admission->update(['status' => $admission->calculated_status]);
            }
        });
    }

    public function down(): void
    {
        // No reversible sin snapshot previo.
    }
};
