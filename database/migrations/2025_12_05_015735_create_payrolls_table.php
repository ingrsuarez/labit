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
        // Tabla principal de liquidaciones cerradas
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');
            $table->string('period_label'); // "Diciembre 2025"
            
            // Datos del empleado al momento de la liquidación
            $table->string('employee_name');
            $table->string('employee_cuil')->nullable();
            $table->string('category_name')->nullable();
            $table->string('position_name')->nullable();
            $table->integer('antiguedad_years')->default(0);
            $table->date('start_date')->nullable();
            
            // Totales
            $table->decimal('salario_basico', 12, 2);
            $table->decimal('total_haberes', 12, 2);
            $table->decimal('total_remunerativo', 12, 2);
            $table->decimal('total_no_remunerativo', 12, 2)->default(0);
            $table->decimal('total_deducciones', 12, 2);
            $table->decimal('neto_a_cobrar', 12, 2);
            
            // Estado y auditoría
            $table->enum('status', ['borrador', 'liquidado', 'pagado'])->default('borrador');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('liquidated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Índice único: un empleado solo puede tener una liquidación por período
            $table->unique(['employee_id', 'year', 'month']);
        });

        // Tabla de detalle de conceptos de la liquidación
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->onDelete('cascade');
            
            $table->enum('type', ['haber', 'deduccion']);
            $table->string('name'); // Nombre del concepto
            $table->string('percentage')->nullable(); // "15%", "8 hs", etc.
            $table->decimal('amount', 12, 2);
            $table->boolean('is_remunerative')->default(true);
            $table->integer('order')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
    }
};
