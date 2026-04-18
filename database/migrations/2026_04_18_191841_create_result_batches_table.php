<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained()->cascadeOnDelete();
            $table->uuid('external_batch_id')->index();
            $table->string('source_app', 50)->default('LISCOM');
            $table->unsignedInteger('items_total')->default(0);
            $table->unsignedInteger('items_ingested')->default(0);
            $table->unsignedInteger('items_overwritten')->default(0);
            $table->unsignedInteger('items_rejected')->default(0);
            $table->unsignedInteger('items_duplicate')->default(0);
            $table->json('raw_request')->nullable();
            $table->timestamps();

            $table->unique(['api_client_id', 'external_batch_id'], 'uniq_client_batch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_batches');
    }
};
