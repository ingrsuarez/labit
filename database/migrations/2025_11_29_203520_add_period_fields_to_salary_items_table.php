<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Campos para controlar cuándo aplica cada concepto:
     * - applies_all_year: true = aplica siempre, false = aplica en períodos específicos
     * - recurrent_month: mes que se repite cada año (1-12), ej: 9 para Día de la Sanidad
     * - specific_month/year: para conceptos puntuales de un solo período
     */
    public function up(): void
    {
        Schema::table('salary_items', function (Blueprint $table) {
            $table->boolean('applies_all_year')->default(true)->after('is_active');
            $table->unsignedTinyInteger('recurrent_month')->nullable()->after('applies_all_year'); // 1-12
            $table->unsignedTinyInteger('specific_month')->nullable()->after('recurrent_month'); // 1-12
            $table->unsignedSmallInteger('specific_year')->nullable()->after('specific_month'); // 2024, 2025, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_items', function (Blueprint $table) {
            $table->dropColumn(['applies_all_year', 'recurrent_month', 'specific_month', 'specific_year']);
        });
    }
};
