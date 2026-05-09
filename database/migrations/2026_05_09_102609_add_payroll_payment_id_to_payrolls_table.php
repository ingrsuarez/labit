<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('payrolls', 'payroll_payment_id')) {
                $table->foreignId('payroll_payment_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('payroll_payments')
                    ->nullOnDelete();
            } else {
                $table->foreign('payroll_payment_id')
                    ->references('id')
                    ->on('payroll_payments')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['payroll_payment_id']);
            $table->dropColumn('payroll_payment_id');
        });
    }
};
