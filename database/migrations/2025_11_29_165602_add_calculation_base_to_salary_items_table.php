<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Bases de cálculo:
     * - basic: solo sueldo básico
     * - basic_antiguedad: básico + antigüedad
     * - basic_hours: básico + horas extras
     * - basic_hours_antiguedad: básico + horas extras + antigüedad
     * - subtotal: sobre el subtotal (para deducciones)
     */
    public function up(): void
    {
        Schema::table('salary_items', function (Blueprint $table) {
            $table->enum('calculation_base', [
                'basic',
                'basic_antiguedad', 
                'basic_hours',
                'basic_hours_antiguedad',
                'subtotal'
            ])->default('basic_antiguedad')->after('calculation_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_items', function (Blueprint $table) {
            $table->dropColumn('calculation_base');
        });
    }
};
