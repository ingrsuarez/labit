<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crear tabla admission_tests con campos completos para autorización
     */
    public function up(): void
    {
        // Primero eliminar la tabla anterior si existe
        Schema::dropIfExists('admission_analyses');
        
        Schema::create('admission_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->foreignId('test_id')->constrained('tests')->onDelete('cascade');
            
            // Precio de la práctica al momento de la admisión
            $table->decimal('price', 12, 2)->default(0)->comment('Precio de la práctica');
            $table->decimal('nbu_units', 10, 2)->default(1)->comment('NBU de la práctica');
            
            // Estado de autorización
            $table->enum('authorization_status', [
                'pending',      // Pendiente de autorización
                'authorized',   // Autorizado por la OS
                'rejected',     // Rechazado por la OS
                'not_required'  // No requiere autorización
            ])->default('pending');
            
            // Quién paga
            $table->boolean('paid_by_patient')->default(false)->comment('True si lo paga el paciente');
            $table->decimal('copago', 10, 2)->default(0)->comment('Copago del paciente');
            
            // Código de autorización de la OS
            $table->string('authorization_code')->nullable();
            
            // Observaciones
            $table->text('observations')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('authorization_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_tests');
        
        // Recrear la tabla original
        Schema::create('admission_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->foreignId('analysis_id')->constrained('tests')->onDelete('cascade');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });
    }
};

