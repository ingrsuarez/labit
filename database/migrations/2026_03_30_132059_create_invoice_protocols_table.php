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
        Schema::create('invoice_protocols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->string('protocol_type');
            $table->unsignedBigInteger('protocol_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['protocol_type', 'protocol_id']);
            $table->unique(['sales_invoice_id', 'protocol_type', 'protocol_id'], 'inv_proto_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_protocols');
    }
};
