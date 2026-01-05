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
        Schema::create('sample_determinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->constrained('samples')->onDelete('cascade');
            $table->foreignId('test_id')->constrained('tests')->onDelete('restrict');
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->string('result')->nullable(); // Resultado del análisis
            $table->string('unit')->nullable(); // Unidad de medida
            $table->string('reference_value')->nullable(); // Valor de referencia
            $table->string('method')->nullable(); // Método utilizado
            $table->text('observations')->nullable();
            $table->foreignId('analyzed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sample_determinations');
    }
};











