<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('total', 14, 2)->default(0);
            $table->enum('status', ['borrador', 'confirmado', 'anulado'])->default('borrador');
            $table->enum('payment_method', ['transferencia', 'cheque', 'efectivo', 'tarjeta', 'deposito'])->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('collection_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_receipt_items');
        Schema::dropIfExists('collection_receipts');
    }
};
