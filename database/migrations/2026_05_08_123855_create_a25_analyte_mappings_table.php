<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a25_analyte_mappings', function (Blueprint $table) {
            $table->id();
            // Nombre exacto del analito como aparece en los archivos del equipo A25
            $table->string('equipment_analyte_name');
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            // Sede opcional: si es null, aplica globalmente
            $table->foreignId('lab_branch_id')->nullable()->constrained('lab_branches')->nullOnDelete();
            // Tipo de material en el equipo (ej. SER) — informativo, para validación opcional
            $table->string('material_type', 20)->default('SER');
            $table->timestamps();

            // Unicidad: un mismo nombre de equipo no puede mapearse dos veces en el mismo ámbito (sede o global)
            $table->unique(['equipment_analyte_name', 'lab_branch_id'], 'a25_unique_analyte_per_branch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('a25_analyte_mappings');
    }
};
