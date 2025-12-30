<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('salary_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Nombre del concepto (ej: "Antigüedad", "Jubilación")
            $table->string('code')->nullable();              // Código interno (ej: "ANT", "JUB")
            $table->enum('type', ['haber', 'deduccion']);    // Tipo: haber (suma) o deduccion (resta)
            $table->enum('calculation_type', ['percentage', 'fixed', 'hours']); // Tipo de cálculo
            $table->decimal('value', 10, 2)->default(0);     // Valor (porcentaje o monto fijo)
            $table->string('base')->default('basic_salary'); // Base de cálculo: basic_salary, subtotal, etc.
            $table->boolean('is_remunerative')->default(true); // Si es remunerativo o no
            $table->boolean('is_active')->default(true);     // Si está activo
            $table->integer('order')->default(0);            // Orden de aparición en el recibo
            $table->text('description')->nullable();         // Descripción opcional
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_items');
    }
};
