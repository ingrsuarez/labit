<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Eliminar registros duplicados (mantener el de menor ID)
        // Un duplicado se define como: mismo employee_id, type, start, end
        DB::statement("
            DELETE l1 FROM leaves l1
            INNER JOIN leaves l2
            WHERE l1.id > l2.id
              AND l1.employee_id = l2.employee_id
              AND l1.type = l2.type
              AND l1.start = l2.start
              AND l1.end = l2.end
        ");

        // 2. Agregar índice único para prevenir duplicados futuros
        Schema::table('leaves', function (Blueprint $table) {
            $table->unique(
                ['employee_id', 'type', 'start', 'end'],
                'leaves_unique_employee_type_dates'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropUnique('leaves_unique_employee_type_dates');
        });
    }
};
