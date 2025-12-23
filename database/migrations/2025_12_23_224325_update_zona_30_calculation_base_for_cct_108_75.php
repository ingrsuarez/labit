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
        // Actualizar Zona 30% para usar la nueva base que incluye Adicional Título
        DB::table('salary_items')
            ->where('code', 'Z30')
            ->update([
                'calculation_base' => 'basic_antiguedad_titulo',
                'order' => 10, // Se calcula después de antigüedad y adicional título
            ]);

        // Marcar Adicional Título para que se incluya en la base de antigüedad y zona
        DB::table('salary_items')
            ->where('code', 'ADTIT')
            ->update([
                'includes_in_antiguedad_base' => true,
                'order' => 2, // Se calcula antes de antigüedad y zona
            ]);

        // Ajustar orden de Puesto Jerárquico (se calcula después de zona)
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
