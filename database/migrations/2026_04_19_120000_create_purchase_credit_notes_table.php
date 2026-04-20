<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            $table->string('credit_note_number');
            $table->string('voucher_type', 1);
            $table->string('point_of_sale')->default('');
            $table->date('issue_date');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('iva_21', 14, 2)->default(0);
            $table->decimal('iva_10_5', 14, 2)->default(0);
            $table->decimal('iva_27', 14, 2)->default(0);
            $table->decimal('percepciones', 14, 2)->default(0);
            $table->decimal('otros_impuestos', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['company_id', 'supplier_id', 'point_of_sale', 'credit_note_number'], 'purchase_credit_notes_doc_unique');
        });

        Schema::create('purchase_credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supply_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_service_id')->nullable()->constrained('purchase_services')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 14, 2);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('iva_rate', 5, 2);
            $table->decimal('iva_amount', 14, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_credit_note_items');
        Schema::dropIfExists('purchase_credit_notes');
    }
};
