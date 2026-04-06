<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_receipt_payments', function (Blueprint $table) {
            $table->foreignId('payment_order_id')->nullable()->after('collection_receipt_id')->constrained('payment_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('collection_receipt_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_order_id');
        });
    }
};
