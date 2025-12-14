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
        Schema::create('non_conformities', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Código único NC-YYYY-XXX');
            $table->unsignedBigInteger('employee_id')->comment('Empleado involucrado');
            $table->unsignedBigInteger('reported_by')->comment('Usuario que reporta');
            $table->date('date')->comment('Fecha del incidente');
            $table->enum('type', ['procedimiento', 'capacitacion', 'seguridad', 'calidad', 'otro'])->default('procedimiento');
            $table->enum('severity', ['leve', 'moderada', 'grave'])->default('leve');
            $table->text('description')->comment('Descripción del incidente');
            $table->string('procedure_name')->nullable()->comment('Nombre del procedimiento incumplido');
            $table->string('training_name')->nullable()->comment('Nombre de la capacitación relacionada');
            $table->text('corrective_action')->nullable()->comment('Acción correctiva tomada');
            $table->text('preventive_action')->nullable()->comment('Acción preventiva propuesta');
            $table->enum('status', ['abierta', 'en_proceso', 'cerrada'])->default('abierta');
            $table->datetime('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->json('attachments')->nullable()->comment('Archivos adjuntos');
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('reported_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('employee_id');
            $table->index('status');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_conformities');
    }
};
