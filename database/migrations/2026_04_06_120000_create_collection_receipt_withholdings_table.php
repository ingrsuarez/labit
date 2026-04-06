<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_receipt_withholdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_receipt_id')->constrained()->cascadeOnDelete();
            $table->string('withholding_type', 32);
            $table->string('document_number', 191)->nullable();
            $table->string('regime', 255);
            $table->string('jurisdiction', 191)->nullable();
            $table->string('certificate_number', 191);
            $table->decimal('amount', 14, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('collection_receipt_id');
            $table->index(['withholding_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_receipt_withholdings');
    }
};
