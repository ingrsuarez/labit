<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'purchase_quotation_requests',
            'purchase_orders',
            'delivery_notes',
            'purchase_invoices',
            'payment_orders',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('company_id')->nullable()->after('id')->constrained('companies');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'payment_orders',
            'purchase_invoices',
            'delivery_notes',
            'purchase_orders',
            'purchase_quotation_requests',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('company_id');
            });
        }
    }
};
