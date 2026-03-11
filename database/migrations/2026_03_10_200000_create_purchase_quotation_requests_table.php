<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_quotation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->date('valid_until')->nullable();
            $table->enum('status', ['borrador', 'enviada', 'recibida', 'cancelada'])->default('borrador');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('purchase_quotation_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_quotation_request_id')
                  ->constrained('purchase_quotation_requests', 'id', 'pqr_items_request_id_fk')
                  ->cascadeOnDelete();
            $table->foreignId('supply_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_quotation_request_items');
        Schema::dropIfExists('purchase_quotation_requests');
    }
};
