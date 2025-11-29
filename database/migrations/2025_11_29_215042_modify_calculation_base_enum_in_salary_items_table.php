<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Modifica el enum calculation_base para incluir opciones para deducciones
     */
    public function up(): void
    {
        // Cambiar el tipo de ENUM a STRING para mayor flexibilidad
        DB::statement("ALTER TABLE salary_items MODIFY COLUMN calculation_base VARCHAR(50) DEFAULT 'subtotal'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE salary_items MODIFY COLUMN calculation_base ENUM('basic', 'basic_antiguedad', 'basic_hours', 'basic_hours_antiguedad', 'subtotal') DEFAULT 'basic'");
    }
};
