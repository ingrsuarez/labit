<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía la clave de deduplicación de result_ingestions para incluir protocol_number.
 *
 * Antes: INDEX(api_client_id, hl7_control_id, equipment_name)
 * Ahora: INDEX(api_client_id, hl7_control_id, equipment_name, protocol_number)
 *
 * Motivo: LISCOM resetea el hl7_control_id (MSH-10) a '1' en cada nueva sesión/dispatch.
 * Con la clave anterior, si DIRUI CST240 envió resultados para el protocolo A con
 * hl7_control_id='1', cualquier envío posterior de DIRUI para el protocolo B con
 * hl7_control_id='1' era bloqueado como duplicate aunque sea un mensaje completamente
 * diferente (diferente protocolo).
 *
 * Un duplicate real es: mismo equipo + mismo hl7_control_id + mismo protocolo.
 * Equipos o protocolos distintos con el mismo control_id son mensajes distintos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_ingestions', function (Blueprint $table) {
            $table->dropIndex('idx_client_control_equipment');
            $table->index(
                ['api_client_id', 'hl7_control_id', 'equipment_name', 'protocol_number'],
                'idx_client_control_equipment_protocol'
            );
        });
    }

    public function down(): void
    {
        Schema::table('result_ingestions', function (Blueprint $table) {
            $table->dropIndex('idx_client_control_equipment_protocol');
            $table->index(
                ['api_client_id', 'hl7_control_id', 'equipment_name'],
                'idx_client_control_equipment'
            );
        });
    }
};
