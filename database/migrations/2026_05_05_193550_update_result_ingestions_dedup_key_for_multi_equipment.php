<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía la clave de deduplicación de result_ingestions para soportar múltiples equipos
 * por protocolo.
 *
 * Antes: UNIQUE(api_client_id, hl7_control_id)
 * Ahora: INDEX(api_client_id, hl7_control_id, equipment_name)   ← no-unique
 *
 * Motivo: LISCOM puede enviar resultados de BC-780 y DIRUI CST240 para el mismo protocolo
 * usando el mismo hl7_control_id (MSH-10 derivado del número de protocolo). Ambos mensajes
 * deben procesarse de forma independiente porque provienen de equipos distintos y cargan
 * determinaciones distintas. El constraint UNIQUE anterior bloqueaba el segundo equipo
 * devolviendo status:duplicate.
 *
 * La idempotencia real (misma retry del mismo equipo) se garantiza en PHP combinando
 * hl7_control_id + equipment_name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_ingestions', function (Blueprint $table) {
            $table->dropUnique('uniq_client_control');
            $table->index(['api_client_id', 'hl7_control_id', 'equipment_name'], 'idx_client_control_equipment');
        });
    }

    public function down(): void
    {
        Schema::table('result_ingestions', function (Blueprint $table) {
            $table->dropIndex('idx_client_control_equipment');
            $table->unique(['api_client_id', 'hl7_control_id'], 'uniq_client_control');
        });
    }
};
