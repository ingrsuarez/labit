<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('cae', 20)->nullable()->after('notes');
            $table->date('cae_expiration')->nullable()->after('cae');
            $table->unsignedInteger('afip_voucher_number')->nullable()->after('cae_expiration');
            $table->string('afip_result', 20)->nullable()->after('afip_voucher_number');
            $table->json('afip_response')->nullable()->after('afip_result');
            $table->boolean('is_electronic')->default(false)->after('afip_response');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['cae', 'cae_expiration', 'afip_voucher_number', 'afip_result', 'afip_response', 'is_electronic']);
        });
    }
};
