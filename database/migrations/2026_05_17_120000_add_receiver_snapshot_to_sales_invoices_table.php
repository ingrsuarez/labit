<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->change();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->string('receiver_name')->nullable()->after('customer_id');
            $table->string('receiver_tax_condition')->nullable()->after('receiver_name');
            $table->unsignedSmallInteger('receiver_document_type')->nullable()->after('receiver_tax_condition');
            $table->string('receiver_document_number')->nullable()->after('receiver_document_type');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn([
                'receiver_name',
                'receiver_tax_condition',
                'receiver_document_type',
                'receiver_document_number',
            ]);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable(false)->change();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }
};
