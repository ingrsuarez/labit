<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained('bank_statements')->cascadeOnDelete();
            $table->date('date');
            $table->date('value_date')->nullable();
            $table->string('concept', 255);
            $table->string('bank_code', 10)->nullable();
            $table->string('document_number', 50)->nullable();
            $table->string('office', 100)->nullable();
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->nullable();
            $table->string('detail', 500)->nullable();
            $table->string('category', 30)->nullable();
            $table->enum('reconciliation_status', ['pending', 'matched', 'ignored'])->default('pending');
            $table->string('reconciled_type')->nullable();
            $table->unsignedBigInteger('reconciled_id')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['bank_statement_id', 'date']);
            $table->index('reconciliation_status');
            $table->index(['reconciled_type', 'reconciled_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_movements');
    }
};
