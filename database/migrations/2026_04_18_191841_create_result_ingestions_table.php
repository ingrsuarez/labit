<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_ingestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_client_id')->constrained()->cascadeOnDelete();

            $table->string('external_message_id', 100)->nullable();
            $table->string('hl7_control_id', 100);
            $table->string('protocol_number', 50)->index();
            $table->enum('protocol_type', ['clinical', 'sample', 'vet'])->nullable();
            $table->string('equipment_name', 100)->nullable();

            $table->enum('status', ['ingested', 'partial', 'rejected', 'duplicate'])->index();
            $table->json('items_summary');
            $table->string('rejection_reason', 100)->nullable();

            $table->timestamps();

            $table->unique(['api_client_id', 'hl7_control_id'], 'uniq_client_control');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_ingestions');
    }
};
