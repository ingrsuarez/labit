<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            // Identificador de muestra para el equipo A25 (ej. C000638S).
            // Es un código que el operador asigna al preparar las muestras para el equipo;
            // no coincide necesariamente con el protocol_number de Labit.
            $table->string('external_equipment_sample_id', 50)->nullable()->after('protocol_number');
            $table->index('external_equipment_sample_id', 'admissions_ext_sample_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropIndex('admissions_ext_sample_id_idx');
            $table->dropColumn('external_equipment_sample_id');
        });
    }
};
