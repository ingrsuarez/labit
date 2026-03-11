<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('total', 14, 2)->default(0);
            $table->enum('status', ['borrador', 'aprobada', 'pagada', 'anulada'])->default('borrador');
            $table->enum('payment_method', ['transferencia', 'cheque', 'efectivo'])->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_order_items');
        Schema::dropIfExists('payment_orders');
    }
};
