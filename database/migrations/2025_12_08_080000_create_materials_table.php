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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Código del material');
            $table->string('name')->comment('Nombre del material');
            $table->text('description')->nullable()->comment('Descripción');
            $table->string('unit')->nullable()->comment('Unidad de medida');
            $table->decimal('stock', 10, 2)->default(0)->comment('Stock actual');
            $table->decimal('min_stock', 10, 2)->default(0)->comment('Stock mínimo');
            $table->boolean('is_active')->default(true)->comment('Activo/Inactivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
