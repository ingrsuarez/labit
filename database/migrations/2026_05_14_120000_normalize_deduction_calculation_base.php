<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Las deducciones deben usar bases semánticas de deducción; normalizar filas heredadas del default de haberes.
     */
    public function up(): void
    {
        DB::table('salary_items')
            ->where('type', 'deduccion')
            ->update(['calculation_base' => 'subtotal_remunerativo']);

        $deductionIds = DB::table('salary_items')->where('type', 'deduccion')->pluck('id');
        if ($deductionIds->isNotEmpty()) {
            DB::table('salary_item_base_items')->whereIn('salary_item_id', $deductionIds)->delete();
        }
    }

    /**
     * No se restauran valores previos (irreversible de forma segura).
     */
    public function down(): void
    {
        //
    }
};
