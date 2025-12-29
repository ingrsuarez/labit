<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Actualizar tabla admissions para el nuevo flujo de pacientes
     */
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            // Agregar relación con paciente
            $table->foreignId('patient_id')->nullable()->after('id')->constrained('patients')->onDelete('set null');
            
            // Número de protocolo único
            $table->string('protocol_number')->nullable()->after('number');
            
            // Número de afiliado del paciente en la obra social
            $table->string('affiliate_number')->nullable()->after('insurance');
            
            // Totales calculados
            $table->decimal('total_insurance', 12, 2)->default(0)->after('insurance_price')->comment('Total a pagar por la obra social');
            $table->decimal('total_patient', 12, 2)->default(0)->after('total_insurance')->comment('Total a pagar por el paciente');
            $table->decimal('total_copago', 12, 2)->default(0)->after('total_patient')->comment('Total de copagos');
            
            // Médico solicitante
            $table->string('requesting_doctor')->nullable()->after('applicant');
            
            // Índice para búsquedas
            $table->index('protocol_number');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn([
                'patient_id',
                'protocol_number',
                'affiliate_number',
                'total_insurance',
                'total_patient',
                'total_copago',
                'requesting_doctor'
            ]);
            $table->dropIndex(['protocol_number']);
            $table->dropIndex(['date']);
        });
    }
};

