<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla pivote para asignar conceptos de sueldo a empleados específicos
     */
    public function up(): void
    {
        Schema::create('employee_salary_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('salary_item_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->decimal('custom_value', 10, 2)->nullable(); // Valor personalizado opcional
            $table->timestamps();
            
            // Índice único para evitar duplicados
            $table->unique(['employee_id', 'salary_item_id']);
        });

        // Agregar campo para indicar si el concepto requiere asignación individual
        Schema::table('salary_items', function (Blueprint $table) {
            $table->boolean('requires_assignment')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_items', function (Blueprint $table) {
            $table->dropColumn('requires_assignment');
        });
        Schema::dropIfExists('employee_salary_item');
    }
};
