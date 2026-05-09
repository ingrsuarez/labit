<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->smallInteger('year');
            $table->tinyInteger('month');
            $table->string('period_label');
            $table->date('payment_date')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedInteger('employee_count')->default(0);
            $table->string('status')->default('borrador'); // borrador | confirmado
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payments');
    }
};
