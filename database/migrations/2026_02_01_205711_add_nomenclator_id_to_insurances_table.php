<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega el campo nomenclator_id para relacionar cada obra social con su nomenclador de facturación.
     */
    public function up(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->unsignedBigInteger('nomenclator_id')->nullable()->after('nbu_value');
            
            // Llave foránea a la misma tabla (un nomenclador es también un insurance con type='nomenclador')
            $table->foreign('nomenclator_id')
                  ->references('id')
                  ->on('insurances')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropForeign(['nomenclator_id']);
            $table->dropColumn('nomenclator_id');
        });
    }
};
