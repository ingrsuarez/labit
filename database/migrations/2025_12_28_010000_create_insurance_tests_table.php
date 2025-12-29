<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla de nomenclador: precio de cada práctica por obra social
     */
    public function up(): void
    {
        Schema::create('insurance_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_id')->constrained('insurances')->onDelete('cascade');
            $table->foreignId('test_id')->constrained('tests')->onDelete('cascade');
            $table->decimal('nbu_units', 10, 2)->default(1.00)->comment('Unidades NBU de la práctica');
            $table->decimal('price', 12, 2)->nullable()->comment('Precio calculado (NBU × valor OS)');
            $table->boolean('requires_authorization')->default(false)->comment('Requiere autorización previa');
            $table->decimal('copago', 10, 2)->default(0)->comment('Copago que paga el paciente');
            $table->string('observations')->nullable();
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['insurance_id', 'test_id']);
        });

        // Agregar campo nbu_value a insurances para el valor de 1 NBU
        Schema::table('insurances', function (Blueprint $table) {
            $table->decimal('nbu_value', 12, 2)->nullable()->after('nbu')->comment('Valor monetario de 1 NBU');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_tests');
        
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropColumn('nbu_value');
        });
    }
};

