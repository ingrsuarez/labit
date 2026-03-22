<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Actualización según CCT 108/75 FATSA y LCT Art. 105
 * 
 * El 30% de zona desfavorable se calcula sobre:
 * - Sueldo básico
 * - Antigüedad
 * - Adicional Título (si es fijo)
 * 
 * Esta migración actualiza:
 * 1. Zona 30% para usar la nueva base 'basic_antiguedad_titulo'
 * 2. Adicional Título para marcarlo como parte de la base de antigüedad/zona
 * 3. Ajusta el orden de cálculo para respetar las dependencias
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('salary_items', 'calculation_base')) {
            return;
        }

        DB::table('salary_items')
            ->where('code', 'Z30')
            ->update([
                'calculation_base' => 'basic_antiguedad_titulo',
                'order' => 10,
            ]);

        DB::table('salary_items')
            ->where('code', 'ADTIT')
            ->update([
                'includes_in_antiguedad_base' => true,
                'order' => 2,
            ]);

        DB::table('salary_items')
            ->where('code', 'PJER')
            ->update([
                'order' => 20,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir Zona 30% a la base anterior
        DB::table('salary_items')
            ->where('code', 'Z30')
            ->update([
                'calculation_base' => 'basic_antiguedad',
                'order' => 1,
            ]);

        // Quitar marca de Adicional Título
        DB::table('salary_items')
            ->where('code', 'ADTIT')
            ->update([
                'includes_in_antiguedad_base' => false,
                'order' => 3,
            ]);

        // Revertir orden de Puesto Jerárquico
        DB::table('salary_items')
            ->where('code', 'PJER')
            ->update([
                'order' => 2,
            ]);
    }
};
