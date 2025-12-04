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
        Schema::table('salary_items', function (Blueprint $table) {
            // Si está marcado, este concepto se suma a la base para calcular antigüedad
            $table->boolean('includes_in_antiguedad_base')->default(false)->after('requires_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_items', function (Blueprint $table) {
            $table->dropColumn('includes_in_antiguedad_base');
        });
    }
};
