<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campos para cargar resultados y validar prácticas de laboratorio
     */
    public function up(): void
    {
        Schema::table('admission_tests', function (Blueprint $table) {
            // Resultado de la práctica
            $table->string('result')->nullable()->after('observations');
            $table->string('unit')->nullable()->after('result');
            $table->string('reference_value')->nullable()->after('unit');
            
            // Validación
            $table->boolean('is_validated')->default(false)->after('reference_value');
            $table->unsignedBigInteger('validated_by')->nullable()->after('is_validated');
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            
            // Llave foránea al usuario que validó
            $table->foreign('validated_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_tests', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn([
                'result',
                'unit',
                'reference_value',
                'is_validated',
                'validated_by',
                'validated_at',
            ]);
        });
    }
};
