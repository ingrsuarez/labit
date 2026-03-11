<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->enum('voucher_type', ['A', 'B', 'C'])->default('B');
            $table->string('point_of_sale')->nullable();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->unsignedBigInteger('admission_id')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('iva_21', 14, 2)->default(0);
            $table->decimal('iva_10_5', 14, 2)->default(0);
            $table->decimal('iva_27', 14, 2)->default(0);
            $table->decimal('percepciones', 14, 2)->default(0);
            $table->decimal('otros_impuestos', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('amount_collected', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->enum('status', ['pendiente', 'parcialmente_cobrada', 'cobrada', 'anulada'])->default('pendiente');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedBigInteger('test_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('iva_rate', 5, 2)->default(21);
            $table->decimal('iva_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
    }
};
