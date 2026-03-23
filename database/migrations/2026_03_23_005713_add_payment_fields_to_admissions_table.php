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
        Schema::table('admissions', function (Blueprint $table) {
            $table->string('payment_status')->default('not_applicable')->after('status')
                ->comment('not_applicable|pendiente|parcial|pagado');
            $table->string('payment_method')->nullable()->after('payment_status')
                ->comment('efectivo|transferencia|mercadopago');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('payment_method');
            $table->timestamp('payment_date')->nullable()->after('paid_amount');
            $table->text('payment_notes')->nullable()->after('payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_method', 'paid_amount', 'payment_date', 'payment_notes']);
        });
    }
};
