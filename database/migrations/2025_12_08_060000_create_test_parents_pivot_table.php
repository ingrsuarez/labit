<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea una tabla pivote para permitir que un test hijo
     * pertenezca a múltiples tests padres.
     */
    public function up(): void
    {
        Schema::create('test_parents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_test_id')->comment('ID del test padre');
            $table->unsignedBigInteger('child_test_id')->comment('ID del test hijo');
            $table->integer('order')->default(0)->comment('Orden del hijo dentro del padre');
            $table->timestamps();

            // Claves foráneas
            $table->foreign('parent_test_id')->references('id')->on('tests')->onDelete('cascade');
            $table->foreign('child_test_id')->references('id')->on('tests')->onDelete('cascade');

            // Evitar duplicados
            $table->unique(['parent_test_id', 'child_test_id']);
            
            // Índices para consultas
            $table->index('parent_test_id');
            $table->index('child_test_id');
        });

        // Migrar datos existentes de la columna parent a la nueva tabla pivote
        $testsWithParent = DB::table('tests')->whereNotNull('parent')->get();
        
        foreach ($testsWithParent as $test) {
            DB::table('test_parents')->insert([
                'parent_test_id' => $test->parent,
                'child_test_id' => $test->id,
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_parents');
    }
};
