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
        Schema::create('purchase_credit_note_perceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_perception_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('accounting_account_id')
                ->constrained('accounting_accounts')
                ->restrictOnDelete();
            $table->string('name_snapshot');
            $table->string('jurisdiction_snapshot')->nullable();
            $table->decimal('rate_snapshot', 5, 2)->default(0);
            $table->decimal('amount', 14, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('purchase_credit_note_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_credit_note_perceptions');
    }
};
