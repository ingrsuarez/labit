<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_return_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_return_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('purchase_invoice_perception_id')->nullable();
            $table->unsignedBigInteger('purchase_credit_note_perception_id')->nullable();
            $table->decimal('amount_applied', 14, 2);
            $table->timestamps();

            $table->foreign('purchase_invoice_perception_id', 'tra_applications_pip_fk')
                ->references('id')->on('purchase_invoice_perceptions')->restrictOnDelete();
            $table->foreign('purchase_credit_note_perception_id', 'tra_applications_pcnp_fk')
                ->references('id')->on('purchase_credit_note_perceptions')->restrictOnDelete();

            $table->index('tax_return_id');
            $table->index('purchase_invoice_perception_id');
            $table->index('purchase_credit_note_perception_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_return_applications');
    }
};
