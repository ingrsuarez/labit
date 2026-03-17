<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_number');
            $table->string('voucher_type', 1);
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices');
            $table->date('issue_date');
            $table->text('reason');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('iva_21', 12, 2)->default(0);
            $table->decimal('iva_10_5', 12, 2)->default(0);
            $table->decimal('iva_27', 12, 2)->default(0);
            $table->decimal('percepciones', 12, 2)->default(0);
            $table->decimal('otros_impuestos', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status', 20)->default('pendiente');
            $table->boolean('is_electronic')->default(false);
            $table->string('cae', 20)->nullable();
            $table->date('cae_expiration')->nullable();
            $table->unsignedInteger('afip_voucher_number')->nullable();
            $table->string('afip_result', 20)->nullable();
            $table->json('afip_response')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
